<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
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
            'organization_id' => $organization,
            'unit_id' => $organization,
            'requester_id' => User::factory()->state(['organization_id' => $organization]),
            'title' => fake()->sentence(4),
            'cost_center' => fake()->bothify('CC-###'),
            'status' => fake()->randomElement(['DRAFT', 'SUBMITTED', 'APPROVED']),
            'total_amount' => fake()->randomFloat(2, 1000000, 100000000),
            'submitted_at' => now(),
        ];
    }
}
