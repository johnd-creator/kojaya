<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrder>
 */
class WorkOrderFactory extends Factory
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
            'asset_id' => Asset::factory()->state(['organization_id' => $organization]),
            'organization_id' => $organization,
            'type' => fake()->randomElement(['CORRECTIVE', 'PREVENTIVE']),
            'priority' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'status' => fake()->randomElement(['OPEN', 'IN_PROGRESS']),
            'description' => fake()->sentence(),
            'assigned_to' => User::factory()->state(['organization_id' => $organization]),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);
    }
}
