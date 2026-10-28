<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Redot\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $country = website_country();
        $inCategory = fn (Builder $query) => $query->whereKey($category->id);

        $stores = $category->stores()
            ->where('active', true)
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)))
            ->latest('id')
            ->get();

        return view('website.categories.show', [
            'category' => $category,
            'stores' => $stores,
            'offers' => Offer::available()->withFavoriteState()->with('store')->whereHas('store.categories', $inCategory)->latest('id')->take(8)->get(),
            'cashbacks' => Cashback::available()->with('store')->whereHas('store.categories', $inCategory)->latest('id')->take(8)->get(),
        ]);
    }
}
