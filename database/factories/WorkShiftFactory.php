<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkShift>
 */
class WorkShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Shift '.fake()->unique()->randomLetter(),
            'type' => fake()->randomElement(['SHIFT', 'NON_SHIFT']),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_flexible' => false,
            'flexible_minutes' => 60,
        ];
    }
}
