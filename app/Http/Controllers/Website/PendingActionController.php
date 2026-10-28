<?php

namespace App\Http\Controllers\Website;

use App\Actions\Auth\PendingAction;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Redot\Http\Controllers\Controller;

class PendingActionController extends Controller
{
    public function store(Request $request, PendingAction $action)
    {
        $data = $request->validate([
            'name' => ['required', Rule::in(['navigate', 'favorite_offer', 'unfavorite_offer'])],
            'offer_id' => ['exclude_if:name,navigate', 'required', 'integer', 'min:1'],
            'return_to' => ['required', 'url', 'max:4096', 'starts_with:' . $request->getSchemeAndHttpHost() . '/'],
        ]);

        if ($data['name'] !== 'navigate') {
            abort_unless(Offer::published()->whereKey($data['offer_id'])->exists(), 404);
        }

        $token = $action->remember($request, $data);
        if ($user = $request->user('users')) {
            $action->complete($request, $user);

            return $request->expectsJson()
                ? response()->json(['authenticated' => true, 'redirect' => $request->session()->pull('url.intended')])
                : redirect()->intended(route('website.index'));
        }

        return $request->expectsJson()
            ? response()->json(['authenticated' => false, 'token' => $token])
            : redirect()->route('website.login');
    }

    public function destroy(Request $request, PendingAction $action)
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $action->cancel($request, $data['token']);

        return response()->noContent();
    }
}
