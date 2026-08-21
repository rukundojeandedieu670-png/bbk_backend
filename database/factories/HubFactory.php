<?php

namespace Database\Factories;

use App\Models\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hub> */
class HubFactory extends Factory
{
    protected $model = Hub::class;

    public function definition(): array
    {
        $name = fake()->city();

        return [
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(1, 9999),
            'district' => fake()->citySuffix(),
            'description' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}