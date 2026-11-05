<?php

namespace App\Enums;

enum WalletPayoutStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Rejected = 'rejected';

    /**
     * Get the label for the payout status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Awaiting review'),
            self::Paid => __('Paid'),
            self::Rejected => __('Rejected'),
        };
    }

    /**
     * Get the badge color for the payout status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Paid => 'green',
            self::Rejected => 'red',
        };
    }
}
