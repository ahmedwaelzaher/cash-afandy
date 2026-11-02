<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Offer;
use App\Models\OfferNotificationEvent;
use App\Notifications\WebsiteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SendOfferNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(): void
    {
        Offer::published()->notExpired()->whereHas('countries')
            ->whereDoesntHave('notificationEvents', fn (Builder $events) => $events->where('type', NotificationType::NewOffer->value))
            ->chunkById(100, function ($offers) {
                foreach ($offers as $offer) {
                    OfferNotificationEvent::firstOrCreate(['event_key' => 'new_offer:' . $offer->id], [
                        'offer_id' => $offer->id, 'type' => NotificationType::NewOffer,
                    ]);
                }
            });

        Offer::published()->notExpired()->whereHas('countries')->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDay())->chunkById(100, function ($offers) {
                foreach ($offers as $offer) {
                    $offer->favoritedBy()->whereDoesntHave('offerNotificationEvents', fn (Builder $events) => $events
                        ->where('offer_id', $offer->id)->where('type', NotificationType::FavoriteOfferExpiring->value)
                        ->where('expiration_date', $offer->expiration_date))
                        ->chunkById(100, function ($users) use ($offer) {
                            foreach ($users as $user) {
                                OfferNotificationEvent::firstOrCreate([
                                    'event_key' => 'favorite_offer_expiring:' . $offer->id . ':' . $user->id . ':' . $offer->expiration_date->timestamp,
                                ], [
                                    'offer_id' => $offer->id, 'user_id' => $user->id,
                                    'type' => NotificationType::FavoriteOfferExpiring, 'expiration_date' => $offer->expiration_date,
                                ]);
                            }
                        }, 'users.id', 'id');
                }
            });

        OfferNotificationEvent::whereNull('processed_at')->chunkById(100, function ($events) {
            foreach ($events as $event) {
                do {
                    $more = DB::transaction(function () use ($event) {
                        $entry = OfferNotificationEvent::whereKey($event->id)->whereNull('processed_at')->lockForUpdate()->first();
                        if (! $entry) return false;
                        // Commit each recipient batch and its cursor together so retries can resume safely.
                        $users = $entry->recipientQuery()->with('preferences')->where('id', '>', $entry->recipient_cursor)
                            ->orderBy('id')->limit(100)->get();
                        $offer = $entry->offer;
                        foreach ($users as $user) {
                            $locale = $user->preferences?->language;
                            if (! in_array($locale, setting('website_locales'), true)) $locale = config('app.fallback_locale');
                            $name = $offer->getTranslation('title', $locale);
                            $newOffer = $entry->type === NotificationType::NewOffer;
                            $title = $newOffer ? __('New offer: :offer', ['offer' => $name], $locale)
                                : __('A favorite offer expires soon', [], $locale);
                            $body = $newOffer ? __('The offer :offer is now available.', ['offer' => $name], $locale)
                                : __('Your saved offer :offer expires within 24 hours.', ['offer' => $name], $locale);
                            Notification::sendNow($user, new WebsiteNotification(
                                title: Str::limit($title, 140),
                                url: route('website.offers.show', ['offer' => $offer->id, 'locale' => $locale]),
                                data: ['offer_event_id' => $entry->id],
                                body: Str::limit($body, 320),
                                type: $entry->type,
                            ));
                        }
                        $entry->recipient_cursor = $users->last()?->id ?? $entry->recipient_cursor;
                        if ($users->count() < 100) $entry->processed_at = now();
                        $entry->save();

                        return $entry->processed_at === null;
                    });
                } while ($more);
            }
        });
    }
}
