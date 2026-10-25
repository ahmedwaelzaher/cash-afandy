<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Redot\Notifications\MagicLinkNotification;

test('magic link screen can be rendered', function () {
    $response = $this->get(route('website.magic-link.create'));

    $response->assertOk();
});

test('user can request magic link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('website.magic-link.store'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, MagicLinkNotification::class);

    $response->assertRedirect(route('website.magic-link-code.create', ['email' => base64_encode($user->email)]));

    $this->assertDatabaseHas('login_tokens', [
        'email' => $user->email,
        'guard' => 'users',
    ]);
});

test('magic link request does not reveal a non-existent email', function () {
    Notification::fake();

    $response = $this->post(route('website.magic-link.store'), [
        'email' => 'nonexistent@example.com',
    ]);

    Notification::assertNothingSent();

    $response->assertRedirect(route('website.magic-link-code.create', ['email' => base64_encode('nonexistent@example.com')]));
    $response->assertSessionHasNoErrors();
    $this->assertGuest('users');
    $this->assertDatabaseMissing('login_tokens', ['email' => 'nonexistent@example.com', 'guard' => 'users']);
});

test('login page shows magic link option', function () {
    $response = $this->get(route('website.login'));

    $response->assertOk();
    $response->assertSee(route('website.magic-link.create'));
});
