<?php

namespace App\Enums;

enum NotificationType: string
{
    case General = 'general';
    case NewOffer = 'new_offer';
    case FavoriteOfferExpiring = 'favorite_offer_expiring';
    case NewReferral = 'new_referral';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::General => __('General notifications'),
            self::NewOffer => __('New offers and coupons'),
            self::FavoriteOfferExpiring => __('Expiring favorite offers'),
            self::NewReferral => __('New referral registrations'),
            self::Wallet => __('Balance'),
        };
    }

    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }

    public function icon(): string
    {
        return match ($this) {
            self::General => 'ti ti-bell',
            self::NewOffer, self::FavoriteOfferExpiring => 'ti ti-ticket',
            self::NewReferral => 'ti ti-user-plus',
            self::Wallet => 'ti ti-wallet',
        };
    }
}
