<?php

use App\Models\Category;
use App\Models\Country;
use App\Models\Store;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
});

test('renamed store routes preserve public urls and slug binding', function (string $locale) {
    $store = Store::factory()->create(['slug' => 'same-store-slug', 'active' => true]);
    $store->countries()->attach(Country::where('code', 'eg')->firstOrFail());
    $url = route('website.stores.show', ['locale' => $locale, 'store' => $store]);

    expect(parse_url($url, PHP_URL_PATH))->toBe('/' . $locale . '/stores/same-store-slug');
    $this->get($url)->assertOk()->assertViewHas('store', fn ($entry) => $entry->is($store));
})->with(['ar', 'en']);

test('store category counts and ajax results use the renamed pivot relations', function () {
    $category = Category::factory()->create();
    $country = Country::where('code', 'eg')->firstOrFail();
    $store = Store::factory()->create(['title' => ['en' => 'Renamed directory store'], 'active' => true]);
    $store->categories()->attach($category);
    $store->countries()->attach($country);
    $inactive = Store::factory()->create(['active' => false]);
    $inactive->categories()->attach($category);
    $inactive->countries()->attach($country);

    $this->get(route('website.stores.index', ['locale' => 'en', 'category' => $category->slug]))
        ->assertOk()->assertViewHas('categories', fn ($items) => $items->firstWhere('id', $category->id)->stores_count === 1);
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.stores.index', ['locale' => 'en', 'category' => $category->slug, 'search' => 'renamed']))
        ->assertOk()->assertJsonPath('countLabel', '1 Store')
        ->assertJsonPath('html', fn ($html) => str_contains($html, route('website.stores.show', $store)));
});
