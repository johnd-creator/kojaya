<?php

namespace Database\Factories;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeMemberOpeningBalanceLine>
 */
class CooperativeMemberOpeningBalanceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subYears(10)->startOfMonth();
        $end = now()->subMonth()->endOfMonth();

        return [
            'opening_balance_batch_id' => CooperativeMemberOpeningBalanceBatch::factory(),
            'cooperative_contribution_type_id' => CooperativeContributionType::factory(),
            'category_snapshot' => 'WAJIB',
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'months_count' => 120,
            'unit_amount' => 50000,
            'total_amount' => 120 * 50000,
            'calculation_method' => 'MONTHLY',
        ];
    }

    public function pokok(int $amount = 200000): static
    {
        return $this->state(fn () => [
            'category_snapshot' => 'POKOK',
            'period_start' => null,
            'period_end' => null,
            'months_count' => 1,
            'unit_amount' => $amount,
            'total_amount' => $amount,
            'calculation_method' => 'ONCE',
        ]);
    }

    public function sukarela(float $amount = 100000): static
    {
        return $this->state(fn () => [
            'category_snapshot' => 'SUKARELA',
            'period_start' => null,
            'period_end' => null,
            'months_count' => 0,
            'unit_amount' => $amount,
            'total_amount' => $amount,
            'calculation_method' => 'MANUAL',
            'override_reason' => 'Saldo sukarela manual dari buku lama.',
        ]);
    }
}
