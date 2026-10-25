<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Notification;
use Redot\Notifications\MagicLinkNotification;

test('magic link screen can be rendered', function () {
    $response = $this->get(route('dashboard.magic-link.create'));

    $response->assertOk();
});

test('admin can request magic link', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $response = $this->post(route('dashboard.magic-link.store'), [
        'email' => $admin->email,
    ]);

    Notification::assertSentTo($admin, MagicLinkNotification::class);

    $response->assertRedirect(route('dashboard.magic-link-code.create', ['email' => base64_encode($admin->email)]));

    $this->assertDatabaseHas('login_tokens', [
        'email' => $admin->email,
        'guard' => 'admins',
    ]);
});

test('magic link request does not reveal a non-existent email', function () {
    Notification::fake();

    $response = $this->post(route('dashboard.magic-link.store'), [
        'email' => 'nonexistent@example.com',
    ]);

    Notification::assertNothingSent();

    $response->assertRedirect(route('dashboard.magic-link-code.create', ['email' => base64_encode('nonexistent@example.com')]));
    $response->assertSessionHasNoErrors();
    $this->assertGuest('admins');
    $this->assertDatabaseMissing('login_tokens', ['email' => 'nonexistent@example.com', 'guard' => 'admins']);
});

test('magic link request does not reveal an inactive admin', function () {
    Notification::fake();

    $admin = Admin::factory()->create(['active' => false]);

    $response = $this->post(route('dashboard.magic-link.store'), [
        'email' => $admin->email,
    ]);

    Notification::assertNothingSent();

    $response->assertRedirect(route('dashboard.magic-link-code.create', ['email' => base64_encode($admin->email)]));
    $response->assertSessionHasNoErrors();
    $this->assertGuest('admins');
    $this->assertDatabaseMissing('login_tokens', ['email' => $admin->email, 'guard' => 'admins']);
});

test('login page shows magic link option', function () {
    $response = $this->get(route('dashboard.login'));

    $response->assertOk();
    $response->assertSee(route('dashboard.magic-link.create'));
});
