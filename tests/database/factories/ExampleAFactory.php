<?php

namespace Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Models\ExampleA;

class ExampleAFactory extends Factory
{
    protected $model = ExampleA::class;

    public function definition()
    {
        return [
            'a' => $this->faker->word(),
            'b' => $this->faker->boolean(),
            'c' => $this->faker->word(),
        ];
    }
}