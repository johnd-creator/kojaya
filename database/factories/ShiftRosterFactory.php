<?php

namespace Database\Factories;

use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftRoster>
 */
class ShiftRosterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'shift_group' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'work_shift_id' => WorkShift::factory()->state(['type' => 'SHIFT']),
            'is_off_day' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
