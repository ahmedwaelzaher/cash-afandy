<?php

use App\Models\StaticPage;
use Database\Seeders\StaticPageSeeder;

dataset('website static pages', ['privacy-policy', 'terms-and-conditions', 'contact-us', 'faq']);

test('footer displays any active static page with its localized title and slug', function (string $locale) {
    $activePage = StaticPage::factory()->create([
        'slug' => 'help-centre',
        'title' => ['ar' => 'مركز المساعدة', 'en' => 'Help Centre'],
        'active' => true,
    ]);
    $inactivePage = StaticPage::factory()->create([
        'slug' => 'hidden-page',
        'title' => ['ar' => 'صفحة مخفية', 'en' => 'Hidden Page'],
        'active' => false,
    ]);

    $this->get(route('website.index', ['locale' => $locale]))
        ->assertOk()
        ->assertSee('href="' . route('website.static-pages.show', ['locale' => $locale, 'staticPage' => $activePage->slug]) . '"', false)
        ->assertSeeText($activePage->getTranslation('title', $locale))
        ->assertDontSee('href="' . route('website.static-pages.show', ['locale' => $locale, 'staticPage' => $inactivePage->slug]) . '"', false)
        ->assertDontSeeText($inactivePage->getTranslation('title', $locale));
})->with(['ar', 'en']);

test('static page can be displayed', function () {
    $staticPage = StaticPage::factory()->create();

    $response = $this->get(route('website.static-pages.show', $staticPage));

    $response->assertOk();
    $response->assertSee($staticPage->title);
});

test('static page returns 404 for non-existent page', function () {
    $response = $this->get(route('website.static-pages.show', 'non-existent-slug'));

    $response->assertNotFound();
});

test('active static pages show localized content and footer links', function (string $slug, string $locale) {
    $staticPage = StaticPage::where('slug', $slug)->firstOrFail();

    $this->get(route('website.static-pages.show', ['locale' => $locale, 'staticPage' => $slug]))
        ->assertOk()
        ->assertSee($staticPage->getTranslation('title', $locale))
        ->assertSee($staticPage->getTranslation('content', $locale), false);

    $this->get(route('website.index', ['locale' => $locale]))
        ->assertOk()
        ->assertSee('href="' . route('website.static-pages.show', ['locale' => $locale, 'staticPage' => $slug]) . '"', false);
})->with('website static pages')->with(['ar', 'en']);

test('inactive static pages are hidden from the footer and cannot be opened', function (string $slug, string $locale) {
    StaticPage::where('slug', $slug)->firstOrFail()->update(['active' => false]);

    $url = route('website.static-pages.show', ['locale' => $locale, 'staticPage' => $slug]);

    $this->get(route('website.index', ['locale' => $locale]))
        ->assertOk()
        ->assertDontSee('href="' . $url . '"', false);

    $this->get($url)->assertNotFound();
})->with('website static pages')->with(['ar', 'en']);

test('static page content can be refreshed without changing activation or unrelated pages', function () {
    $staticPage = StaticPage::where('slug', 'contact-us')->firstOrFail();
    $staticPage->update(['active' => false, 'content' => ['ar' => 'قديم', 'en' => 'Old content']]);
    $otherPage = StaticPage::factory()->create();
    $otherPageAttributes = $otherPage->refresh()->getAttributes();
    $pageCount = StaticPage::count();

    $this->seed(StaticPageSeeder::class);
    $this->seed(StaticPageSeeder::class);

    expect(StaticPage::count())->toBe($pageCount)
        ->and($staticPage->refresh()->active)->toBeFalse()
        ->and($otherPage->refresh()->getAttributes())->toBe($otherPageAttributes);

    foreach (['ar', 'en'] as $locale) {
        expect($staticPage->getTranslation('content', $locale))
            ->toContain('mailto:support@cashafandy.com', 'mailto:partners@cashafandy.com');
    }
});
