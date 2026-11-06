<?php

namespace App\Models;

use App\Enums\ProgramRewardStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramReward extends Model
{
    /**
     * The earned reward and its review details.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_subscription_id',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'admin_id',
        'review_note',
        'reviewed_at',
    ];

    /**
     * The typed monetary and review attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'status' => ProgramRewardStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the subscription that earned this reward.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ProgramSubscription::class, 'program_subscription_id');
    }

    /**
     * Get the administrator who reviewed this reward.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
