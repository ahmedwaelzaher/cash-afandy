<?php

namespace App\Jobs;

use App\Models\PushSubscription;
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

        $message = CloudMessage::new()->withToken($subscription->token)
            ->withNotification([
                'title' => $notification->data['title'],
                'body' => $notification->data['body'] ?? '',
            ])
            ->withData(['notification_id' => $this->notificationId])
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
