<?php

namespace App\Http\Controllers\Website;

use App\Actions\Offers\SaveFavoriteOffer;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class FavoriteOfferController extends Controller
{
    public function index(Request $request)
    {
        $offers = $request->user('users')->favoriteOffers()->published()
            ->withFavoriteState()->with('store.categories')
            ->orderByPivot('created_at', 'desc')->orderByDesc('offers.id')
            ->paginate(12);

        return view('website.favorites.index', [
            'offers' => $offers,
            'countLabel' => trans_choice('Offer count', $offers->total(), ['count' => number_format($offers->total())]),
        ]);
    }

    public function store(Request $request, Offer $offer, SaveFavoriteOffer $save): JsonResponse
    {
        // Published expired offers can be restored from the saved list.
        abort_unless(Offer::published()->whereKey($offer->id)->exists(), 404);
        $user = $request->user('users');

        $save->handle($user, $offer);

        return $this->favoriteResponse($user, true);
    }

    public function destroy(Request $request, Offer $offer): JsonResponse
    {
        $user = $request->user('users');
        $user->favoriteOffers()->detach($offer->id);

        return $this->favoriteResponse($user, false);
    }

    protected function favoriteResponse(User $user, bool $favorited): JsonResponse
    {
        $count = $user->favoriteOffers()->published()->count();

        return response()->json([
            'favorited' => $favorited,
            'countLabel' => trans_choice('Offer count', $count, ['count' => number_format($count)]),
        ]);
    }
}
