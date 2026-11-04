<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => __('Credit'),
            self::Debit => __('Debit'),
        };
    }

    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
