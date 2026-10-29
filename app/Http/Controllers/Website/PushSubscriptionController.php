<?php

namespace App\Http\Controllers\Website;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Redot\Http\Controllers\Controller;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(config('redot.notifications.push_enabled', false), 404);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $owner = $request->user('users');
        $subscription = new PushSubscription([
            'token' => $validated['token'],
            'last_seen_at' => now(),
            'disabled_at' => null,
        ]);
        $subscription->notifiable()->associate($owner);

        DB::transaction(function () use ($request, $owner, $subscription) {
            $previous = $request->session()->get('push_subscription_token_hash');
            if ($previous !== $subscription->token_hash) {
                PushSubscription::disableFor($owner, $previous);
            }

            // Atomic registration also transfers a shared browser after an account switch.
            PushSubscription::upsert([$subscription->getAttributes()], ['token_hash'], [
                'notifiable_type', 'notifiable_id', 'token', 'last_seen_at', 'disabled_at', 'updated_at',
            ]);
        });

        $request->session()->put('push_subscription_token_hash', $subscription->token_hash);

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        PushSubscription::disableFor(
            $request->user('users'),
            $request->session()->pull('push_subscription_token_hash'),
        );

        return response()->noContent();
    }
}
