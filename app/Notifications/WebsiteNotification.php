<?php

namespace App\Notifications;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Jobs\SendPushNotification;
use App\Models\OfferNotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;

class WebsiteNotification extends DashboardNotification implements ShouldQueue
{
    public int $tries = 3;

    public NotificationType $type = NotificationType::General;

    public function __construct(
        string $title,
        ?string $url = null,
        NotificationLevel|string|null $level = null,
        array $data = [],
        bool $lightbox = false,
        public string $body = '',
        public bool $push = true,
        NotificationType $type = NotificationType::General,
    ) {
        parent::__construct($title, $url, $level, $data, $lightbox);
        $this->type = $type;

        $this->afterCommit();
    }

    public function toArray(object $notifiable): array
    {
        return [...parent::toArray($notifiable), 'body' => $this->body, 'type' => $this->type->value];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $recipient = $notifiable instanceof PushSubscription ? $notifiable->notifiable : $notifiable;

        if ($recipient instanceof User && ! $recipient->receivesWebsiteNotification($this->type)) return false;
        if (isset($this->data['offer_event_id'])) {
            $event = OfferNotificationEvent::find($this->data['offer_event_id']);

            return $recipient instanceof User && $event?->allowsRecipient($recipient);
        }

        return true;
    }

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        if (! $this->push || ! config('redot.notifications.push_enabled', false)
            || $channel !== 'database'
            || ! $response instanceof DatabaseNotification
        ) {
            return;
        }

        // Push is queued only after the database channel has stored the notification.
        if ($notifiable instanceof PushSubscription) {
            SendPushNotification::dispatch($response->getKey(), $notifiable->getKey(), $notifiable->token_hash, forBrowser: true);

            return;
        }

        PushSubscription::query()
            ->where('notifiable_type', $response->notifiable_type)
            ->where('notifiable_id', $response->notifiable_id)
            ->whereNull('disabled_at')->chunkById(100, function ($subscriptions) use ($response) {
                foreach ($subscriptions as $subscription) {
                    SendPushNotification::dispatch(
                        $response->getKey(),
                        $subscription->getKey(),
                        $subscription->token_hash,
                    );
                }
            });
    }
}
