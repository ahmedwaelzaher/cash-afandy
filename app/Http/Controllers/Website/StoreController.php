<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class StoreController extends Controller
{
    /**
     * Browse the active stores in the selected shopping market.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($filters['search'] ?? '');
        $categorySlug = $filters['category'] ?? '';
        $country = website_country();
        $locale = app()->getLocale();
        $availableStores = fn (Builder $query) => $query->where('active', true)
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)));
        $stores = $availableStores(Store::query())
            ->with('categories')
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas('categories', fn (Builder $query) => $query->where('slug', $categorySlug)))
            ->when($search !== '', function (Builder $query) use ($search, $locale) {
                $title = $query->getQuery()->getGrammar()->wrap("title->{$locale}");

                return $query->whereRaw("LOWER({$title}) LIKE ?", ['%' . mb_strtolower($search) . '%']);
            })
            ->orderBy("title->{$locale}")->orderBy('id')
            ->paginate(12)->withQueryString();

        $data = [
            'stores' => $stores,
            'categorySlug' => $categorySlug,
            'search' => $search,
            'countLabel' => trans_choice('Store count', $stores->total(), ['count' => number_format($stores->total())]),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('website.stores.partials.results', $data)->render(),
                'countLabel' => $data['countLabel'],
                'category' => $categorySlug,
                'categoryLabel' => Category::where('slug', $categorySlug)->first(['title'])?->title ?? __('All stores'),
            ]);
        }

        $categories = Category::withCount(['stores' => $availableStores])
            ->orderBy("title->{$locale}")->orderBy('id')->get();

        return view('website.stores.index', array_merge($data, [
            'categories' => $categories,
            'allStoresCount' => $availableStores(Store::query())->count(),
            'selectedCategory' => $categories->firstWhere('slug', $categorySlug),
        ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $country = website_country();

        abort_unless($store->active, 404);
        abort_if($country && ! $store->countries()->whereKey($country->id)->exists(), 404);

        $store->load('categories');

        return view('website.stores.show', [
            'store' => $store,
            'offers' => Offer::available()->withFavoriteState()->with('store')->whereBelongsTo($store)->latest('id')->get(),
        ]);
    }
}
