<?php

use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
});

test('the website coin cover follows the existing page loader setting', function (string $locale, string $page, bool $enabled) {
    cache()->forever('settings.page_loader_enabled', $enabled);

    $response = $this->get(route($page, ['locale' => $locale, 'afandy_search' => 'example']));
    $response->assertOk();
    if ($enabled) {
        $response->assertSee('class="page-loader site-page-loader', false)
            ->assertSee('afandy-coin-front.svg', false)
            ->assertSee('afandy-coin-back.svg', false)
            ->assertSee('role="progressbar"', false);
        expect(substr_count($response->getContent(), 'class="site-loader-coin-edge"'))->toBe(17);
    } else {
        $response->assertDontSee('class="page-loader site-page-loader', false)
            ->assertDontSee('afandy-coin-front.svg', false)
            ->assertDontSee('afandy-coin-back.svg', false);
    }
})->with(['ar', 'en'])->with([
    'stores' => 'website.stores.index',
    'offers' => 'website.offers.index',
    'search' => 'website.search',
    'login' => 'website.login',
])->with([true, false]);

test('the default page loader still renders the dashboard logo and spinner', function () {
    $html = (string) $this->blade('<x-page-loader />');

    expect($html)->toContain('page page-loader', 'spinner-border')
        ->not->toContain('site-loader-coin', 'afandy-coin-front.svg');
});
