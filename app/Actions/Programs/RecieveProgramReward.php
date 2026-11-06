<?php

namespace App\Actions\Programs;

use App\Actions\Wallets\PostWalletTransaction;
use App\Enums\ProgramRewardStatus;
use App\Enums\WalletTransactionSource;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\ProgramReward;
use App\Models\ProgramSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecieveProgramReward
{
    /**
     * Finalize an earned reward and credit its wallet at most once.
     */
    public function handle(ProgramReward $reward, bool $approve, ?Admin $admin = null, ?string $note = null): void
    {
        DB::transaction(function () use ($reward, $approve, $admin, $note) {
            // Serialize review and payment using the wallet owner's lock first.
            $owner = User::withTrashed()->whereKey($reward->subscription->user_id)->lockForUpdate()->firstOrFail();
            $subscription = ProgramSubscription::whereKey($reward->program_subscription_id)->lockForUpdate()->firstOrFail();
            $reward = ProgramReward::whereKey($reward->id)->lockForUpdate()->firstOrFail();
            if ($reward->status !== ProgramRewardStatus::Pending) return;

            // An earned reward remains reviewable after the program expires or pauses.
            if ($approve) {
                if ($owner->trashed() || $owner->wallet?->currency !== $reward->currency) {
                    throw ValidationException::withMessages(['reward' => __('The reward cannot be paid to this account wallet.')]);
                }

                app(PostWalletTransaction::class)->handle(
                    user: $owner,
                    type: WalletTransactionType::Credit,
                    sourceCategory: WalletTransactionSource::ProgramReward,
                    amount: $reward->amount,
                    source: $reward,
                    description: '',
                    idempotencyKey: $reward->idempotency_key,
                    admin: $admin,
                );

                // A new participation attempt becomes possible only after successful payment.
                $subscription->update(['completed_at' => now()]);
            }

            // Retain the reviewer and reason without completing unpaid participation.
            $reward->update([
                'status' => $approve ? ProgramRewardStatus::Paid : ProgramRewardStatus::Rejected,
                'admin_id' => $admin?->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);
        }, 3);
    }
}
