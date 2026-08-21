<?php

namespace Database\Factories;

use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Story> */
class StoryFactory extends Factory
{
    protected $model = Story::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 9999),
            'author_name' => fake()->name(),
            'body' => fake()->paragraphs(3, true),
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}