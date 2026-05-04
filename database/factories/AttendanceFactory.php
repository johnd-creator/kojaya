<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
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
            'employee_id' => Employee::factory()->state(['organization_id' => $organization]),
            'organization_id' => $organization,
            'date' => fake()->date(),
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => fake()->randomElement(['PRESENT', 'ABSENT', 'SICK', 'LEAVE', 'OFF']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
