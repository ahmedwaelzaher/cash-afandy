<?php

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', true);
    cache()->forever('settings.website_countries', ['eg', 'sa']);
    cache()->forever('settings.default_website_country', 'eg');
    cache()->forever('website-country-ip:127.0.0.1', 'eg');
    $this->egypt = Country::where('code', 'eg')->firstOrFail();
    $this->saudi = Country::where('code', 'sa')->firstOrFail();
});

test('an explicit country change updates only the authenticated users preferences', function (string $locale) {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $other->preferences()->update(['country_id' => $this->egypt->id]);
    $this->actingAs($user, 'users')->get(route('website.stores.index', ['locale' => $locale, 'country' => 'sa']))
        ->assertOk()->assertSessionHas('website_country', 'sa')->assertCookie('website_country', 'sa');
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => $this->saudi->id]);
    $this->assertDatabaseHas('user_preferences', ['user_id' => $other->id, 'country_id' => $this->egypt->id]);
})->with(['ar', 'en']);

test('a saved country takes precedence over browser history and needs no explicit query', function () {
    $user = User::factory()->create();
    $user->preferences()->update(['country_id' => $this->saudi->id]);
    $this->actingAs($user, 'users')->withSession(['website_country' => 'eg'])->withCookie('website_country', 'eg')
        ->get(route('website.stores.index'))->assertOk()->assertSessionHas('website_country', 'sa')->assertCookie('website_country', 'sa');
});

test('guests keep their selection in the session and cookie without changing user preferences', function () {
    $user = User::factory()->create();
    $user->preferences()->update(['country_id' => $this->egypt->id]);
    $this->get(route('website.stores.index', ['country' => 'sa']))->assertOk()
        ->assertSessionHas('website_country', 'sa')->assertCookie('website_country', 'sa');
    $this->get(route('website.stores.index'))->assertOk()->assertSessionHas('website_country', 'sa');
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => $this->egypt->id]);
});

test('a cookie restores a guest selection after the session is gone', function () {
    $this->withCookie('website_country', 'sa')->get(route('website.stores.index'))
        ->assertOk()->assertSessionHas('website_country', 'sa');
});

test('automatic country resolution and invalid selections do not overwrite account preferences', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users')->withCookie('website_country', 'sa')->get(route('website.stores.index'))
        ->assertOk()->assertSessionHas('website_country', 'sa');
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => null]);
    $user->preferences()->update(['country_id' => $this->saudi->id]);
    $user->unsetRelation('preferences');
    $this->get(route('website.stores.index', ['country' => 'unsupported']))
        ->assertOk()->assertSessionHas('website_country', 'sa');
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => $this->saudi->id]);
});

test('disabling the country dropdown keeps the default market without changing the saved country', function () {
    cache()->forever('settings.show_website_countries_dropdown', false);
    $user = User::factory()->create();
    $user->preferences()->update(['country_id' => $this->saudi->id]);
    $this->actingAs($user, 'users')->get(route('website.stores.index', ['country' => 'eg']))->assertOk();
    expect(website_country()->code)->toBe('eg');
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => $this->saudi->id]);
});

test('repeated country lookups do not repeat preference writes', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'users');
    DB::enableQueryLog();
    $this->get(route('website.stores.index', ['country' => 'sa']))->assertOk();
    $this->get(route('website.stores.index', ['country' => 'sa']))->assertOk();
    $updates = collect(DB::getQueryLog())->filter(fn ($query) => str_starts_with(strtolower($query['query']), 'update') && str_contains($query['query'], 'user_preferences'));
    DB::disableQueryLog();
    expect($updates)->toHaveCount(1);
});

test('legacy accounts without a preference row can save a country selection', function () {
    $user = User::factory()->create();
    $user->preferences()->delete();
    $this->actingAs($user, 'users')->get(route('website.stores.index', ['country' => 'sa']))->assertOk();
    expect($user->preferences()->count())->toBe(1);
    $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id, 'country_id' => $this->saudi->id]);
});
