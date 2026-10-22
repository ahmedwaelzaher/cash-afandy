<?php

namespace App\Http\Controllers\Website;

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
        $heroStores = Client::where('active', true)
            ->when($country, fn ($query) => $query->whereHas('countries', fn ($query) => $query->whereKey($country->id)))
            ->latest('id')
            ->take(3)
            ->get();

        return view('website.index', [
            'heroStores' => $heroStores,
        ]);
    }
}
