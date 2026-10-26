<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Client;
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
        $stores = Client::where('active', true)
            ->with('categories')
            ->when($country, fn ($query) => $query->whereHas('countries', fn ($query) => $query->whereKey($country->id)))
            ->orderBy('id')
            ->get();

        return view('website.index', [
            'stores' => $stores,
            'heroStores' => $stores->take(3),
            'offerCategories' => Category::orderBy('id')->get(),
        ]);
    }
}
