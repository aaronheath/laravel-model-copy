<?php

namespace Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\ExampleB;

class ExampleBFactory extends Factory
{
    protected $model = ExampleB::class;

    public function definition()
    {
        return [
            'a' => $this->faker->word(),
            'b' => $this->faker->boolean(),
            'c' => $this->faker->word(),
        ];
    }
}