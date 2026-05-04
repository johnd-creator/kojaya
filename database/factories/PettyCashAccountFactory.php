<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PettyCashAccount>
 */
class PettyCashAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'balance' => fake()->randomFloat(2, 500000, 5000000),
            'limit' => fake()->randomFloat(2, 1000000, 10000000),
            'status' => fake()->randomElement(['ACTIVE', 'INACTIVE']),
            'description' => fake()->sentence(),
        ];
    }
}
