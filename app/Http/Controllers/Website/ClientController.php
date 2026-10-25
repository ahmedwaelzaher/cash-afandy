<?php

namespace App\Http\Controllers\Website;

use App\Models\Category;
use App\Models\Client;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class ClientController extends Controller
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
        $categories = Category::orderBy("title->{$locale}")->orderBy('id')->get();

        $stores = Client::where('active', true)
            ->with('categories')
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)))
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas('categories', fn (Builder $query) => $query->where('slug', $categorySlug)))
            ->when($search !== '', fn (Builder $query) => $query->where("title->{$locale}", 'like', "%{$search}%"))
            ->orderBy("title->{$locale}")->orderBy('id')
            ->paginate(12)->withQueryString();

        return view('website.clients.index', [
            'stores' => $stores,
            'categories' => $categories,
            'selectedCategory' => $categories->firstWhere('slug', $categorySlug),
            'categorySlug' => $categorySlug,
            'search' => $search,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $country = website_country();

        abort_unless($client->active, 404);
        abort_if($country && ! $client->countries()->whereKey($country->id)->exists(), 404);

        $client->load('categories');

        return view('website.clients.show', [
            'client' => $client,
            'coupons' => Coupon::available()->with('client')->whereBelongsTo($client)->latest('id')->get(),
        ]);
    }
}
