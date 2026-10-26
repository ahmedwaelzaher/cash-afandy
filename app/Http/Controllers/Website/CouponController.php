<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class CouponController extends Controller
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

        $coupons = Coupon::available()
            ->with('client.categories')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where($matchTitle)->orWhereHas('client', $matchTitle)))
            ->when($categorySlug !== '', fn (Builder $query) => $query
                ->whereHas('client.categories', fn (Builder $query) => $query->where('slug', $categorySlug)))
            ->when($filters['store'] ?? '', fn (Builder $query, string $slug) => $query
                ->whereHas('client', fn (Builder $query) => $query->where('slug', $slug)));

        match ($filters['sort'] ?? null) {
            'highest' => $coupons->orderByDesc('discount'),
            'expiring' => $coupons->orderByRaw('expiration_date is null')->orderBy('expiration_date'),
            default => $coupons->latest('id'),
        };

        $data = [
            'coupons' => $coupons->paginate(12)->withQueryString(),
            'search' => $search,
            'categorySlug' => $categorySlug,
        ];
        $data['countLabel'] = trans_choice('Offer count', $data['coupons']->total(), ['count' => number_format($data['coupons']->total())]);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('website.coupons.partials.results', $data)->render(),
                'countLabel' => $data['countLabel'],
            ]);
        }

        $categories = Category::orderBy("title->{$locale}")->orderBy('id')->get();
        $categoryCounts = Coupon::available()
            ->join('category_client', 'coupons.client_id', '=', 'category_client.client_id')
            ->selectRaw('category_client.category_id, COUNT(DISTINCT coupons.id) as total')
            ->groupBy('category_client.category_id')->pluck('total', 'category_client.category_id');

        return view('website.coupons.index', array_merge($data, [
            'categories' => $categories,
            'selectedCategory' => $categories->firstWhere('slug', $categorySlug),
            'allOffersCount' => Coupon::available()->count(),
            'categoryCounts' => $categoryCounts,
        ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        abort_unless(Coupon::available()->whereKey($coupon->id)->exists(), 404);

        $coupon->load(['client', 'countries']);

        $relatedCoupons = Coupon::available()
            ->with('client')
            ->where('client_id', $coupon->client_id)
            ->whereKeyNot($coupon->id)
            ->latest('id')
            ->take(4)
            ->get();

        return view('website.coupons.show', [
            'coupon' => $coupon,
            'relatedCoupons' => $relatedCoupons,
        ]);
    }
}
