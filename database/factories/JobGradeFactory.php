<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobGrade>
 */
class JobGradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('JG-##'),
            'name' => 'Grade '.fake()->randomLetter(),
            'level' => fake()->numberBetween(1, 6),
        ];
    }
}
