<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a single test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '01700000000', // Must be unique
            'experiment_tag' => 'onboarding_test',
            'user_type' => 'user',
            'remember_token' => Str::random(10),
        ]);

        // Optionally create more users with factory:
        // User::factory(10)->create();
    }
}
