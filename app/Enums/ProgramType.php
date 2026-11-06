<?php

namespace App\Enums;

enum ProgramType: string
{
    /**
     * Get the program type's display label.
     */
    public function label(): string
    {
        return match ($this) {
        };
    }

    /**
     * Get the action exposing handle() eligibility and rewardAmount() in minor currency units.
     */
    public function action(): string
    {
        return match ($this) {
        };
    }

    /**
     * Get supported program types for the management form.
     */
    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
