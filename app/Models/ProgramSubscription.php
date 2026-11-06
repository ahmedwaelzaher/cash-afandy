<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProgramSubscription extends Model
{
    /**
     * The subscription identity and displayed reward snapshot.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'user_id',
        'reward_amount',
        'completed_at',
    ];

    /**
     * The monetary snapshot and payment completion time for this participation attempt.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reward_amount' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the subscribed program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the subscriber, retaining the financial history of deleted accounts.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the single reward earned by this participation attempt.
     */
    public function reward(): HasOne
    {
        return $this->hasOne(ProgramReward::class);
    }

    /**
     * Determine whether the subscription can receive further qualifying activity.
     */
    public function canProgress(): bool
    {
        return $this->completed_at === null && ! $this->user->trashed() && $this->program->isAvailable()
            && $this->user->country_id === $this->program->country_id;
    }
}
