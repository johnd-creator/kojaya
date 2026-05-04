<?php

namespace Database\Factories;

use App\Models\JobGrade;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_type' => fake()->randomElement(['TKWT', 'Organic']),
            'job_grade_id' => JobGrade::factory(),
            'organization_id' => Organization::factory(),
            'min_tenure_months' => 0,
            'max_tenure_months' => null,
            'effective_from' => now()->startOfMonth()->toDateString(),
            'effective_until' => null,
        ];
    }
}
