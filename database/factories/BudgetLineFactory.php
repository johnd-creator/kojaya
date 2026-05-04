<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetLine>
 */
class BudgetLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allocated = fake()->randomFloat(2, 1000000, 25000000);
        $committed = fake()->randomFloat(2, 0, $allocated / 2);
        $realized = fake()->randomFloat(2, 0, $allocated / 2);

        return [
            'budget_id' => Budget::factory(),
            'cost_center' => fake()->bothify('CC-###'),
            'project_id' => Project::factory(),
            'gl_account' => fake()->numerify('6-####'),
            'category' => fake()->randomElement(['OPEX', 'CAPEX', 'PROJECT']),
            'allocated_amount' => $allocated,
            'committed_amount' => $committed,
            'realized_amount' => $realized,
        ];
    }
}
