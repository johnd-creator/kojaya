<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('POS-###'),
            'name' => fake()->jobTitle(),
            'description' => fake()->sentence(),
            'department_id' => Department::factory(),
            'job_grade_id' => JobGrade::factory(),
        ];
    }
}
