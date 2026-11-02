<?php

namespace App\Enums;

enum NotificationType: string
{
    case General = 'general';
    case NewOffer = 'new_offer';
    case FavoriteOfferExpiring = 'favorite_offer_expiring';

    public function label(): string
    {
        return match ($this) {
            self::General => __('General notifications'),
            self::NewOffer => __('New offers and coupons'),
            self::FavoriteOfferExpiring => __('Expiring favorite offers'),
        };
    }

    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
