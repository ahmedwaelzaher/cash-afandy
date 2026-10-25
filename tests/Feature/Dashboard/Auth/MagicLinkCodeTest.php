<?php

use App\Models\Admin;
use Redot\Models\LoginToken;

test('code entry screen can be rendered with valid token', function () {
    $admin = Admin::factory()->create();
    LoginToken::generate($admin->email, 'admins');

    $response = $this->get(route('dashboard.magic-link-code.create', ['email' => base64_encode($admin->email)]));

    $response->assertOk();
    $response->assertSee($admin->email);
});

test('code entry screen redirects without email', function () {
    $response = $this->get(route('dashboard.magic-link-code.create'));

    $response->assertRedirect(route('dashboard.magic-link.create'));
});

test('code entry screen does not reveal whether an email has a valid token', function () {
    $response = $this->get(route('dashboard.magic-link-code.create', ['email' => base64_encode('nonexistent@example.com')]));

    $response->assertOk();
    $response->assertSee('nonexistent@example.com');
    $this->assertGuest('admins');
});

test('admin can authenticate via magic link token', function () {
    $admin = Admin::factory()->create();
    $loginToken = LoginToken::generate($admin->email, 'admins');

    $response = $this->get(route('dashboard.magic-link.verify', ['token' => $loginToken->token]));

    $this->assertAuthenticated('admins');
    $response->assertRedirect(route('dashboard.index'));

    $this->assertDatabaseMissing('login_tokens', [
        'id' => $loginToken->id,
    ]);
});

test('admin can authenticate via OTP code', function () {
    $admin = Admin::factory()->create();
    $loginToken = LoginToken::generate($admin->email, 'admins');

    $response = $this->post(route('dashboard.magic-link-code.store'), [
        'email' => $admin->email,
        'code' => $loginToken->code,
    ]);

    $this->assertAuthenticated('admins');
    $response->assertRedirect(route('dashboard.index'));

    $this->assertDatabaseMissing('login_tokens', [
        'id' => $loginToken->id,
    ]);
});

test('authentication fails with invalid token', function () {
    $response = $this->get(route('dashboard.magic-link.verify', ['token' => 'invalid-token']));

    $this->assertGuest('admins');
    $response->assertRedirect(route('dashboard.magic-link.create'));
    $response->assertSessionHas('error');
});

test('authentication fails with invalid code', function () {
    $admin = Admin::factory()->create();
    LoginToken::generate($admin->email, 'admins');

    $response = $this->post(route('dashboard.magic-link-code.store'), [
        'email' => $admin->email,
        'code' => '000000',
    ]);

    $this->assertGuest('admins');
    $response->assertSessionHasErrors('code');
});

test('authentication fails with expired token', function () {
    $admin = Admin::factory()->create();
    $loginToken = LoginToken::generate($admin->email, 'admins');

    // Expire the token
    $loginToken->update(['expires_at' => now()->subMinutes(1)]);

    $response = $this->get(route('dashboard.magic-link.verify', ['token' => $loginToken->token]));

    $this->assertGuest('admins');
    $response->assertRedirect(route('dashboard.magic-link.create'));
});

test('authentication fails with expired code', function () {
    $admin = Admin::factory()->create();
    $loginToken = LoginToken::generate($admin->email, 'admins');

    // Expire the token
    $loginToken->update(['expires_at' => now()->subMinutes(1)]);

    $response = $this->post(route('dashboard.magic-link-code.store'), [
        'email' => $admin->email,
        'code' => $loginToken->code,
    ]);

    $this->assertGuest('admins');
    $response->assertSessionHasErrors('code');
});

test('authentication fails for inactive admin via token', function () {
    $admin = Admin::factory()->create(['active' => false]);
    $loginToken = LoginToken::generate($admin->email, 'admins');

    $response = $this->get(route('dashboard.magic-link.verify', ['token' => $loginToken->token]));

    $this->assertGuest('admins');
    $response->assertRedirect(route('dashboard.magic-link.create'));
    $response->assertSessionHas('error');
});

test('last login timestamp is updated on magic link authentication', function () {
    $admin = Admin::factory()->create(['last_login_at' => null]);
    $loginToken = LoginToken::generate($admin->email, 'admins');

    $this->get(route('dashboard.magic-link.verify', ['token' => $loginToken->token]));

    $admin->refresh();
    expect($admin->last_login_at)->not->toBeNull();
});
