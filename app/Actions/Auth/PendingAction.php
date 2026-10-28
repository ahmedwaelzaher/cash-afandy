<?php

namespace App\Actions\Auth;

use App\Actions\Offers\SaveFavoriteOffer;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PendingAction
{
    public function remember(Request $request, array $data): string
    {
        $token = (string) Str::uuid();
        $request->session()->put('auth.pending_action', [
            ...$data,
            'token' => $token,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return $token;
    }

    public function complete(Request $request, User $user): void
    {
        $action = $request->session()->get('auth.pending_action');
        if (! is_array($action)) return;

        if (($action['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('auth.pending_action');

            return;
        }

        if (($action['name'] ?? null) !== 'navigate'
            && ! filter_var($action['offer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $request->session()->forget('auth.pending_action');

            return;
        }

        switch ($action['name'] ?? null) {
            case 'favorite_offer':
                if ($offer = Offer::published()->find($action['offer_id'] ?? null)) {
                    app(SaveFavoriteOffer::class)->handle($user, $offer);
                }
            break;

            case 'unfavorite_offer':
                $user->favoriteOffers()->detach($action['offer_id'] ?? null);
            break;

            case 'navigate':break;

            default:
                $request->session()->forget('auth.pending_action');
                return;
        }

        $request->session()->put('url.intended', $action['return_to']);
        $request->session()->forget('auth.pending_action');
    }

    public function cancel(Request $request, string $token): void
    {
        if ($request->session()->get('auth.pending_action.token') === $token) {
            $request->session()->forget('auth.pending_action');
        }
    }
}
