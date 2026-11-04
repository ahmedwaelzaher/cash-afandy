<?php

namespace App\Actions\Wallets;

use App\Enums\NotificationType;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostWalletTransaction
{
    public function handle(
        User $user,
        WalletTransactionType $type,
        int $amount,
        Model $source,
        string $description,
        string $idempotencyKey,
        ?Admin $admin = null,
    ): WalletTransaction {
        $description = trim($description);
        if ($amount < 1 || $amount > Wallet::MAX_BALANCE) {
            throw ValidationException::withMessages(['amount' => __('The wallet amount is out of range.')]);
        }
        if ($description === '' || mb_strlen($description) > 500 || ! Str::isUuid($idempotencyKey) || ! $source->exists) {
            throw ValidationException::withMessages(['description' => __('A valid source, reason and request reference are required.')]);
        }

        return DB::transaction(function () use ($user, $type, $amount, $source, $description, $idempotencyKey, $admin) {
            // Use the same owner-first lock order as account completion.
            $owner = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $wallet = $owner->wallet()->lockForUpdate()->first();
            if (! $wallet) {
                throw ValidationException::withMessages(['amount' => __('The user must confirm their account country before using the wallet.')]);
            }

            $existing = $wallet->transactions()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->type !== $type || $existing->amount !== $amount
                    || $existing->source_type !== $source->getMorphClass() || (string) $existing->source_id !== (string) $source->getKey()
                    || $existing->admin_id !== $admin?->getKey() || $existing->description !== $description) {
                    throw ValidationException::withMessages(['idempotency_key' => __('This request reference has already been used for another transaction.')]);
                }

                return $existing;
            }

            $balance = $wallet->balance + ($type === WalletTransactionType::Credit ? $amount : -$amount);
            if ($balance < 0) {
                throw ValidationException::withMessages(['amount' => __('The wallet has insufficient available balance.')]);
            }
            if ($balance > Wallet::MAX_BALANCE) {
                throw ValidationException::withMessages(['amount' => __('The wallet balance limit would be exceeded.')]);
            }

            $transaction = $wallet->transactions()->create([
                'reference' => (string) Str::uuid(),
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'admin_id' => $admin?->getKey(),
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
            ]);
            $wallet->update(['balance' => $balance]);
            $this->notify($owner, $transaction);

            return $transaction;
        }, 3);
    }

    protected function notify(User $user, WalletTransaction $transaction): void
    {
        if (! $user->receivesWebsiteNotification(NotificationType::Wallet)) return;

        $locale = $user->preferences?->language;
        if (! in_array($locale, setting('website_locales'), true)) $locale = config('app.fallback_locale');
        $body = $transaction->type === WalletTransactionType::Credit
            ? __(':amount :currency was added to your wallet.', ['amount' => Wallet::formatAmount($transaction->amount), 'currency' => $transaction->wallet->currency], $locale)
            : __(':amount :currency was deducted from your wallet.', ['amount' => Wallet::formatAmount($transaction->amount), 'currency' => $transaction->wallet->currency], $locale);

        $user->notify((new WebsiteNotification(
            title: __('Afandy Wallet', [], $locale),
            url: route('website.wallet.show', ['locale' => $locale]),
            data: ['wallet_transaction_id' => $transaction->id],
            body: $body,
            type: NotificationType::Wallet,
        ))->locale($locale));
    }
}
