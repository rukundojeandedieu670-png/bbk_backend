<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Partner> */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'website_url' => fake()->url(),
            'partner_type' => fake()->randomElement(['funder', 'implementing_partner', 'local_partner']),
            'description' => fake()->sentence(),
        ];
    }
}