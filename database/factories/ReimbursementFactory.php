<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reimbursement>
 */
class ReimbursementFactory extends Factory
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
            'user_id' => User::factory()->state(['organization_id' => $organization]),
            'approver_id' => User::factory()->state(['organization_id' => $organization]),
            'submission_date' => now()->toDateString(),
            'total_amount' => fake()->randomFloat(2, 100000, 5000000),
            'status' => fake()->randomElement(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'PAID']),
            'description' => fake()->sentence(),
            'rejection_reason' => null,
            'payment_date' => null,
        ];
    }
}
