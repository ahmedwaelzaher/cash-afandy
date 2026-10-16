<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the admin is verified.
     */
    public function verified(): AdminFactory
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => now()]);
    }

    /**
     * Indicate that the admin is unverified.
     */
    public function unverified(): AdminFactory
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }
}
