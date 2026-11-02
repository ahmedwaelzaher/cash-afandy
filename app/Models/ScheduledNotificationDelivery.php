<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledNotificationDelivery extends Model
{
    protected $fillable = ['timezone', 'recipient_ids', 'send_at'];

    protected $casts = ['recipient_ids' => 'array', 'send_at' => 'datetime', 'sent_at' => 'datetime'];
}
