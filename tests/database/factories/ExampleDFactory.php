<?php

namespace Tests\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;
use Tests\Models\ExampleD;

class ExampleDFactory extends Factory
{
    protected $model = ExampleD::class;

    public function definition()
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'a' => $this->faker->word(),
            'b' => $this->faker->boolean(),
            'c' => $this->faker->word(),
            'created_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}