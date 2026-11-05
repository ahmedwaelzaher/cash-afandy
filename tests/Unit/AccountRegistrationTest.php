<?php

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Redot\Http\Middleware\EnsureDependenciesBuilt;
use Redot\Models\Language;

uses(TestCase::class);

beforeEach(function () {
    // Exercise account creation without depending on unrelated application tables.
    config([
        'database.default' => 'account_registration_test',
        'database.connections.account_registration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
    ]);
    foreach ([
        '0001_01_01_000000_create_admins_table.php',
        '0001_01_01_000005_create_permission_tables.php',
        '0001_01_01_000006_create_settings_table.php',
        '0001_01_01_000007_create_users_table.php',
        '0001_01_01_000010_create_languages_table.php',
        '0001_01_01_000012_create_countries_table.php',
        '0001_01_01_000012_create_states_table.php',
        '0001_01_01_000013_create_static_pages_table.php',
        '0001_01_01_000015_create_notifications_table.php',
        '2026_07_11_000012_create_user_preferences_table.php',
        '2026_10_03_000003_add_social_ids_to_users_table.php',
        '2026_10_03_000004_add_profile_fields_to_users_table.php',
        '2026_11_01_000001_add_notification_preferences_to_user_preferences.php',
        '2026_11_04_000001_create_wallets_and_wallet_transactions.php',
    ] as $migration) {
        (require database_path('migrations/' . $migration))->up();
    }

    // Keep language and market resolution deterministic for both registration paths.
    foreach (['ar', 'en'] as $locale) {
        Language::create([
            'code' => $locale,
            'name' => $locale,
            'is_rtl' => $locale === 'ar',
        ]);
    }
    $this->country = Country::create([
        'code' => 'eg',
        'name' => 'Egypt',
        'native' => 'مصر',
        'currency' => 'EGP',
        'phone' => '20',
        'continent' => 'AF',
        'capital' => 'Cairo',
        'languages' => ['ar'],
    ]);
    cache()->forever('settings.website_locales', ['ar', 'en']);
    cache()->forever('settings.website_countries', ['eg']);
    cache()->forever('settings.default_website_country', 'eg');
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.cloudflare_turnstile_site_key', '');
    cache()->forever('website-country-ip:127.0.0.1', 'eg');
    URL::defaults(['locale' => 'en']);
    Notification::fake();
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
});

afterEach(function () {
    DB::purge('account_registration_test');
});

test('the registration page renders personal details and account consent in both languages', function (string $locale) {
    $this->get(route('website.register', ['locale' => $locale]))->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="country_id"', false)
        ->assertSee('name="gender"', false)
        ->assertSee('name="terms_accepted"', false);
})->with(['ar', 'en']);

test('registration creates the account preferences and country wallet independently', function () {
    $this->post(route('website.register.store'), [
        'first_name' => 'New',
        'last_name' => 'Account',
        'email' => 'new-account@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'male',
        'country_id' => $this->country->id,
        'account_country_confirmation' => 1,
        'terms_accepted' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $user = auth('users')->user();
    expect($user->country_id)->toBe($this->country->id)
        ->and($user->preferences()->count())->toBe(1)
        ->and($user->wallet->currency)->toBe('EGP')
        ->and($user->wallet->fresh()->balance)->toBe(0)
        ->and(User::count())->toBe(1);
});

test('registration still requires country confirmation and terms acceptance', function (string $consent) {
    $input = [
        'first_name' => 'New',
        'last_name' => 'Account',
        'email' => 'incomplete-account@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'male',
        'country_id' => $this->country->id,
        'account_country_confirmation' => 1,
        'terms_accepted' => 1,
    ];
    unset($input[$consent]);
    $this->postJson(route('website.register.store'), $input)->assertUnprocessable();

    expect(User::count())->toBe(0);
})->with(['account_country_confirmation', 'terms_accepted']);

test('social registration creates an independent account and requests personal details completion', function (string $provider) {
    cache()->forever('settings.' . $provider . '_client_id', 'test');
    cache()->forever('settings.' . $provider . '_client_secret', 'test');
    $driver = Mockery::mock();
    $driver->shouldReceive('fields')->andReturnSelf();
    $driver->shouldReceive('user')->once()->andReturn((new SocialiteUser)->map([
        'id' => 'new-social-account',
        'email' => 'new-social-account@example.test',
        'name' => 'Social Account',
    ]));
    Socialite::shouldReceive('driver')->with($provider)->once()->andReturn($driver);
    $this->get(route('website.socialite.callback', ['provider' => $provider]))->assertRedirect(route('website.profile.complete'));

    $user = auth('users')->user();
    expect($user->email)->toBe('new-social-account@example.test')
        ->and($user->{$provider . '_id'})->toBe('new-social-account')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->preferences()->count())->toBe(1)
        ->and($user->wallet)->toBeNull();
})->with(['google', 'facebook']);
