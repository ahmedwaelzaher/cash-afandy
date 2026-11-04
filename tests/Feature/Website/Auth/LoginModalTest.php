<?php

use App\Models\User;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
});

test('website pages include one accessible login modal with the existing login form', function (string $locale) {
    cache()->forever('settings.google_client_id', 'test');
    cache()->forever('settings.google_client_secret', 'test');
    cache()->forever('settings.facebook_client_id', 'test');
    cache()->forever('settings.facebook_client_secret', 'test');

    $response = $this->get(route('website.stores.index', ['locale' => $locale]));
    $response->assertOk()->assertSee('id="site-login-modal"', false)
        ->assertSee('aria-labelledby="site-login-title"', false)
        ->assertSee('data-login-modal-form', false)
        ->assertSee('id="login-modal-password"', false)
        ->assertSee('btn btn-brand w-100', false)
        ->assertSee('btn-social-magic-link', false)
        ->assertSee('btn-social-google', false)
        ->assertSee('btn-social-facebook', false)
        ->assertSee('modal-dialog-scrollable', false);

    expect(substr_count($response->getContent(), 'id="site-login-modal"'))->toBe(1);
    $this->get(route('website.login', ['locale' => $locale]))->assertOk()
        ->assertSee('id="login-password"', false)
        ->assertDontSee('id="site-login-modal"', false)
        ->assertDontSee('data-login-modal-form', false);
})->with(['ar', 'en']);

test('modal login uses the existing user authentication pipeline and returns a fresh csrf token', function () {
    $user = User::factory()->create();
    $oldToken = str_repeat('a', 40);
    $response = $this->withSession(['_token' => $oldToken, 'url.intended' => route('website.profile.edit')])
        ->withHeader('X-Login-Modal', 'true')->postJson(route('website.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response->assertOk()->assertJsonStructure(['csrfToken'])
        ->assertSessionMissing('url.intended');
    expect($response->json('csrfToken'))->toBe(session()->token())->not->toBe($oldToken);
    $this->assertAuthenticatedAs($user, 'users');
    $this->assertGuest('admins');
});

test('modal login keeps validation failures and throttling in the existing pipeline', function () {
    $user = User::factory()->create();
    $this->withHeader('X-Login-Modal', 'true');
    $this->postJson(route('website.login.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password'], 'payload');
    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('website.login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email', 'payload');
    }
    $this->postJson(route('website.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()->assertJsonValidationErrors('email', 'payload');
    $this->assertGuest('users');
});

test('regular login keeps its intended redirect and protected routes still reject guests', function () {
    $this->get(route('website.favorites.index'))->assertRedirect(route('website.login'));
    $user = User::factory()->create();
    $this->post(route('website.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('website.favorites.index'));
    $this->assertAuthenticatedAs($user, 'users');
});
