<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeTransfer>
 */
class EmployeeTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employee::factory(),
            'from_organization_id' => \App\Models\Organization::factory(),
            'to_organization_id' => \App\Models\Organization::factory(),
            'effective_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'reason' => fake()->optional()->sentence(),
            'status' => 'PENDING',
            'requested_by' => \App\Models\User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'notes' => fake()->optional()->text(),
        ];
    }
}
