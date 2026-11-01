<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
}
