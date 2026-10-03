<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Client;
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
        $locale = app()->getLocale();
        $search = $request->string('search')->trim()->toString();

        $coupons = Coupon::available()
            ->with('client')
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where("title->{$locale}", 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $query) => $query->where("title->{$locale}", 'like', "%{$search}%"))))
            ->when($request->string('category')->toString(), fn (Builder $query, string $slug) => $query
                ->whereHas('client.categories', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($request->string('store')->toString(), fn (Builder $query, string $slug) => $query
                ->whereHas('client', fn (Builder $query) => $query->where('slug', $slug)));

        match ($request->query('sort')) {
            'highest' => $coupons->orderByDesc('discount'),
            'expiring' => $coupons->orderByRaw('expiration_date is null')->orderBy('expiration_date'),
            default => $coupons->latest('id'),
        };

        return view('website.coupons.index', [
            'coupons' => $coupons->paginate(12)->withQueryString(),
            'categories' => Category::all(),
            'stores' => Client::where('active', true)->get(),
        ]);
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
