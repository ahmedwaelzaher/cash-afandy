<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@email.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'active' => true,
        ]);

        $admin->assignRole('super-admin');
    }
}
