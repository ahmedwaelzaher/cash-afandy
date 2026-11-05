<?php

namespace App\Actions\Wallets;

use App\Enums\NotificationType;
use App\Enums\WalletTransactionSource;
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
    /**
     * Post a validated wallet transaction and update the balance atomically.
     */
    public function handle(
        User $user,
        WalletTransactionType $type,
        WalletTransactionSource $sourceCategory,
        int $amount,
        Model $source,
        string $description,
        string $idempotencyKey,
        ?Admin $admin = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $type, $sourceCategory, $amount, $source, $description, $idempotencyKey, $admin) {
            // Use the same owner-first lock order as account completion.
            $owner = User::withTrashed()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $wallet = $owner->wallet()->lockForUpdate()->first();
            if (! $wallet) {
                throw ValidationException::withMessages(['amount' => __('The user must confirm their account country before using the wallet.')]);
            }

            // Reuse an identical request and reject conflicting request references.
            $existing = $wallet->transactions()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->type !== $type || $existing->source_category !== $sourceCategory || $existing->amount !== $amount
                    || $existing->source_type !== $source->getMorphClass() || (string) $existing->source_id !== (string) $source->getKey()
                    || $existing->admin_id !== $admin?->getKey() || $existing->description !== $description) {
                    throw ValidationException::withMessages(['idempotency_key' => __('This request reference has already been used for another transaction.')]);
                }

                return $existing;
            }

            // Check the resulting balance before recording the transaction.
            $balance = $wallet->balance + ($type === WalletTransactionType::Credit ? $amount : -$amount);

            if ($balance < 0) {
                throw ValidationException::withMessages(['amount' => __('The wallet has insufficient available balance.')]);
            }

            if ($balance > Wallet::MAX_BALANCE) {
                throw ValidationException::withMessages(['amount' => __('The wallet balance limit would be exceeded.')]);
            }

            // Record the transaction and its resulting wallet balance together.
            $transaction = $wallet->transactions()->create([
                'reference' => (string) Str::uuid(),
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'source_category' => $sourceCategory,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'admin_id' => $admin?->getKey(),
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
            ]);

            $wallet->update(['balance' => $balance]);

            // Notify the owner using their notification preferences.
            $this->notify($owner, $transaction);

            return $transaction;
        }, 3);
    }

    /**
     * Notify the wallet owner about the posted transaction.
     */
    protected function notify(User $user, WalletTransaction $transaction): void
    {
        if (! $user->receivesWebsiteNotification(NotificationType::Wallet)) return;

        // Use the owner's preferred language when it is available on the website.
        $locale = $user->preferences?->language;
        if (! in_array($locale, setting('website_locales'), true)) $locale = config('app.fallback_locale');

        // Build the credit or debit notification in the selected language.
        $body = $transaction->type === WalletTransactionType::Credit
            ? __(':amount :currency was added to your wallet.', ['amount' => Wallet::formatAmount($transaction->amount), 'currency' => $transaction->wallet->currency], $locale)
            : __(':amount :currency was deducted from your wallet.', ['amount' => Wallet::formatAmount($transaction->amount), 'currency' => $transaction->wallet->currency], $locale);

        // Queue delivery through the existing website notification flow.
        $user->notify((new WebsiteNotification(
            title: __('Balance', [], $locale),
            url: route('website.wallet.show', ['locale' => $locale]),
            data: ['wallet_transaction_id' => $transaction->id],
            body: $body,
            type: NotificationType::Wallet,
        ))->locale($locale));
    }
}
