<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferNotificationEvent extends Model
{
    protected $fillable = ['event_key', 'offer_id', 'user_id', 'type', 'expiration_date'];

    protected $casts = ['type' => NotificationType::class, 'expiration_date' => 'datetime', 'processed_at' => 'datetime', 'recipient_cursor' => 'integer'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function recipientQuery(): Builder
    {
        $query = User::query();
        $offer = $this->offer()->published()->notExpired()->with('countries')->first();
        if (! $offer) return $query->whereRaw('1 = 0');

        if ($this->type === NotificationType::FavoriteOfferExpiring) {
            if (! $offer->expiration_date || ! $this->expiration_date?->equalTo($offer->expiration_date)
                || $offer->expiration_date->gt(now()->addDay())) return $query->whereRaw('1 = 0');
            $query->whereKey($this->user_id)->whereHas('favoriteOffers', fn (Builder $favorites) => $favorites->whereKey($offer->id));
        } else {
            // A later registration must not receive publication events from before that account existed.
            $query->where(fn (Builder $users) => $users->whereNull('created_at')->orWhere('created_at', '<=', $this->created_at));
        }

        $countryIds = $offer->countries->modelKeys();
        $defaultId = Country::code(setting('default_website_country'))?->id;
        if (! setting('show_website_countries_dropdown')) {
            return in_array($defaultId, $countryIds, true) ? $query : $query->whereRaw('1 = 0');
        }

        $supportedIds = Country::query()->whereIn('code', setting('website_countries'))->pluck('id')->all();
        $matchingIds = array_values(array_intersect($countryIds, $supportedIds));

        return $query->where(function (Builder $users) use ($matchingIds, $supportedIds, $defaultId, $countryIds) {
            $users->whereHas('preferences', fn (Builder $preferences) => $preferences->whereIn('country_id', $matchingIds));
            if (in_array($defaultId, $countryIds, true)) {
                $users->orWhereDoesntHave('preferences')->orWhereHas('preferences', fn (Builder $preferences) => $preferences->whereNull('country_id')->orWhereNotIn('country_id', $supportedIds));
            }
        });
    }

    public function allowsRecipient(User $user): bool
    {
        return $user->receivesWebsiteNotification($this->type) && $this->recipientQuery()->whereKey($user->id)->exists();
    }
}
