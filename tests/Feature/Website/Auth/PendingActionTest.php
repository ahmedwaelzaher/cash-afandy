<?php

use App\Models\Offer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Redot\Http\Middleware\EnsureDependenciesBuilt;
use Redot\Models\LoginToken;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    cache()->forever('settings.show_website_countries_dropdown', false);
    cache()->forever('settings.default_website_country', 'eg');
    $this->user = User::factory()->create();
    $store = Store::factory()->create(['active' => true]);
    $this->offer = Offer::factory()->create([
        'store_id' => $store->id, 'active' => true,
        'launch_date' => now()->subDay(), 'expiration_date' => null,
    ]);
    $this->destination = route('website.offers.index', ['category' => 'fashion', 'search' => 'test']) . '#offers';
    $this->intent = ['name' => 'favorite_offer', 'offer_id' => $this->offer->id, 'return_to' => $this->destination];
});

test('the pending favorite runs after password login for both regular and ajax requests', function (bool $ajax) {
    if ($ajax) {
        $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk()->assertJsonPath('authenticated', false);
    } else {
        $this->post(route('website.pending-action.store'), $this->intent)->assertRedirect(route('website.login'));
    }
    $this->assertDatabaseCount('offer_user', 0);
    $credentials = ['email' => $this->user->email, 'password' => 'password'];
    if ($ajax) {
        $this->withHeader('X-Login-Modal', 'true')->postJson(route('website.login.store'), $credentials)
            ->assertOk()->assertJsonPath('redirect', $this->destination)->assertSessionMissing('auth.pending_action');
    } else {
        $this->post(route('website.login.store'), $credentials)->assertRedirect($this->destination)->assertSessionMissing('auth.pending_action');
    }
    $this->assertDatabaseHas('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
})->with([false, true]);

test('social login completes the pending favorite before returning from the provider', function (string $provider) {
    cache()->forever('settings.' . $provider . '_client_id', 'test');
    cache()->forever('settings.' . $provider . '_client_secret', 'test');
    $driver = Mockery::mock();
    $driver->shouldReceive('fields')->andReturnSelf();
    $driver->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://provider.example/authorize'));
    $driver->shouldReceive('user')->once()->andReturn((new SocialiteUser)->map([
        'id' => 'test-provider-id', 'email' => $this->user->email,
    ]));
    Socialite::shouldReceive('driver')->with($provider)->twice()->andReturn($driver);

    $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk();
    $this->get(route('website.socialite.redirect', ['provider' => $provider, 'return_to' => $this->destination]))
        ->assertRedirect('https://provider.example/authorize');
    $this->get(route('website.socialite.callback', $provider))->assertRedirect($this->destination)
        ->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseHas('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
})->with(['google', 'facebook']);

test('magic links and email codes complete the same pending action', function (bool $code) {
    $token = LoginToken::generate($this->user->email, 'users');
    $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk();
    $response = $code
        ? $this->post(route('website.magic-link-code.store'), ['email' => $this->user->email, 'code' => $token->code])
        : $this->get(route('website.magic-link.verify', ['token' => $token->token]));
    $response->assertRedirect($this->destination)->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseHas('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
})->with([false, true]);

test('creating an account also completes the pending action', function () {
    Notification::fake();
    cache()->forever('settings.cloudflare_turnstile_site_key', '');
    $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk();
    $this->post(route('website.register.store'), [
        'first_name' => 'Test', 'last_name' => 'User', 'email' => 'pending-registration@example.test',
        'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'gender' => 'male',
    ])->assertSessionHasNoErrors()->assertRedirect($this->destination)->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseHas('offer_user', ['user_id' => auth('users')->id(), 'offer_id' => $this->offer->id]);
});

test('failed login retains the intent and an existing favorite is not toggled or duplicated', function () {
    $this->user->favoriteOffers()->attach($this->offer);
    $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk();
    $this->postJson(route('website.login.store'), ['email' => $this->user->email, 'password' => 'wrong'])
        ->assertUnprocessable()->assertSessionHas('auth.pending_action.name', 'favorite_offer');
    $this->assertGuest('users');
    $this->post(route('website.login.store'), ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect($this->destination)->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseCount('offer_user', 1);
});

test('expired and cancelled intents do not run on later login', function (bool $expired) {
    $token = $this->postJson(route('website.pending-action.store'), $this->intent)->json('token');
    if ($expired) {
        $this->travel(11)->minutes();
    } else {
        $this->deleteJson(route('website.pending-action.destroy'), ['token' => $token])
            ->assertNoContent()->assertSessionMissing('auth.pending_action');
    }
    $this->post(route('website.login.store'), ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect(route('website.index'))->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseCount('offer_user', 0);
})->with([false, true]);

test('late cancellation cannot erase a newer pending intent', function () {
    $old = $this->postJson(route('website.pending-action.store'), $this->intent)->json('token');
    $new = $this->postJson(route('website.pending-action.store'), ['name' => 'navigate', 'return_to' => $this->destination])->json('token');
    $this->deleteJson(route('website.pending-action.destroy'), ['token' => $old])
        ->assertNoContent()->assertSessionHas('auth.pending_action.token', $new);
});

test('unsupported actions and foreign redirects are rejected without overwriting a valid intent', function () {
    $token = $this->postJson(route('website.pending-action.store'), $this->intent)->json('token');
    foreach ([['name' => 'delete_user'], ['return_to' => 'https://external.example/path'], ['return_to' => '//external.example/path']] as $invalid) {
        $this->postJson(route('website.pending-action.store'), array_replace($this->intent, $invalid))
            ->assertUnprocessable()->assertSessionHas('auth.pending_action.token', $token);
    }
});

test('unpublished offers cannot be queued and availability is checked again at login', function () {
    $this->offer->update(['active' => false]);
    $this->postJson(route('website.pending-action.store'), $this->intent)->assertNotFound();
    $this->offer->update(['active' => true]);
    $this->postJson(route('website.pending-action.store'), $this->intent)->assertOk();
    $this->offer->delete();
    $this->post(route('website.login.store'), ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect($this->destination)->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseCount('offer_user', 0);
});

test('pending removal only removes the signed in users chosen offer', function () {
    $other = User::factory()->create();
    $this->user->favoriteOffers()->attach($this->offer);
    $other->favoriteOffers()->attach($this->offer);
    $this->postJson(route('website.pending-action.store'), array_replace($this->intent, ['name' => 'unfavorite_offer']))->assertOk();
    $this->post(route('website.login.store'), ['email' => $this->user->email, 'password' => 'password'])->assertRedirect($this->destination);
    $this->assertDatabaseMissing('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
    $this->assertDatabaseHas('offer_user', ['user_id' => $other->id, 'offer_id' => $this->offer->id]);
});

test('a session already signed in elsewhere completes the requested action without another login', function () {
    $this->actingAs($this->user, 'users')->postJson(route('website.pending-action.store'), $this->intent)
        ->assertOk()->assertJsonPath('authenticated', true)->assertJsonPath('redirect', $this->destination)
        ->assertSessionMissing('auth.pending_action');
    $this->assertDatabaseHas('offer_user', ['user_id' => $this->user->id, 'offer_id' => $this->offer->id]);
});
