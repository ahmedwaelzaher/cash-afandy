<?php

namespace App\Jobs;

use App\Models\ScheduledWebsiteNotification;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendScheduledWebsiteNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(): void
    {
        ScheduledWebsiteNotification::query()->pending()->where('send_at', '<=', now())
            ->chunkById(100, function ($schedules) {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule) {
                        // Sending and cancellation lock the same row; concurrent workers recheck it.
                        $entry = ScheduledWebsiteNotification::query()->pending()->whereKey($schedule->id)
                            ->where('send_at', '<=', now())->lockForUpdate()->first();
                        if (! $entry) return;

                        $notification = new WebsiteNotification($entry->title, $entry->url, body: $entry->body);
                        User::query()->whereKey($entry->recipient_ids)->chunkById(100, function ($users) use ($notification) {
                            // Store inbox rows in this transaction; push remains queued after commit.
                            Notification::sendNow($users, $notification);
                        });
                        $entry->sent_at = now();
                        $entry->save();
                    });
                }
            });
    }
}
