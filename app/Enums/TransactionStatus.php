<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Confirmed = 'confirmed';
    case Pending = 'pending';

    /**
     * Get the transaction statuses as a value => label map.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * Get the label for the transaction status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Confirmed => __('Confirmed'),
            self::Pending => __('Pending'),
        };
    }
}
