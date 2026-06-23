<?php

namespace Database\Factories;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeMemberOpeningBalanceBatch>
 */
class CooperativeMemberOpeningBalanceBatchFactory extends Factory
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
            'cooperative_member_id' => CooperativeMember::factory(),
            'organization_id' => Organization::factory(),
            'status' => OpeningBalanceBatchStatus::Draft,
            'calculation_start_period' => $start->toDateString(),
            'calculation_end_period' => $end->toDateString(),
            'months_count' => 120,
            'total_amount' => 0,
            'source_type' => 'MIGRATION_LEDGER',
            'source_reference' => 'REF-'.fake()->bothify('###??##'),
            'source_document_date' => now()->subDays(7)->toDateString(),
            'notes' => 'Migrasi dari buku besar lama.',
        ];
    }

    public function posted(?User $poster = null): static
    {
        return $this->state(fn () => [
            'status' => OpeningBalanceBatchStatus::Posted,
            'posted_by' => $poster?->id,
            'posted_at' => now(),
        ]);
    }

    public function voided(?User $voider = null, string $reason = 'Salah periode'): static
    {
        return $this->state(fn () => [
            'status' => OpeningBalanceBatchStatus::Voided,
            'voided_by' => $voider?->id,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }
}
