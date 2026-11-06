<?php

namespace App\Models;

use App\Enums\NotificationLevel;
use App\Enums\ProgramRewardStatus;
use App\Notifications\DashboardNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

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
     * Notify administrators of pending reviews and successful automatic payments.
     */
    protected static function booted(): void
    {
        // Automatic rewards only produce a notification after payment succeeds.
        static::created(function (self $reward) {
            if ($reward->subscription->program->requires_approval && $reward->status === ProgramRewardStatus::Pending) {
                $reward->notifyAdministrators();
            }
        });

        // Manual reviews and repeated updates do not produce automatic-payment notices.
        static::updated(function (self $reward) {
            if ($reward->wasChanged('status') && $reward->status === ProgramRewardStatus::Paid
                && ! $reward->subscription->program->requires_approval && $reward->admin_id === null) {
                $reward->notifyAdministrators();
            }
        });
    }

    /**
     * Send the reward's current outcome through ordinary dashboard notifications.
     */
    protected function notifyAdministrators(): void
    {
        $details = [
            'user' => $this->subscription->user->full_name,
            'program' => $this->subscription->program->title,
        ];

        Notification::send(Admin::active()->get()->filter(fn (Admin $admin) => $admin->can('dashboard.program-rewards.show')), DashboardNotification::make(
            title: $this->status === ProgramRewardStatus::Pending
                ? __(':user earned a reward in :program awaiting approval.', $details)
                : __(':user received an automatic reward in :program.', $details),
            url: route('dashboard.program-rewards.show', $this),
            level: $this->status === ProgramRewardStatus::Pending ? NotificationLevel::Warning : NotificationLevel::Success,
            data: ['program_reward_id' => $this->id],
        ));
    }

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
