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

test('search ranks exact store names first', function () {
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

    expect(substr_count($html, 'class="site-search-result '))->toBe(9);
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

describe('search result types', function () {
    beforeEach(function () {
        $this->store = Client::factory()->create(['title' => ['en' => 'Typed Brand', 'ar' => 'متجر الأنواع']]);
        $this->store->countries()->attach($this->country);
        $this->offer = Offer::factory()->create([
            'client_id' => $this->store->id, 'title' => ['en' => 'Typed offer', 'ar' => 'عرض الأنواع'],
            'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ]);
        $this->offer->countries()->attach($this->country);
    });

    test('the selected type is active and its results are the only visible group', function (string $type, string $locale) {
        $otherType = $type === 'stores' ? 'offers' : 'stores';
        $response = $this->get(route('website.search', [
            'locale' => $locale, 'afandy_search' => 'Typed', 'afandy_category' => $type,
        ]))->assertOk()->assertViewHas('category', $type)->assertViewHas('counts', ['stores' => 1, 'offers' => 1])
            ->assertViewHas($otherType, fn ($items) => $items->isEmpty())
            ->assertSee('data-search-result-type="' . $type . '"', false)
            ->assertDontSee('data-search-result-type="' . $otherType . '"', false)
            ->assertDontSee('data-afandy-category="all"', false);
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
        $xpath = new DOMXPath($document);

        expect($xpath->query('//*[@data-afandy-category]'))->toHaveCount(2);
        expect($xpath->query('//*[@data-afandy-category="' . $type . '" and @aria-current="page" and contains(@class, "active")]'))->toHaveCount(1);
        expect($xpath->query('//*[@data-afandy-category="' . $otherType . '" and @aria-current]'))->toHaveCount(0);
        expect($xpath->query('//header[h1[@id="search-results-title"]]/following-sibling::div[contains(@class,"row")]/aside'))->toHaveCount(1);
        foreach ($xpath->query('//*[@data-afandy-category]') as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            expect($query['afandy_search'])->toBe('Typed');
            expect($query['afandy_category'])->toBe($link->getAttribute('data-afandy-category'));
            expect($query)->not->toHaveKeys(['stores_page', 'offers_page']);
        }
    })->with(['stores', 'offers'])->with(['ar', 'en']);

    test('filtered ajax suggestions and their full results link retain the selected type', function (string $type) {
        $otherType = $type === 'stores' ? 'offers' : 'stores';
        $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'Typed', 'afandy_category' => $type]))
            ->assertOk()->json('html');

        expect($html)->toContain('id="afandy-search-' . $type . '"', 'afandy_category=' . $type)
            ->not->toContain('id="afandy-search-' . $otherType . '"');
    })->with(['stores', 'offers']);

    test('a missing type defaults to offers without introducing an all results tab', function () {
        $this->get(route('website.search', ['locale' => 'en', 'afandy_search' => 'Typed']))
            ->assertOk()->assertViewHas('category', 'offers')
            ->assertSee('data-search-result-type="offers"', false)
            ->assertDontSee('data-search-result-type="stores"', false)
            ->assertDontSee('data-afandy-category="all"', false);
    });

    test('an empty selected type stays selected when another type has results', function () {
        $this->offer->update(['active' => false]);
        $this->get(route('website.search', ['locale' => 'en', 'afandy_search' => 'Typed', 'afandy_category' => 'offers']))
            ->assertOk()->assertViewHas('category', 'offers')->assertViewHas('counts', ['stores' => 1, 'offers' => 0])
            ->assertSee('No results found')->assertDontSee('data-search-result-type="stores"', false);
    });

    test('store pagination retains the selected type and type switching resets pagination', function () {
        foreach (range(1, 12) as $number) {
            $store = Client::factory()->create(['title' => ['en' => "Typed Brand {$number}"]]);
            $store->countries()->attach($this->country);
        }
        $response = $this->get(route('website.search', [
            'locale' => 'en', 'afandy_search' => 'Typed', 'afandy_category' => 'stores', 'stores_page' => 2,
        ]))->assertOk()->assertViewHas('counts', ['stores' => 13, 'offers' => 1])
            ->assertViewHas('stores', fn ($stores) => $stores->count() === 1)
            ->assertDontSee('data-search-result-type="offers"', false);
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*[@data-search-pagination]//a[@href]') as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            expect($query['afandy_category'])->toBe('stores');
            expect($query['afandy_search'])->toBe('Typed');
        }
        foreach ($xpath->query('//*[@data-afandy-category]') as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            expect($query)->not->toHaveKeys(['stores_page', 'offers_page']);
        }
    });
});

test('search rejects unsupported or non-scalar result types', function (mixed $type) {
    $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'Typed', 'afandy_category' => $type]))
        ->assertUnprocessable();
})->with(['all', 'untrusted', [['offers']]]);

test('the header includes an integrated type selector and a generic localized placeholder', function (string $locale, string $type) {
    $response = $this->get(route('website.index', ['locale' => $locale, 'afandy_category' => $type]))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
    $xpath = new DOMXPath($document);
    $form = '//form[@data-afandy-search]';

    expect($xpath->query($form . '//button[@data-search-category]'))->toHaveCount(2);
    expect($xpath->query($form . '//button[@data-search-category="' . $type . '" and @aria-pressed="true"]'))->toHaveCount(1);
    expect($xpath->query($form . '//input[@name="afandy_category"]')->item(0)->getAttribute('value'))->toBe($type);
    expect($xpath->query($form . '//input[@name="afandy_search"]')->item(0)->getAttribute('placeholder'))->toBe(__('Type to search...'));
    expect($xpath->query($form . '//button[@data-afandy-search-button]//*[@data-submit-spinner]'))->toHaveCount(1);
    expect($xpath->query($form . '//button[@data-search-category]/ancestor::div[contains(@class,"site-search-type")]/following-sibling::div[contains(@class,"site-search-field")]'))->toHaveCount(1);
})->with(['ar', 'en'])->with(['offers', 'stores']);

test('header and hero search default to offers when no type is selected', function () {
    $response = $this->get(route('website.index', ['locale' => 'en']))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//form[@role="search"]//input[@name="afandy_category" and @value="offers"]'))->toHaveCount(2);
    expect($xpath->query('//form[@data-afandy-search]//button[@data-search-category="offers" and @aria-pressed="true"]'))->toHaveCount(1);
});

test('ajax suggestions return at most ten results while full results retain all matches', function (string $type) {
    $store = Client::factory()->create(['title' => ['en' => 'Limit Brand']]);
    $store->countries()->attach($this->country);
    foreach (range(1, 11) as $number) {
        if ($type === 'stores') {
            $item = Client::factory()->create(['title' => ['en' => "Limited store {$number}"]]);
        } else {
            $item = Offer::factory()->create([
                'client_id' => $store->id, 'title' => ['en' => "Limited offer {$number}"],
                'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
            ]);
        }
        $item->countries()->attach($this->country);
    }
    $url = route('website.search', ['locale' => 'en', 'afandy_search' => 'Limited', 'afandy_category' => $type]);
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')->getJson($url)->assertOk()->json('html');
    expect(substr_count($html, 'class="site-search-result '))->toBe(10);
    expect($html)->toContain('class="site-search-view-all ', 'afandy_category=' . $type);
    $this->flushHeaders();
    $this->get($url)->assertOk()->assertViewHas($type, fn ($items) => $items->total() === 11 && $items->count() === 11);
})->with(['stores', 'offers']);

test('ajax suggestions without a type still respect the ten-result total limit', function () {
    foreach (range(1, 11) as $number) {
        $store = Client::factory()->create(['title' => ['en' => "Combined store {$number}"]]);
        $store->countries()->attach($this->country);
        $offer = Offer::factory()->create([
            'client_id' => $store->id, 'title' => ['en' => "Combined offer {$number}"],
            'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ]);
        $offer->countries()->attach($this->country);
    }
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'Combined']))->assertOk()->json('html');
    expect(substr_count($html, 'class="site-search-result '))->toBe(10);
});

test('store suggestions display localized available offer counts including empty stores', function (int $count, string $locale) {
    $store = Client::factory()->create(['title' => ['en' => 'Count Brand', 'ar' => 'متجر العدد']]);
    $store->countries()->attach($this->country);
    for ($number = 1; $number <= $count; $number++) {
        $offer = Offer::factory()->create([
            'client_id' => $store->id, 'title' => ['en' => "Unrelated offer {$number}"],
            'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ]);
        $offer->countries()->attach($this->country);
    }
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => $locale, 'afandy_search' => 'Count Brand', 'afandy_category' => 'stores']))
        ->assertOk()->json('html');
    $expected = $count > 0
        ? trans_choice('Offer count', $count, ['count' => number_format($count)])
        : __('No offers from :store yet', ['store' => $store->getTranslation('title', $locale)]);
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($document);
    $subtitle = $xpath->query('//span[contains(@class,"site-search-result-copy")]/small')->item(0)->textContent;

    expect(trim($subtitle))->toBe($expected);
})->with([0, 1, 2, 5, 12])->with(['ar', 'en']);

test('suggested store offer counts exclude unavailable and foreign-market offers', function () {
    $store = Client::factory()->create(['title' => ['en' => 'Availability Counter']]);
    $store->countries()->attach($this->country);
    foreach ([
        [],
        ['expiration_date' => now()->addDay()],
        ['expiration_date' => now()->subMinute()],
        ['launch_date' => now()->addDay()],
        ['active' => false],
        ['foreign' => true],
    ] as $attributes) {
        $foreign = $attributes['foreign'] ?? false;
        unset($attributes['foreign']);
        $offer = Offer::factory()->create(array_merge([
            'client_id' => $store->id, 'active' => true, 'launch_date' => now()->subDay(), 'expiration_date' => null,
        ], $attributes));
        $offer->countries()->attach($foreign ? Country::where('code', 'sa')->firstOrFail() : $this->country);
    }
    $html = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('website.search', ['locale' => 'en', 'afandy_search' => 'Availability Counter', 'afandy_category' => 'stores']))
        ->assertOk()->json('html');

    expect($html)->toContain('2 Offers');
});
