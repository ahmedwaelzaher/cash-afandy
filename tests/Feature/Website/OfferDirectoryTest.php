<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Country;
use App\Models\Coupon;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
});

test('the offers page shares the directory layout and includes the reveal modal even when empty', function () {
    $this->get(route('website.coupons.index', ['locale' => 'en', 'search' => 'missing-offer-7z']))
        ->assertOk()
        ->assertSee('data-directory-search', false)
        ->assertSee('data-directory-results', false)
        ->assertSee('id="site-coupon-reveal"', false)
        ->assertDontSee('data-directory-search disable-validation', false);
});

test('ajax offer search matches store names without case sensitivity and respects category and availability', function () {
    $category = Category::factory()->create();
    $store = Client::factory()->create(['title' => ['en' => 'Example Brand'], 'active' => true]);
    $store->categories()->attach($category);
    $country = Country::where('code', 'eg')->firstOrFail();
    $offer = Coupon::factory()->create([
        'client_id' => $store->id,
        'title' => ['en' => 'Eligible directory offer'],
        'launch_date' => now()->subDay(),
        'expiration_date' => null,
        'active' => true,
    ]);
    $offer->countries()->attach($country);
    $expired = Coupon::factory()->create([
        'client_id' => $store->id,
        'title' => ['en' => 'Expired directory offer'],
        'launch_date' => now()->subDays(2),
        'expiration_date' => now()->subDay(),
        'active' => true,
    ]);
    $expired->countries()->attach($country);

    $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.coupons.index', ['locale' => 'en', 'search' => 'example brand', 'category' => $category->slug]))
        ->assertOk()->assertJsonStructure(['html', 'countLabel']);

    expect($response->json('html'))->toContain('Eligible directory offer')
        ->not->toContain('Expired directory offer', 'site-navbar');
    expect($response->json('countLabel'))->toBe('1 Offer');
});

test('ajax offer search validates the search length', function () {
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.coupons.index', ['locale' => 'en', 'search' => str_repeat('a', 256)]))
        ->assertUnprocessable();
});

test('the homepage shows available database offers instead of preview offers', function () {
    $store = Client::factory()->create(['active' => true]);
    $country = Country::where('code', 'eg')->firstOrFail();
    $store->countries()->attach($country);
    $offer = Coupon::factory()->create([
        'client_id' => $store->id,
        'title' => ['en' => 'Live homepage offer'],
        'launch_date' => now()->subDay(),
        'expiration_date' => null,
        'active' => true,
    ]);
    $offer->countries()->attach($country);

    $this->get(route('website.index', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Live homepage offer')
        ->assertDontSee('AFANDY01')
        ->assertDontSee('cashafandy-demo');
});

test('a direct offer card uses its own url when there is no code', function () {
    $offer = new Coupon([
        'title' => ['en' => 'Direct offer'],
        'code' => null,
        'discount' => 20,
    ]);
    $offer->url = 'https://example.test/deal?affiliate=partner';
    $offer->setRelation('client', Client::factory()->create());

    $html = view('website.partials.coupon-card', ['coupon' => $offer])->render();

    expect($html)->toContain('href="https://example.test/deal?affiliate=partner"')
        ->not->toContain('data-coupon-code=');
});
