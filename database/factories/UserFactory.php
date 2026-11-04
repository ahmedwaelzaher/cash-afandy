<?php

namespace Database\Factories;

use App\Actions\Wallets\InitializeWallet;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
        ];
    }

    /**
     * Indicate that the user is verified.
     */
    public function verified(): UserFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => now(),
            ];
        });
    }

    public function completedProfile(): UserFactory
    {
        return $this->state(['gender' => 'male'])->afterCreating(function (User $user) {
            app(InitializeWallet::class)->handle($user, Country::forAccounts()->where('code', setting('default_website_country'))->firstOrFail()->id);
        });
    }

    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): UserFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
