<?php

namespace App\Actions\Wallets;

use App\Enums\WalletPayoutStatus;
use App\Enums\WalletTransactionSource;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\User;
use App\Models\WalletPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReviewWalletPayout
{
    /**
     * Record an administrator's payment confirmation or refund a rejected request.
     */
    public function handle(WalletPayout $payout, WalletPayoutStatus $status, Admin $admin, ?string $transferReference, ?string $note): WalletPayout
    {
        return DB::transaction(function () use ($payout, $status, $admin, $transferReference, $note) {
            // Lock the owner, wallet and request in a consistent order.
            $owner = User::withTrashed()->whereKey($payout->wallet->user_id)->lockForUpdate()->firstOrFail();
            $owner->wallet()->lockForUpdate()->firstOrFail();
            $payout = WalletPayout::whereKey($payout->getKey())->lockForUpdate()->firstOrFail();

            // A final decision cannot refund or settle the same request again.
            if ($payout->status !== WalletPayoutStatus::Pending) {
                throw ValidationException::withMessages(['status' => __('This payout request has already been reviewed.')]);
            }

            // Return reserved funds when the administrator rejects the request.
            if ($status === WalletPayoutStatus::Rejected) {
                app(PostWalletTransaction::class)->handle(
                    user: $owner,
                    type: WalletTransactionType::Credit,
                    sourceCategory: WalletTransactionSource::PayoutRefund,
                    amount: $payout->amount,
                    source: $payout,
                    description: __('Payout refund'),
                    idempotencyKey: (string) Str::uuid(),
                    admin: $admin,
                );
            }

            // Payment confirmation records an external transfer already made by the administrator.
            $payout->update([
                'status' => $status,
                'admin_id' => $admin->getKey(),
                'transfer_reference' => $transferReference,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            return $payout;
        }, 3);
    }
}
