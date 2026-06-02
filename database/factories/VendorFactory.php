<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => fake()->unique()->numerify('VND-#####'),
            'name' => fake()->company(),
            'status' => fake()->randomElement([VendorStatus::Active, VendorStatus::Inactive]),
            'rating' => fake()->numberBetween(1, 5),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_id' => fake()->numerify('NPWP#############'),
            'address' => fake()->address(),
            'bank_name' => fake()->randomElement(['BCA', 'BRI', 'Mandiri', 'BNI']),
            'bank_account_no' => fake()->bankAccountNumber(),
            'bank_account_name' => fake()->name(),
        ];
    }
}
