<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scale>
 */
class ScaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),                 // example scale name
            'description' => $this->faker->sentence(),       // example description
            'visible_to' => $this->faker->randomElement(['child', 'parent', 'user']),  // who can see this scale
            // add other columns as needed
        ];
    }
}
