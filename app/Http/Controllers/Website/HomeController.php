<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Redot\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Show the website.
     *
     * @return View
     */
    public function __invoke()
    {
        if (request()->has('verified')) {
            toastify()->success(__('Your email address has been verified.'));
        }

        $country = website_country();
        $stores = Store::where('active', true)
            ->with('categories')
            ->when($country, fn ($query) => $query->whereHas('countries', fn ($query) => $query->whereKey($country->id)))
            ->orderBy('id')
            ->get();

        return view('website.index', [
            'stores' => $stores,
            'heroStores' => $stores->take(3),
            'offers' => Offer::available()->withFavoriteState()->with('store.categories')->latest('id')->take(20)->get(),
            'categoryStoreCounts' => $stores->flatMap->categories->countBy('slug'),
            'offerCategories' => Category::orderBy('id')->get(),
        ]);
    }
}
