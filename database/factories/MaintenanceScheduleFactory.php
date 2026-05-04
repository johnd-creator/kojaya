<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceSchedule>
 */
class MaintenanceScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'type' => 'TIME_BASED',
            'frequency' => fake()->randomElement(['DAILY', 'WEEKLY', 'MONTHLY']),
            'interval_value' => fake()->numberBetween(1, 4),
            'maintenance_checklist_id' => null,
            'next_due_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'last_meter_reading' => fake()->randomFloat(2, 0, 1000),
            'target_meter_reading' => fake()->randomFloat(2, 1001, 5000),
            'priority' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'assigned_to' => User::factory(),
            'instructions' => fake()->sentence(),
            'is_active' => true,
            'last_completed_at' => null,
        ];
    }
}
