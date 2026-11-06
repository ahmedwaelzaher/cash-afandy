<?php

namespace App\Enums;

enum ProgramRewardStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Rejected = 'rejected';

    /**
     * Get the reward status shown to the user and administrators.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending review'),
            self::Paid => __('Paid'),
            self::Rejected => __('Rejected'),
        };
    }
}
