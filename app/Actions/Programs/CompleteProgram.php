<?php

namespace App\Actions\Programs;

use App\Enums\ProgramRewardStatus;
use App\Models\Program;
use App\Models\ProgramReward;
use App\Models\ProgramSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompleteProgram
{
    /**
     * Evaluate the selected program action and record one reward for the subscription.
     */
    public function handle(ProgramSubscription $subscription): ?ProgramReward
    {
        return DB::transaction(function () use ($subscription) {
            // Match the owner-first lock order used for enrollment and wallet payments.
            $owner = User::withTrashed()->whereKey($subscription->user_id)->lockForUpdate()->firstOrFail();
            $program = Program::whereKey($subscription->program_id)->lockForUpdate()->firstOrFail();
            $subscription = ProgramSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            $subscription->setRelation('user', $owner)->setRelation('program', $program);

            // Repeated evaluation returns the same earned reward, including pending reviews.
            $reward = $subscription->reward()->first();
            if ($reward) return $reward;
            if (! $subscription->canProgress()) return null;

            // The enum selects the action containing the program's eligibility query.
            $action = app($program->type->action());
            if (! $action->handle($subscription)) return null;

            // Record the amount determined by the program action, independently of its display value.
            $reward = $subscription->reward()->create([
                'amount' => $action->rewardAmount($subscription),
                'currency' => $program->currency,
                'status' => ProgramRewardStatus::Pending,
                'idempotency_key' => (string) Str::uuid(),
            ]);
            if (! $program->requires_approval) app(RecieveProgramReward::class)->handle($reward, true);

            return $reward->refresh();
        }, 3);
    }
}
