<?php

test('user can register', function () {
    $email = fake()->unique()->safeEmail;
    $response = $this->post(route('api.website.register.store'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'male',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['payload' => ['token', 'token_type']]);
    $this->assertDatabaseHas('users', ['email' => $email, 'birthdate' => null]);
});
