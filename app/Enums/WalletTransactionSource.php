<?php

namespace App\Enums;

enum WalletTransactionSource: string
{
    case AdminAdjustment = 'admin_adjustment';
    case PayoutRequest = 'payout_request';
    case PayoutRefund = 'payout_refund';

    /**
     * Get the label for the wallet transaction source.
     */
    public function label(): string
    {
        return match ($this) {
            self::AdminAdjustment => __('Administrative adjustment'),
            self::PayoutRequest => __('Payout request'),
            self::PayoutRefund => __('Payout refund'),
        };
    }

    /**
     * Get the wallet transaction sources as a value => label map.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $source) => [$source->value => $source->label()])
            ->all();
    }
}
