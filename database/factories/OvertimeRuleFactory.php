<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimeRule>
 */
class OvertimeRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => \App\Models\Organization::factory(),
            'code' => fake()->unique()->numerify('OT#####'),
            'name' => fake()->randomElement(['Weekday Overtime', 'Holiday Overtime', 'Weekend Overtime']),
            'description' => fake()->sentence(),
            'multiplier' => fake()->randomElement([1.5, 2.0, 3.0, 4.0]),
            'min_hours' => 0,
            'max_hours_daily' => 4,
            'max_hours_monthly' => 40,
            'is_weekday' => fake()->boolean(),
            'is_holiday' => fake()->boolean(),
            'requires_approval' => true,
            'is_active' => true,
        ];
    }
}
