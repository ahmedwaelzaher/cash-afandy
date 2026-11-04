<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('website.login'));

    $response->assertOk();
});

test('user can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('website.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('users');
    $response->assertRedirect(route('website.index'));
});

test('user can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('website.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('users');
});

test('authenticated user can logout', function () {
    $this->be(User::factory()->create(), 'users');

    $response = $this->post(route('website.logout'));

    $this->assertGuest('users');
    $response->assertRedirect(route('website.index'));
});
