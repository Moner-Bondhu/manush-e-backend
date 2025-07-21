<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition()
    {
        return [
            'question_id' => Question::factory(),
            'text' => $this->faker->sentence(),
            'is_image' => false,
            'value' => $this->faker->numberBetween(1, 10),
            'order' => $this->faker->numberBetween(1, 5),
        ];
    }
}
