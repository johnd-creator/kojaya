<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
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
            'year' => (int) now()->format('Y'),
            'period' => fake()->randomElement(['ANNUAL', 'Q1', 'Q2', 'Q3', 'Q4']),
            'status' => fake()->randomElement(['DRAFT', 'APPROVED', 'LOCKED']),
        ];
    }
}
