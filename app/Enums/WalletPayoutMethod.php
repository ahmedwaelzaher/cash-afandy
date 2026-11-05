<?php

namespace App\Enums;

enum WalletPayoutMethod: string
{
    /**
     * Get the label for the payout method.
     */
    public function label(): string
    {
        return match ($this) {
        };
    }

    /**
     * Get the icon for the payout method.
     */
    public function icon(): string
    {
        return match ($this) {
        };
    }

    /**
     * Get the action responsible for the payout method.
     */
    public function action(): string
    {
        return match ($this) {
        };
    }
}
