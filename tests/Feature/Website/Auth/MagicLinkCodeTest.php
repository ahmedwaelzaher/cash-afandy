<?php

use App\Models\User;
use Redot\Models\LoginToken;

test('code entry screen can be rendered with valid token', function () {
    $user = User::factory()->create();
    LoginToken::generate($user->email, 'users');

    $response = $this->get(route('website.magic-link-code.create', ['email' => base64_encode($user->email)]));

    $response->assertOk();
    $response->assertSee($user->email);
});

test('code entry screen redirects without email', function () {
    $response = $this->get(route('website.magic-link-code.create'));

    $response->assertRedirect(route('website.magic-link.create'));
});

test('code entry screen does not reveal whether an email has a valid token', function () {
    $response = $this->get(route('website.magic-link-code.create', ['email' => base64_encode('nonexistent@example.com')]));

    $response->assertOk();
    $response->assertSee('nonexistent@example.com');
    $this->assertGuest('users');
});

test('user can authenticate via magic link token', function () {
    $user = User::factory()->create();
    $loginToken = LoginToken::generate($user->email, 'users');

    $response = $this->get(route('website.magic-link.verify', ['token' => $loginToken->token]));

    $this->assertAuthenticated('users');
    $response->assertRedirect(route('website.index'));

    $this->assertDatabaseMissing('login_tokens', [
        'id' => $loginToken->id,
    ]);
});

test('user can authenticate via OTP code', function () {
    $user = User::factory()->create();
    $loginToken = LoginToken::generate($user->email, 'users');

    $response = $this->post(route('website.magic-link-code.store'), [
        'email' => $user->email,
        'code' => $loginToken->code,
    ]);

    $this->assertAuthenticated('users');
    $response->assertRedirect(route('website.index'));

    $this->assertDatabaseMissing('login_tokens', [
        'id' => $loginToken->id,
    ]);
});

test('authentication fails with invalid token', function () {
    $response = $this->get(route('website.magic-link.verify', ['token' => 'invalid-token']));

    $this->assertGuest('users');
    $response->assertRedirect(route('website.magic-link.create'));
    $response->assertSessionHas('error');
});

test('authentication fails with invalid code', function () {
    $user = User::factory()->create();
    LoginToken::generate($user->email, 'users');

    $response = $this->post(route('website.magic-link-code.store'), [
        'email' => $user->email,
        'code' => '000000',
    ]);

    $this->assertGuest('users');
    $response->assertSessionHasErrors('code');
});

test('authentication fails with expired token', function () {
    $user = User::factory()->create();
    $loginToken = LoginToken::generate($user->email, 'users');

    // Expire the token
    $loginToken->update(['expires_at' => now()->subMinutes(1)]);

    $response = $this->get(route('website.magic-link.verify', ['token' => $loginToken->token]));

    $this->assertGuest('users');
    $response->assertRedirect(route('website.magic-link.create'));
});

test('authentication fails with expired code', function () {
    $user = User::factory()->create();
    $loginToken = LoginToken::generate($user->email, 'users');

    // Expire the token
    $loginToken->update(['expires_at' => now()->subMinutes(1)]);

    $response = $this->post(route('website.magic-link-code.store'), [
        'email' => $user->email,
        'code' => $loginToken->code,
    ]);

    $this->assertGuest('users');
    $response->assertSessionHasErrors('code');
});

test('last login timestamp is updated on magic link authentication', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    $loginToken = LoginToken::generate($user->email, 'users');

    $this->get(route('website.magic-link.verify', ['token' => $loginToken->token]));

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});
