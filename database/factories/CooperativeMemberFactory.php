<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeMember>
 */
class CooperativeMemberFactory extends Factory
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
            'organization_id' => $organization,
            'employee_id' => Employee::factory()->state(['organization_id' => $organization]),
            'user_id' => User::factory()->state(['organization_id' => $organization]),
            'member_no' => fake()->unique()->numerify('MBR-######'),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'identity_number' => fake()->numerify('################'),
            'address' => fake()->address(),
            'joined_at' => now()->subMonths(fake()->numberBetween(1, 24))->toDateString(),
            'resigned_at' => null,
            'status' => fake()->randomElement(['PENDING', 'ACTIVE']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'ACTIVE']);
    }
}
