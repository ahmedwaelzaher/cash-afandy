<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Memo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memo>
 */
class MemoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'title' => $this->faker->sentence,
            'icon' => 'ti ti-note',
            'date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'content' => $this->faker->paragraph,
        ];
    }
}
