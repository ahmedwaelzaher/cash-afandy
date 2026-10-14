<?php

namespace App\Enums;

enum RecurrenceNature: string
{
    case Fixed = 'fixed';
    case Variable = 'variable';

    /**
     * Get the recurrence natures as a value => label map.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $nature) => [$nature->value => $nature->label()])
            ->all();
    }

    /**
     * Get the label for the recurrence nature.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fixed => __('Fixed'),
            self::Variable => __('Variable'),
        };
    }

    /**
     * Get the status of the transactions posted for this nature: fixed amounts are known, variable ones need confirming.
     */
    public function transactionStatus(): TransactionStatus
    {
        return match ($this) {
            self::Fixed => TransactionStatus::Confirmed,
            self::Variable => TransactionStatus::Pending,
        };
    }
}
