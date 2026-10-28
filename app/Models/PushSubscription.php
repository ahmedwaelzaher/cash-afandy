<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'token',
        'last_seen_at',
        'disabled_at',
    ];

    protected $hidden = ['token', 'token_hash'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    protected function token(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => [
                'token' => $value,
                'token_hash' => hash('sha256', $value),
            ],
        );
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
