<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Category;
use App\Models\Coupon;
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

        $stores = $category->clients()
            ->where('active', true)
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)))
            ->latest('id')
            ->get();

        return view('website.categories.show', [
            'category' => $category,
            'stores' => $stores,
            'coupons' => Coupon::available()->with('client')->whereHas('client.categories', $inCategory)->latest('id')->take(8)->get(),
            'cashbacks' => Cashback::available()->with('client')->whereHas('client.categories', $inCategory)->latest('id')->take(8)->get(),
        ]);
    }
}
