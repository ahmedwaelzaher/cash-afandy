<?php

use App\Models\Admin;
use Redot\Models\Setting;

test('admins can view the settings page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admins')->get(route('dashboard.settings.edit'));

    $response->assertStatus(200);

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
