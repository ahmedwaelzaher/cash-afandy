<?php

use App\Models\Client;
use App\Models\Country;
use App\Models\Offer;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
    $this->country = Country::where('code', 'eg')->firstOrFail();
});

test('header search uses its own key and renders localized escaped grouped suggestions', function (string $locale) {
    $store = Client::factory()->create(['title' => ['en' => 'Afandy Brand', 'ar' => 'متجر أفندي'], 'logo' => '']);
    $store->countries()->attach($this->country);
    $offer = Offer::factory()->create([
        'client_id' => $store->id,
        'title' => ['en' => 'Afandy <script>alert(1)</script>', 'ar' => 'عرض أفندي'],
        'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
    ]);
    $offer->countries()->attach($this->country);

    $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')->getJson(route('website.search', [
        'locale' => $locale, 'afandy_search' => 'AFANDY', 'search' => 'unrelated-directory-term',
    ]))->assertOk()->assertJsonStructure(['html']);
    $html = $response->json('html');

    expect($html)->toContain('afandy-search-stores', 'afandy-search-offers', 'ti-arrow-left',
        route('website.clients.show', $store), route('website.offers.show', $offer),
        e($offer->getTranslation('title', $locale)), 'afandy_search=AFANDY')
        ->not->toContain('<script>', 'site-navbar', 'Cashback');
})->with(['ar', 'en']);

test('search ranks exact store names first and caps suggestions at two stores and four offers', function () {
    $exact = Client::factory()->create(['title' => ['en' => 'Needle']]);
    $exact->countries()->attach($this->country);
    foreach (range(1, 3) as $number) {
        $store = Client::factory()->create(['title' => ['en' => "Needle Store {$number}"]]);
        $store->countries()->attach($this->country);
    }
    foreach (range(1, 5) as $number) {
        $offer = Offer::factory()->create([
            'client_id' => $exact->id, 'title' => ['en' => "Sale {$number}"],
            'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ]);
        $offer->countries()->attach($this->country);
    }
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'needle']))
        ->assertOk()->json('html');

    expect(substr_count($html, 'class="site-search-result '))->toBe(6);
    expect(strpos($html, 'afandy-search-stores'))->toBeLessThan(strpos($html, 'afandy-search-offers'));
    expect(strpos($html, '>' . $exact->title . '</strong>'))->toBeLessThan(strpos($html, '>Needle Store'));
});

test('an exact offer title precedes stores that only contain the term', function () {
    $store = Client::factory()->create(['title' => ['en' => 'My Laptop Store']]);
    $store->countries()->attach($this->country);
    $offer = Offer::factory()->create([
        'client_id' => $store->id, 'title' => ['en' => 'Laptop'],
        'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
    ]);
    $offer->countries()->attach($this->country);
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'laptop']))
        ->assertOk()->json('html');

    expect(strpos($html, 'afandy-search-offers'))->toBeLessThan(strpos($html, 'afandy-search-stores'));
});

test('header search excludes unavailable stores and offers in the current market', function () {
    $visible = Client::factory()->create(['title' => ['en' => 'Searchable Visible']]);
    $visible->countries()->attach($this->country);
    $inactive = Client::factory()->create(['title' => ['en' => 'Searchable Inactive'], 'active' => false]);
    $inactive->countries()->attach($this->country);
    $foreign = Client::factory()->create(['title' => ['en' => 'Searchable Foreign']]);
    $foreign->countries()->attach(Country::where('code', 'sa')->firstOrFail());
    foreach ([
        ['label' => 'Expired', 'client_id' => $visible->id, 'expiration_date' => now()->subHour()],
        ['label' => 'Future', 'client_id' => $visible->id, 'launch_date' => now()->addDay()],
        ['label' => 'Inactive offer', 'client_id' => $visible->id, 'active' => false],
        ['label' => 'Inactive owner', 'client_id' => $inactive->id],
        ['label' => 'Foreign owner', 'client_id' => $foreign->id],
        ['label' => 'Foreign offer', 'client_id' => $visible->id, 'foreign' => true],
        ['label' => 'Visible offer', 'client_id' => $visible->id],
    ] as $attributes) {
        $offer = Offer::factory()->create(array_merge([
            'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ], array_diff_key($attributes, array_flip(['label', 'foreign'])), ['title' => ['en' => 'Searchable ' . $attributes['label']]]));
        $offer->countries()->attach(($attributes['foreign'] ?? false) ? Country::where('code', 'sa')->firstOrFail() : $this->country);
    }
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'searchable']))
        ->assertOk()->json('html');

    expect($html)->toContain('Searchable Visible', 'Searchable Visible offer')
        ->not->toContain('Searchable Expired', 'Searchable Future', 'Searchable Inactive', 'Searchable Foreign');
});

test('empty short and directory-only ajax searches return no suggestions', function (array $query) {
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', ...$query]))
        ->assertOk()->assertExactJson(['html' => '']);
})->with([[[]], [['afandy_search' => ' ']], [['afandy_search' => 'a']], [['search' => 'Amazon']]]);

test('header search validates length and scalar input', function (mixed $query) {
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => $query]))
        ->assertUnprocessable();
})->with([str_repeat('a', 256), [['invalid']]]);

test('search treats percent and underscore as literal characters', function () {
    $store = Client::factory()->create(['title' => ['en' => 'Unique 20%_ savings']]);
    $store->countries()->attach($this->country);
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => '20%_']))
        ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, 'Unique 20%_ savings'));
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'missing%_']))
        ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, 'No results found') && ! str_contains($html, 'afandy-search-stores'));
});

test('full results paginate beyond the dropdown and old links still work', function () {
    foreach (range(1, 13) as $number) {
        $store = Client::factory()->create(['title' => ['en' => "Pagination Brand {$number}"]]);
        $store->countries()->attach($this->country);
    }
    $this->get(route('website.search', ['locale' => 'en', 'afandy_search' => 'Pagination Brand']))
        ->assertOk()->assertViewHas('stores', fn ($stores) => $stores->total() === 13 && $stores->count() === 12)
        ->assertSee('stores_page=2', false)->assertSee('name="afandy_search"', false);
    $this->get(route('website.search', ['locale' => 'en', 'search' => 'Pagination Brand']))
        ->assertOk()->assertViewHas('search', 'Pagination Brand');
});

test('search binds SQL syntax as literal text instead of widening the results', function () {
    $store = Client::factory()->create(['title' => ['en' => 'Unrelated visible store']]);
    $store->countries()->attach($this->country);
    $offer = Offer::factory()->create([
        'client_id' => $store->id, 'title' => ['en' => 'Unrelated visible offer'],
        'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
    ]);
    $offer->countries()->attach($this->country);
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => "' OR 1=1 --"]))
        ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, 'No results found') && ! str_contains($html, 'Unrelated visible'));
});

test('LIKE escape characters and quotes remain searchable literal text', function (string $search) {
    $store = Client::factory()->create(['title' => ['en' => 'Literal ' . $search . ' store']]);
    $store->countries()->attach($this->country);
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => $search]))
        ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, e('Literal ' . $search . ' store')));
})->with(['20!%_', "O'Reilly", 'path\\name']);
