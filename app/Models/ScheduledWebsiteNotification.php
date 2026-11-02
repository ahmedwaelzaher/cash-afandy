<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledWebsiteNotification extends Model
{
    protected $fillable = [
        'title',
        'body',
        'url',
        'recipient_ids',
        'send_at',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function scopePending(Builder $query): void
    {
        $query->whereNull('sent_at');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ScheduledNotificationDelivery::class);
    }

    public function scopeDue(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $legacy) => $legacy->doesntHave('deliveries')->where('send_at', '<=', now()))
            ->orWhereHas('deliveries', fn (Builder $deliveries) => $deliveries->whereNull('sent_at')->where('send_at', '<=', now())));
    }
}
