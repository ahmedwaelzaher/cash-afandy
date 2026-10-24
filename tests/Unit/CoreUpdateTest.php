<?php

use App\Models\User;
use Redot\Models\Setting;

beforeEach(function () {
    require dirname(__DIR__, 2) . '/app/settings.php';
});

test('the settings registry preserves Cash Afandy identity and supported markets', function () {
    expect(Setting::default('app_name.en'))->toBe('Cash Afandy')
        ->and(Setting::default('website_countries'))->toBe(['eg', 'sa'])
        ->and(Setting::default('default_website_country'))->toBe('eg')
        ->and(Setting::default('website_locales'))->toBe(['en', 'ar']);
});

test('the settings registry retains the original field types and validation', function () {
    expect(Setting::type('app_logo_light'))->toBe('file')
        ->and(Setting::type('show_website_countries_dropdown'))->toBe('boolean')
        ->and(Setting::type('theme'))->toBe('array')
        ->and(Setting::rules()['website_countries'])->toBe(['required', 'array', 'min:1'])
        ->and(array_keys(Setting::schema()))->toContain('google_client_id', 'google_client_secret', 'facebook_client_id', 'facebook_client_secret', 'footer_about_description');
});

test('personal details validation survives the authentication upgrade', function () {
    $rules = User::personalDetailsRules();

    expect($rules['birthdate'])->toBe(['nullable', 'date', 'before:today'])
        ->and($rules['phone'])->toContain('phone', 'starts_with:+')
        ->and($rules)->not->toHaveKeys(['country_id', 'state_id', 'referral_code']);
});
