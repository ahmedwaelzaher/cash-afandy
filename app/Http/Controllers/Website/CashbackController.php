<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Category;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class CashbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $locale = app()->getLocale();
        $search = $request->string('search')->trim()->toString();

        $cashbacks = Cashback::available()
            ->with('client')
            ->when($search, fn (Builder $query) => $query
                ->whereHas('client', fn (Builder $query) => $query->where("title->{$locale}", 'like', "%{$search}%")))
            ->when($request->string('category')->toString(), fn (Builder $query, string $slug) => $query
                ->whereHas('client.categories', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($request->string('store')->toString(), fn (Builder $query, string $slug) => $query
                ->whereHas('client', fn (Builder $query) => $query->where('slug', $slug)));

        match ($request->query('sort')) {
            'highest' => $cashbacks->orderByDesc('percentage'),
            'expiring' => $cashbacks->orderByRaw('expiration_date is null')->orderBy('expiration_date'),
            default => $cashbacks->latest('id'),
        };

        return view('website.cashbacks.index', [
            'cashbacks' => $cashbacks->paginate(12)->withQueryString(),
            'categories' => Category::all(),
            'stores' => Client::where('active', true)->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cashback $cashback)
    {
        abort_unless(Cashback::available()->whereKey($cashback->id)->exists(), 404);

        $cashback->load(['client.categories', 'country']);

        $relatedCashbacks = Cashback::available()
            ->with('client')
            ->whereKeyNot($cashback->id)
            ->whereHas('client.categories', fn (Builder $query) => $query
                ->whereIn('categories.id', $cashback->client->categories->modelKeys()))
            ->latest('id')
            ->take(4)
            ->get();

        return view('website.cashbacks.show', [
            'cashback' => $cashback,
            'relatedCashbacks' => $relatedCashbacks,
        ]);
    }
}
