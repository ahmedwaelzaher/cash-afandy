<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
    foreach (['google', 'facebook'] as $provider) {
        cache()->forever('settings.' . $provider . '_client_id', 'test');
        cache()->forever('settings.' . $provider . '_client_secret', 'test');
    }
});

test('social login returns to the originating page with its filters after the callback', function (string $provider, string $locale) {
    $user = User::factory()->create();
    $socialUser = (new SocialiteUser)->map(['id' => 'provider-user', 'email' => $user->email, 'name' => 'Test User']);
    $driver = Mockery::mock();
    $driver->shouldReceive('fields')->andReturnSelf();
    $driver->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://provider.example/authorize'));
    $driver->shouldReceive('user')->once()->andReturn($socialUser);
    Socialite::shouldReceive('driver')->with($provider)->twice()->andReturn($driver);

    $returnTo = route('website.offers.index', ['locale' => $locale, 'category' => 'fashion', 'search' => 'test']) . '#offers';
    $this->get(route('website.socialite.redirect', ['locale' => $locale, 'provider' => $provider, 'return_to' => $returnTo]))
        ->assertRedirect('https://provider.example/authorize')->assertSessionHas('url.intended', $returnTo);
    $this->get(route('website.socialite.callback', ['locale' => $locale, 'provider' => $provider]))
        ->assertRedirect($returnTo)->assertSessionMissing('url.intended');
    $this->assertAuthenticatedAs($user, 'users');
})->with(['google', 'facebook'])->with(['ar', 'en']);

test('social login rejects external return destinations without replacing a valid intended page', function (string $returnTo) {
    $driver = Mockery::mock();
    $driver->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://provider.example/authorize'));
    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);

    $intended = route('website.favorites.index');
    $this->withSession(['url.intended' => $intended])
        ->get(route('website.socialite.redirect', ['provider' => 'google', 'return_to' => $returnTo]))
        ->assertRedirect('https://provider.example/authorize')->assertSessionHas('url.intended', $intended);
})->with([
    'https://external.example/path',
    '//external.example/path',
    'http://localhost.external.example/path',
    'http://localhost@external.example/path',
    'javascript:alert(1)',
]);

test('modal social links carry their originating page while standalone login keeps the existing intended destination', function () {
    $url = route('website.offers.index', ['search' => 'test']);
    $this->get($url)->assertOk()
        ->assertSee('data-login-return', false)
        ->assertSee('return_to=' . rawurlencode($url), false);
    $this->get(route('website.login'))->assertOk()->assertDontSee('data-login-return', false);
});

test('social registration uses only an unexpired captured invitation and ignores a supplied code', function (string $provider, string $invitation) {
    Notification::fake();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    if ($invitation !== 'missing') {
        $this->postJson(route('website.referral.store'), ['referral_code' => $owner->referral_code])
            ->assertOk()->assertSessionHas('auth.invitation.code', $owner->referral_code);
    }

    $driver = Mockery::mock();
    $driver->shouldReceive('fields')->andReturnSelf();
    $driver->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://provider.example/authorize'));
    $driver->shouldReceive('user')->once()->andReturn((new SocialiteUser)->map([
        'id' => 'new-provider-user', 'email' => 'new-social@example.test', 'name' => 'New Friend',
    ]));
    Socialite::shouldReceive('driver')->with($provider)->twice()->andReturn($driver);

    $this->get(route('website.socialite.redirect', ['provider' => $provider, 'referral_code' => $other->referral_code]))
        ->assertRedirect('https://provider.example/authorize');
    if ($invitation === 'expired') $this->travel(61)->minutes();
    $this->get(route('website.socialite.callback', ['provider' => $provider]))
        ->assertRedirect(route('website.index'))->assertSessionMissing('auth.invitation');

    $user = auth('users')->user();
    expect($user->email)->toBe('new-social@example.test');
    if ($invitation === 'valid') {
        expect($user->referral?->referrer_id)->toBe($owner->id);
    } else {
        expect($user->referral)->toBeNull();
    }
})->with(['google', 'facebook'])->with(['valid', 'expired', 'missing']);

test('social redirect no longer accepts registration form submissions', function (string $provider) {
    $this->postJson(route('website.socialite.redirect', ['provider' => $provider]), ['referral_code' => 'MANUALCODE'])
        ->assertStatus(405)->assertSessionMissing('auth.invitation');
})->with(['google', 'facebook']);

test('registration uses ordinary social links instead of alternate form submit buttons', function () {
    $this->get(route('website.register'))->assertOk()
        ->assertSee('href="' . route('website.socialite.redirect', ['provider' => 'google']) . '"', false)
        ->assertSee('href="' . route('website.socialite.redirect', ['provider' => 'facebook']) . '"', false)
        ->assertDontSee('formaction=', false)->assertDontSee('formnovalidate', false);
});
