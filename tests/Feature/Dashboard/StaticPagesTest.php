<?php

use App\Models\Admin;
use App\Models\StaticPage;

test('static pages index page can be rendered', function () {
    $this->actingAs(Admin::factory()->create(), 'admins');

    $response = $this->get(route('dashboard.static-pages.index'));

    $response->assertOk();
});

test('static pages create page can be rendered', function () {
    $this->actingAs(Admin::factory()->create(), 'admins');

    $response = $this->get(route('dashboard.static-pages.create'));

    $response->assertOk();
});

test('static pages can be created', function () {
    $this->actingAs(Admin::factory()->create(), 'admins');

    $data = [
        'title' => [],
        'content' => [],
    ];

    foreach (array_keys(config('app.locales')) as $locale) {
        $data['title'][$locale] = fake()->sentence(3);
        $data['content'][$locale] = fake()->paragraphs(2, true);
    }

    $response = $this->post(route('dashboard.static-pages.store'), $data);

    $response->assertRedirect(route('dashboard.static-pages.index'));
    $response->assertSessionHas('success');

    $fallbackLocale = config('app.fallback_locale');
    $this->assertDatabaseHas('static_pages', [
        "title->{$fallbackLocale}" => $data['title'][$fallbackLocale],
    ]);
});

test('static pages store validates required fields', function () {
    $this->actingAs(Admin::factory()->create(), 'admins');

    $response = $this->post(route('dashboard.static-pages.store'), []);

    $response->assertSessionHasErrors(['title']);
});

test('static pages edit page can be rendered', function () {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::factory()->create();

    $response = $this->actingAs($admin, 'admins')->get(route('dashboard.static-pages.edit', $staticPage));

    $response->assertOk();
});

test('static pages can be updated', function () {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::factory()->create();

    $data = [
        'title' => [],
        'content' => [],
    ];

    foreach (array_keys(config('app.locales')) as $locale) {
        $data['title'][$locale] = "Updated Title {$locale}";
        $data['content'][$locale] = "Updated content {$locale}";
    }

    $response = $this->actingAs($admin, 'admins')->put(route('dashboard.static-pages.update', $staticPage), $data);

    $response->assertSessionHasNoErrors();

    $staticPage->refresh();

    foreach (array_keys(config('app.locales')) as $locale) {
        expect($staticPage->getTranslation('title', $locale))->toBe("Updated Title {$locale}");
    }
});

test('static pages update validates required fields', function () {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::factory()->create();

    $response = $this->actingAs($admin, 'admins')->put(route('dashboard.static-pages.update', $staticPage), []);

    $response->assertSessionHasErrors(['title']);
});

test('static pages can be deleted', function () {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::factory()->create();

    $response = $this->actingAs($admin, 'admins')->delete(route('dashboard.static-pages.destroy', $staticPage));

    $response->assertRedirect(route('dashboard.static-pages.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('static_pages', [
        'id' => $staticPage->id,
    ]);
});

test('static page forms expose the activation toggle with the current state', function (bool $active) {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::factory()->create(['active' => $active]);

    $response = $this->actingAs($admin, 'admins')
        ->get(route('dashboard.static-pages.edit', $staticPage))
        ->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
    $toggle = (new DOMXPath($document))->query('//input[@name="active" and @type="checkbox"]')->item(0);

    expect($toggle)->not->toBeNull()
        ->and($toggle->hasAttribute('checked'))->toBe($active);

    $this->get(route('dashboard.static-pages.create'))
        ->assertOk()
        ->assertSee('name="active"', false);
})->with([true, false]);

test('dashboard activation controls static page access and footer visibility', function (bool $active) {
    $admin = Admin::factory()->create();
    $staticPage = StaticPage::where('slug', 'faq')->firstOrFail();
    $staticPage->update(['active' => ! $active]);
    $data = ['title' => $staticPage->getTranslations('title'), 'content' => $staticPage->getTranslations('content')];

    if ($active) $data['active'] = 'on';

    $this->actingAs($admin, 'admins')
        ->put(route('dashboard.static-pages.update', $staticPage), $data)
        ->assertSessionHasNoErrors();

    expect($staticPage->refresh()->active)->toBe($active);

    $url = route('website.static-pages.show', $staticPage);
    $this->get($url)->assertStatus($active ? 200 : 404);
    $response = $this->get(route('website.index'))->assertOk();

    if ($active) {
        $response->assertSee('href="' . $url . '"', false);
    } else {
        $response->assertDontSee('href="' . $url . '"', false);
    }
})->with([true, false]);
