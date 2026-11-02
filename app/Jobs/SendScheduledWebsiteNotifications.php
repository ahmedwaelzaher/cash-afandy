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

    public ?int $scheduleId = null;

    public function __construct(?int $scheduleId = null)
    {
        $this->scheduleId = $scheduleId;
    }

    public function handle(): void
    {
        ScheduledWebsiteNotification::query()->pending()->due()
            ->when($this->scheduleId, fn ($query) => $query->whereKey($this->scheduleId))
            ->chunkById(100, function ($schedules) {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule) {
                        // Sending and cancellation lock the same row; concurrent workers recheck it.
                        $entry = ScheduledWebsiteNotification::query()->pending()->whereKey($schedule->id)
                            ->due()->lockForUpdate()->first();
                        if (! $entry) return;

                        $notification = new WebsiteNotification($entry->title, $entry->url, data: ['source' => 'dashboard'], body: $entry->body);
                        $deliveries = $entry->deliveries()->whereNull('sent_at')->where('send_at', '<=', now())->get();
                        $batches = $entry->deliveries()->exists() ? $deliveries : [$entry];
                        foreach ($batches as $batch) {
                            User::query()->whereKey($batch->recipient_ids)->chunkById(100, function ($users) use ($notification) {
                                // Store inbox rows and batch completion together; push stays queued after commit.
                                Notification::sendNow($users, $notification);
                            });
                            $batch->sent_at = now();
                            $batch->save();
                        }
                        if (! $entry->deliveries()->whereNull('sent_at')->exists()) {
                            $entry->sent_at = now();
                            $entry->save();
                        }
                    });
                }
            });
    }
}
