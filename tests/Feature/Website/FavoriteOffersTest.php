<?php

use App\Models\Category;
use App\Models\Country;
use App\Models\Offer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
    app()->setLocale('en');
    URL::defaults(['locale' => 'en']);
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create(['title' => ['en' => 'Favorite Brand'], 'active' => true]);
    $this->country = Country::where('code', 'eg')->firstOrFail();
    $this->store->countries()->attach($this->country);
    $this->offer = Offer::factory()->create([
        'store_id' => $this->store->id,
        'title' => ['en' => 'Saved favorite offer', 'ar' => 'عرض محفوظ في المفضلة'],
        'active' => true,
        'launch_date' => now()->subDay(),
        'expiration_date' => null,
    ]);
    $this->offer->countries()->attach($this->country);
});

test('only website users can access and change their favorite offers', function () {
    $this->get(route('website.favorites.index'))->assertRedirect(route('website.login'));
    $this->putJson(route('website.favorites.store', $this->offer))->assertUnauthorized();
    $this->deleteJson(route('website.favorites.destroy', $this->offer))->assertUnauthorized();
    $this->assertDatabaseCount('offer_user', 0);
});

test('saving removing and restoring favorites is idempotent and scoped to the authenticated user', function () {
    $other = User::factory()->create();
    $other->favoriteOffers()->attach($this->offer);
    $this->actingAs($this->user, 'users');
    $url = route('website.favorites.store', $this->offer);

    foreach (range(1, 2) as $attempt) {
        $this->putJson($url, ['user_id' => $other->id])->assertOk()
            ->assertJsonPath('favorited', true)->assertJsonPath('countLabel', '1 Offer');
    }
    $this->assertDatabaseCount('offer_user', 2);
    foreach (range(1, 2) as $attempt) {
        $this->deleteJson($url)->assertOk()->assertJsonPath('favorited', false)->assertJsonPath('countLabel', '0 Offers');
    }
    $this->assertDatabaseMissing('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
    $this->assertDatabaseHas('offer_user', ['user_id' => $other->id, 'offer_id' => $this->offer->id]);
    $this->putJson($url)->assertOk()->assertJsonPath('favorited', true);
    expect($this->user->favoriteOffers()->first()->is($this->offer))->toBeTrue();
    expect($this->offer->favoritedBy()->count())->toBe(2);
});

test('favorite lists show only the current users saved offers and include their restoration controls', function (string $locale) {
    $other = User::factory()->create();
    $other->favoriteOffers()->attach($this->offer);
    $this->actingAs($this->user, 'users')->get(route('website.favorites.index', ['locale' => $locale]))
        ->assertOk()->assertViewHas('offers', fn ($offers) => $offers->isEmpty());

    $this->user->favoriteOffers()->attach($this->offer);
    $this->get(route('website.favorites.index', ['locale' => $locale]))
        ->assertOk()->assertSee($this->offer->getTranslation('title', $locale))
        ->assertSee('data-favorite-item="' . $this->offer->id . '"', false)
        ->assertSee('data-add-success="' . e(__('Added to favorites successfully')) . '"', false)
        ->assertSee('data-remove-success="' . e(__('Removed from favorites successfully')) . '"', false)
        ->assertSee('data-favorite-removed hidden', false)
        ->assertSee('data-countdown-seconds="10"', false)
        ->assertSee('role="timer" aria-live="off"', false)
        ->assertSee('btn btn-sm btn-outline-primary', false)
        ->assertDontSee('data-favorite-count', false)
        ->assertViewHas('offers', fn ($offers) => $offers->first()->is_favorited === true);
})->with(['ar', 'en']);

test('expired favorites remain visible without redemption actions and can be removed and restored', function () {
    $this->offer->update(['expiration_date' => now()->subMinute()]);
    $this->user->favoriteOffers()->attach($this->offer);
    $this->actingAs($this->user, 'users')->get(route('website.favorites.index', ['locale' => 'en']))
        ->assertOk()->assertSee('This offer has expired')->assertDontSee('data-offer-code=', false);

    $this->deleteJson(route('website.favorites.destroy', $this->offer))->assertOk()->assertJsonPath('favorited', false);
    $this->putJson(route('website.favorites.store', $this->offer))->assertOk()->assertJsonPath('favorited', true);
    $this->get(route('website.favorites.index'))->assertViewHas('offers', fn ($offers) => $offers->total() === 1);
});

test('unpublished offers cannot be added or exposed through favorites', function (string $state) {
    if ($state === 'future') $this->offer->update(['launch_date' => now()->addDay()]);
    if ($state === 'inactive') $this->offer->update(['active' => false]);
    if ($state === 'store') $this->store->update(['active' => false]);

    $this->actingAs($this->user, 'users')->putJson(route('website.favorites.store', $this->offer))->assertNotFound();
    $this->user->favoriteOffers()->attach($this->offer);
    $this->get(route('website.favorites.index'))->assertOk()
        ->assertViewHas('offers', fn ($offers) => $offers->isEmpty())->assertDontSee('Saved favorite offer');
    $this->deleteJson(route('website.favorites.destroy', $this->offer))->assertOk();
})->with(['future', 'inactive', 'store']);

test('favorite states are included in all public offer card queries including ajax search results', function () {
    $category = Category::factory()->create();
    $this->store->categories()->attach($category);
    $this->user->favoriteOffers()->attach($this->offer);
    $this->actingAs($this->user, 'users');
    $paths = [
        route('website.index'),
        route('website.offers.index'),
        route('website.offers.show', $this->offer),
        route('website.stores.show', $this->store),
        route('website.categories.show', $category),
        route('website.search', ['afandy_search' => 'Saved', 'afandy_category' => 'offers']),
    ];

    foreach ($paths as $url) {
        $response = $this->get($url)->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        expect($xpath->query('//button[@data-offer-favorite="' . $this->offer->id . '" and @aria-pressed="true"]'))->toHaveCount(1);
    }
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')->withHeader('X-Search-Results', '1')
        ->getJson(end($paths))->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, 'aria-pressed="true"'));

    $this->flushHeaders();
    $this->actingAs(User::factory()->create(), 'users');
    $this->get(route('website.offers.index'))->assertViewHas('offers', fn ($offers) => $offers->first()->is_favorited === false);
});

test('favorite state is loaded in one query and saved offers paginate across markets', function () {
    $otherCountry = Country::where('code', 'sa')->firstOrFail();
    foreach (range(1, 13) as $number) {
        $offer = Offer::factory()->create([
            'store_id' => $this->store->id, 'active' => true,
            'launch_date' => now()->subDay(), 'expiration_date' => null,
        ]);
        $offer->countries()->attach($otherCountry);
        $this->user->favoriteOffers()->attach($offer);
    }
    $this->actingAs($this->user, 'users');
    DB::enableQueryLog();
    $this->get(route('website.favorites.index'))->assertOk()
        ->assertViewHas('offers', fn ($offers) => $offers->total() === 13 && $offers->count() === 12);
    $stateQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'offer_user'));
    DB::disableQueryLog();
    expect($stateQueries)->toHaveCount(2);
    $this->get(route('website.favorites.index', ['page' => 2]))->assertOk()
        ->assertViewHas('offers', fn ($offers) => $offers->count() === 1);
});

test('deleted offers and permanently deleted users do not leave favorite links behind', function () {
    $this->user->favoriteOffers()->attach($this->offer);
    $this->offer->delete();
    $this->assertDatabaseCount('offer_user', 0);

    $offer = Offer::factory()->create(['store_id' => $this->store->id]);
    $this->user->favoriteOffers()->attach($offer);
    $this->user->forceDelete();
    $this->assertDatabaseCount('offer_user', 0);
});
