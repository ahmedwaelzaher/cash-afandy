<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Client;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class SearchController extends Controller
{
    /**
     * Search stores, coupons and cashbacks.
     */
    public function __invoke(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        if ($search === '') {
            return redirect()->route('website.index');
        }

        $locale = app()->getLocale();
        $country = website_country();
        $titleMatches = fn (Builder $query) => $query->where("title->{$locale}", 'like', "%{$search}%");

        $stores = Client::where('active', true)
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)))
            ->where($titleMatches)
            ->latest('id')
            ->take(8)
            ->get();

        $coupons = Coupon::available()
            ->with('client')
            ->where(fn (Builder $query) => $query->where($titleMatches)->orWhereHas('client', $titleMatches))
            ->latest('id')
            ->take(8)
            ->get();

        $cashbacks = Cashback::available()
            ->with('client')
            ->whereHas('client', $titleMatches)
            ->latest('id')
            ->take(8)
            ->get();

        return view('website.search.index', [
            'search' => $search,
            'stores' => $stores,
            'coupons' => $coupons,
            'cashbacks' => $cashbacks,
        ]);
    }
}
