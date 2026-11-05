<?php

namespace App\Actions\Wallets;

use App\Enums\WalletPayoutMethod;
use App\Enums\WalletPayoutStatus;
use App\Enums\WalletTransactionSource;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestWalletPayout
{
    /**
     * Record a validated payout request and reserve its available balance.
     */
    public function handle(User $user, WalletPayoutMethod $method, int $amount, array $details, string $idempotencyKey): WalletPayout
    {
        return DB::transaction(function () use ($user, $method, $amount, $details, $idempotencyKey) {
            // Match the owner-first locking order used by wallet transactions.
            $owner = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $wallet = $owner->wallet()->lockForUpdate()->firstOrFail();

            // Return the original request when a submission is retried unchanged.
            $existing = $wallet->payouts()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->method !== $method || $existing->amount !== $amount || $existing->details !== $details) {
                    throw ValidationException::withMessages(['idempotency_key' => __('This request reference has already been used for another transaction.')]);
                }

                return $existing;
            }

            // Store the destination separately from the public transaction history.
            $payout = $wallet->payouts()->create([
                'reference' => (string) Str::uuid(),
                'method' => $method,
                'amount' => $amount,
                'details' => $details,
                'status' => WalletPayoutStatus::Pending,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Reserve funds through the existing ledger and balance checks.
            app(PostWalletTransaction::class)->handle(
                user: $owner,
                type: WalletTransactionType::Debit,
                sourceCategory: WalletTransactionSource::PayoutRequest,
                amount: $amount,
                source: $payout,
                description: __('Payout request') . ' - ' . $method->label(),
                idempotencyKey: $idempotencyKey,
            );

            return $payout;
        }, 3);
    }
}
