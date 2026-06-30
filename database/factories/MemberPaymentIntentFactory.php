<?php

namespace Database\Factories;

use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberPaymentIntent>
 */
class MemberPaymentIntentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cooperative_member_id' => CooperativeMember::factory(),
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $this->faker->numberBetween(1, 1000),
            'amount' => $this->faker->numberBetween(25000, 250000),
            'channel' => 'QRIS',
            'gateway_provider' => 'internal',
            'gateway_reference' => 'PAY-'.$this->faker->unique()->numerify('########'),
            'gateway_status' => 'PENDING',
        ];
    }
}
