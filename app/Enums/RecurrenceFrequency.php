<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Get the recurrence frequencies as a value => label map.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $frequency) => [$frequency->value => $frequency->label()])
            ->all();
    }

    /**
     * Get the label for the recurrence frequency.
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekly => __('Weekly'),
            self::Monthly => __('Monthly'),
            self::Yearly => __('Yearly'),
        };
    }

    /**
     * Get how many times this frequency happens in an average month.
     */
    public function timesPerMonth(): float
    {
        return match ($this) {
            self::Weekly => 52 / 12,
            self::Monthly => 1,
            self::Yearly => 1 / 12,
        };
    }

    /**
     * Get the nth occurrence counted from the given start date.
     *
     * Always counted from the start, so a 31st keeps landing on month ends instead of drifting to the 28th.
     */
    public function occurrence(CarbonInterface $start, int $n): CarbonInterface
    {
        return match ($this) {
            self::Weekly => $start->copy()->addWeeks($n),
            self::Monthly => $start->copy()->addMonthsNoOverflow($n),
            self::Yearly => $start->copy()->addYearsNoOverflow($n),
        };
    }
}
