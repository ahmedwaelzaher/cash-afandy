<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Wallet extends Model
{
    public const MAX_BALANCE = 999_999_999_999;

    protected $fillable = ['user_id', 'currency', 'balance'];

    protected $casts = ['balance' => 'integer'];

    protected static function booted(): void
    {
        static::updating(function (Wallet $wallet) {
            if ($wallet->isDirty(['currency', 'user_id'])) {
                throw ValidationException::withMessages(['wallet_currency' => __('The wallet currency cannot be changed after it is selected.')]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** Format minor units without converting the monetary value to a float. */
    public static function formatAmount(int $amount): string
    {
        return number_format(intdiv($amount, 100)) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
