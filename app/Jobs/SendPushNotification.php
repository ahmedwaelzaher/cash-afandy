<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\OfferNotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public bool $forBrowser = false;

    public function __construct(
        public string $notificationId,
        public int $subscriptionId,
        public string $tokenHash,
        bool $forBrowser = false,
    ) {
        $this->forBrowser = $forBrowser;
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        if (! config('redot.notifications.push_enabled', false)) return;

        $subscription = PushSubscription::query()
            ->whereKey($this->subscriptionId)
            ->where('token_hash', $this->tokenHash)
            ->whereNull('disabled_at')
            ->first();

        if (! $subscription) {
            return;
        }

        $recipient = $this->forBrowser ? $subscription : $subscription->notifiable;
        $notification = $recipient?->notifications()->whereKey($this->notificationId)->first();

        if (! $notification) return;

        // An account can opt out after inbox storage but before this push job runs.
        $owner = $this->forBrowser ? $subscription->notifiable : $recipient;
        $type = NotificationType::tryFrom($notification->data['type'] ?? NotificationType::General->value);
        $fromDashboard = ($notification->data['data']['source'] ?? null) === 'dashboard';
        if ($owner instanceof User && (! $type || ! $owner->receivesWebsiteNotification($fromDashboard ? null : $type))) return;
        if (isset($notification->data['data']['offer_event_id'])) {
            $event = OfferNotificationEvent::find($notification->data['data']['offer_event_id']);
            if (! $owner instanceof User || ! $event?->allowsRecipient($owner)) return;
        }

        $payload = WebsiteNotification::browserPayload($notification, $event ?? null);

        $message = CloudMessage::new()->withToken($subscription->token)
            ->withNotification($payload['notification'])
            ->withData($payload['data'])
            ->withWebPushConfig(['notification' => ['tag' => $this->notificationId]]);

        try {
            app(Messaging::class)->send($message);
        } catch (NotFound $exception) {
            $details = $exception->errors()['error']['details'] ?? [];

            // A missing project or a mismatched environment must not disable a device.
            if (! collect($details)->contains('errorCode', 'UNREGISTERED')) {
                throw $exception;
            }

            PushSubscription::query()
                ->whereKey($this->subscriptionId)
                ->where('notifiable_type', $subscription->notifiable_type)
                ->where('notifiable_id', $subscription->notifiable_id)
                ->where('token_hash', $this->tokenHash)
                ->update(['disabled_at' => now()]);
        }
    }
}
