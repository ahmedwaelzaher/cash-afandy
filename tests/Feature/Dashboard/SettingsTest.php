<?php

use App\Models\Admin;
use Redot\Models\Setting;

test('admins can view the settings page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admins')->get(route('dashboard.settings.edit'));

    $response->assertStatus(200);
    $response->assertSee('name="hero_words"', false);
    foreach (array_keys(config('app.locales')) as $locale) {
        $response->assertSee('name="word[' . $locale . ']"', false);
    }

    foreach (array_keys(config('app.locales')) as $locale) {
        $response->assertSee('name="theme[fonts][' . $locale . ']"', false);
    }
});

test('admins can update the settings', function () {
    $admin = Admin::factory()->create();

    $names = [];
    foreach (array_keys(config('app.locales')) as $locale) {
        $names[$locale] = fake($locale)->word;
    }

    $response = $this->actingAs($admin, 'admins')->put(route('dashboard.settings.update'), [
        'app_name' => $names,
        'website_locales' => ['en'],
        'dashboard_locales' => ['en'],
        'website_countries' => ['eg'],
        'default_website_country' => 'eg',
        'hero_words' => setting('hero_words'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('theme saves locale fonts and removes the obsolete font while preserving other theme settings', function () {
    $theme = ['font' => 'serif', 'primary' => 'green', 'base' => 'slate', 'radius' => 1];
    Setting::set('theme', $theme);
    $fonts = ['ar' => 'rubik', 'en' => 'dm-sans'];

    $this->actingAs(Admin::factory()->create(), 'admins')
        ->put(route('dashboard.settings.update'), [
            'app_name' => setting('app_name'),
            'website_locales' => ['en', 'ar'],
            'dashboard_locales' => ['en', 'ar'],
            'website_countries' => ['eg'],
            'default_website_country' => 'eg',
            'default_locale' => 'ar',
            'theme' => ['fonts' => $fonts],
            'hero_words' => setting('hero_words'),
        ])->assertSessionHasNoErrors();

    unset($theme['font']);
    expect(Setting::get('theme', fresh: true))->toBe($theme + ['fonts' => $fonts])
        ->and(Setting::get('default_locale', fresh: true))->toBe('ar');
});

test('theme rejects a default locale that is not defined', function () {
    $this->actingAs(Admin::factory()->create(), 'admins')
        ->put(route('dashboard.settings.update'), ['default_locale' => 'undefined'])
        ->assertSessionHasErrors('default_locale');
});

test('theme rejects an unavailable font or an undefined locale', function (array $fonts, string $error) {
    $this->actingAs(Admin::factory()->create(), 'admins')
        ->put(route('dashboard.settings.update'), ['theme' => ['fonts' => $fonts]])
        ->assertSessionHasErrors($error);
})->with([
    'unavailable font' => [['ar' => 'missing-font'], 'theme.fonts.ar'],
    'undefined locale' => [['undefined' => 'rubik'], 'theme.fonts'],
]);

test('theme saves translated hero words and rejects clearing the repeater', function () {
    $this->actingAs(Admin::factory()->create(), 'admins');
    $words = [
        ['word' => ['ar' => 'عرض', 'en' => 'offer']],
        ['word' => ['ar' => 'كوبون', 'en' => 'coupon']],
    ];
    $settings = [
        'app_name' => setting('app_name'),
        'website_locales' => ['ar', 'en'],
        'dashboard_locales' => ['ar', 'en'],
        'website_countries' => ['eg'],
        'default_website_country' => 'eg',
    ];

    $this->put(route('dashboard.settings.update'), $settings + ['hero_words' => $words])
        ->assertSessionHasNoErrors();
    expect(Setting::get('hero_words', fresh: true))->toBe($words);

    $this->put(route('dashboard.settings.update'), $settings + ['hero_words' => ''])
        ->assertSessionHasErrors('hero_words');
    expect(Setting::get('hero_words', fresh: true))->toBe($words);
});

test('hero words reject malformed or excessively long translations', function (array $words, string $error) {
    $this->actingAs(Admin::factory()->create(), 'admins')
        ->put(route('dashboard.settings.update'), ['hero_words' => $words])
        ->assertSessionHasErrors($error);
})->with([
    'empty repeater' => [[], 'hero_words'],
    'empty translation' => [[['word' => ['ar' => '', 'en' => 'offer']]], 'hero_words.0.word.ar'],
    'invalid translation' => [[['word' => ['ar' => ['invalid']]]], 'hero_words.0.word.ar'],
    'long translation' => [[['word' => ['en' => str_repeat('x', 41)]]], 'hero_words.0.word.en'],
]);
