<?php

namespace App\Actions\Programs;

use App\Models\Program;
use App\Models\ProgramSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscribeToProgram
{
    /**
     * Reuse an open subscription or start an eligible participation attempt within its limit.
     */
    public function handle(User $user, Program $program): ProgramSubscription
    {
        return DB::transaction(function () use ($user, $program) {
            // Serialize subscription creation with wallet and eligibility changes.
            $owner = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $program = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();

            if (! $program->isAvailable() || ! $owner->hasCompletedProfile()
                || $owner->country_id !== $program->country_id || $owner->wallet->currency !== $program->currency) {
                throw ValidationException::withMessages(['program' => __('This program is not available for your account.')]);
            }

            // Repeated submissions return the original subscription.
            $subscription = $program->subscriptions()->where('user_id', $owner->id)->whereNull('completed_at')->first();
            if ($subscription) return $subscription;

            // Count every participation attempt before permitting an explicit new one.
            if ($program->subscriptions()->where('user_id', $owner->id)->count() >= $program->max_subscriptions) {
                throw ValidationException::withMessages(['program' => __('You have reached the subscription limit for this program.')]);
            }

            return $program->subscriptions()->create([
                'user_id' => $owner->id,
                'reward_amount' => $program->reward_amount,
            ]);
        }, 3);
    }
}
