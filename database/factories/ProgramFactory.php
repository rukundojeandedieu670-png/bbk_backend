<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement(['sport', 'culture', 'entertainment', 'peace_building', 'storytelling']),
            'status' => 'published',
            'summary' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'is_featured' => false,
        ];
    }
}