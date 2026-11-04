<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'registered_at',
        'accepted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'registered_at' => 'datetime',
        'accepted' => 'boolean',
    ];

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('accepted', true);
    }

    /**
     * Get the referrer for this referral.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id')->withTrashed();
    }

    /**
     * Get the referred user for this referral.
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id')->withTrashed();
    }
}
