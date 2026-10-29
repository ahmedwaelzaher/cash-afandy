<?php

namespace App\Notifications;

use App\Enums\NotificationLevel;
use App\Jobs\SendPushNotification;
use App\Models\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;

class WebsiteNotification extends DashboardNotification implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(
        string $title,
        ?string $url = null,
        NotificationLevel|string|null $level = null,
        array $data = [],
        bool $lightbox = false,
        public string $body = '',
        public bool $push = true,
    ) {
        parent::__construct($title, $url, $level, $data, $lightbox);

        $this->afterCommit();
    }

    public function toArray(object $notifiable): array
    {
        return [...parent::toArray($notifiable), 'body' => $this->body];
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
