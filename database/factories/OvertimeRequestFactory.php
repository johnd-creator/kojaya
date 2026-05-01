<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->time('H:i');
        $endTime = fake()->time('H:i');

        return [
            'employee_id' => \App\Models\Employee::factory(),
            'organization_id' => \App\Models\Organization::factory(),
            'overtime_rule_id' => \App\Models\OvertimeRule::factory(),
            'date' => fake()->date(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'total_hours' => fake()->randomFloat(1, 1, 5),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['PENDING', 'APPROVED', 'REJECTED']),
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }
}
