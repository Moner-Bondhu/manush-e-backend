<?php

namespace Database\Factories;

use App\Models\Response;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Response>
 */
class ResponseFactory extends Factory
{
    protected $model = Response::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
        public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'question_id' => Question::factory(),
            'option_id' => Option::factory(),
            'text_answer' => $this->faker->sentence(),
            'numeric_answer' => $this->faker->randomDigitNotNull(),
            // no 'value' field here
        ];
    }
}
