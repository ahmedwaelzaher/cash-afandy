<?php

namespace App\Enums;

enum NotificationType: string
{
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::General => __('General notifications'),
        };
    }

    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
