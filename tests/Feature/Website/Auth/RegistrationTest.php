<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('register screen can be rendered', function () {
    $response = $this->get(route('website.register'));

    $response->assertOk()->assertDontSee('name="birthdate"', false);
});

test('user can register', function () {
    Event::fake();

    $response = $this->post(route('website.register.store'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => fake()->unique()->safeEmail,
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'male',
    ]);

    Event::assertDispatched(Registered::class);

    $this->assertAuthenticated('users');
    expect(auth('users')->user()->birthdate)->toBeNull();
    $response->assertRedirect(route('website.index'));
});
