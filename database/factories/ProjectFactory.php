<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'id' => fake()->uuid(),
            'project_code' => fake()->unique()->numerify('PRJ-#####'),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'organization_id' => $organization,
            'client_id' => Client::factory()->state(['organization_id' => $organization]),
            'start_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'end_date' => now()->addDays(fake()->numberBetween(30, 120))->toDateString(),
            'budget' => fake()->randomFloat(2, 1000000, 50000000),
            'actual_cost' => fake()->randomFloat(2, 100000, 10000000),
            'status' => fake()->randomElement(['PLANNING', 'ONGOING', 'ON_HOLD']),
            'progress_percentage' => fake()->numberBetween(0, 95),
            'notes' => fake()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
            'progress_percentage' => 100,
        ]);
    }
}
