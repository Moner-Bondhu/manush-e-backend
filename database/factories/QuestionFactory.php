<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Scale;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition()
    {
        return [
            'scale_id' => Scale::factory(),
            'text' => $this->faker->sentence(),
            'type' => 'numeric',
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
