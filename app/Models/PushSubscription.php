<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

class PushSubscription extends Model
{
    use Notifiable;

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

    public static function disableFor(?Model $notifiable, ?string $tokenHash): void
    {
        if (! $tokenHash) return;

        static::query()->whereMorphedTo('notifiable', $notifiable)
            ->where('token_hash', $tokenHash)->update(['disabled_at' => now()]);
    }
}
