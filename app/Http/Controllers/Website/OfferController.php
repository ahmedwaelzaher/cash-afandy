<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'store' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:latest,highest,expiring'],
        ]);
        $locale = app()->getLocale();
        $search = trim($filters['search'] ?? '');
        $categorySlug = $filters['category'] ?? '';
        $matchTitle = function (Builder $query) use ($locale, $search) {
            $title = $query->getQuery()->getGrammar()->wrap("title->{$locale}");

            return $query->whereRaw("LOWER({$title}) LIKE ?", ['%' . mb_strtolower($search) . '%']);
        };

        $offers = Offer::available()
            ->with('store.categories')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where($matchTitle)->orWhereHas('store', $matchTitle)))
            ->when($categorySlug !== '', fn (Builder $query) => $query
                ->whereHas('store.categories', fn (Builder $query) => $query->where('slug', $categorySlug)))
            ->when($filters['store'] ?? '', fn (Builder $query, string $slug) => $query
                ->whereHas('store', fn (Builder $query) => $query->where('slug', $slug)));

        match ($filters['sort'] ?? null) {
            'highest' => $offers->orderByDesc('discount'),
            'expiring' => $offers->orderByRaw('expiration_date is null')->orderBy('expiration_date'),
            default => $offers->latest('id'),
        };

        $data = [
            'offers' => $offers->paginate(12)->withQueryString(),
            'search' => $search,
            'categorySlug' => $categorySlug,
        ];
        $data['countLabel'] = trans_choice('Offer count', $data['offers']->total(), ['count' => number_format($data['offers']->total())]);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('website.offers.partials.results', $data)->render(),
                'countLabel' => $data['countLabel'],
                'category' => $categorySlug,
                'categoryLabel' => Category::where('slug', $categorySlug)->first(['title'])?->title ?? __('Offers'),
            ]);
        }

        $categories = Category::orderBy("title->{$locale}")->orderBy('id')->get();
        $categoryCounts = Offer::available()
            ->join('category_store', 'offers.store_id', '=', 'category_store.store_id')
            ->selectRaw('category_store.category_id, COUNT(DISTINCT offers.id) as total')
            ->groupBy('category_store.category_id')->pluck('total', 'category_store.category_id');

        return view('website.offers.index', array_merge($data, [
            'categories' => $categories,
            'selectedCategory' => $categories->firstWhere('slug', $categorySlug),
            'allOffersCount' => Offer::available()->count(),
            'categoryCounts' => $categoryCounts,
        ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Offer $offer)
    {
        abort_unless(Offer::available()->whereKey($offer->id)->exists(), 404);

        $offer->load(['store', 'countries']);

        $relatedOffers = Offer::available()
            ->with('store')
            ->where('store_id', $offer->store_id)
            ->whereKeyNot($offer->id)
            ->latest('id')
            ->take(4)
            ->get();

        return view('website.offers.show', [
            'offer' => $offer,
            'relatedOffers' => $relatedOffers,
        ]);
    }
}
