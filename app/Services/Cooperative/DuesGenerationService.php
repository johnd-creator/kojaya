<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use Carbon\CarbonImmutable;

class DuesGenerationService
{
    public function __construct(private readonly CooperativePeriodLockService $periodLockService) {}

    public function generateForPeriod(string $period): int
    {
        $this->periodLockService->assertUnlocked($period);

        $periodDate = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
        $created = 0;

        $types = CooperativeContributionType::query()
            ->where('is_active', true)
            ->whereIn('frequency', ['MONTHLY', 'ONCE'])
            ->get();

        CooperativeMember::query()
            ->active()
            ->orderBy('id')
            ->chunkById(100, function ($members) use ($period, $periodDate, $types, &$created): void {
                foreach ($members as $member) {
                    foreach ($types as $type) {
                        if ($type->frequency === 'ONCE' && $this->hasPreviousInvoice($member->id, $type->id)) {
                            continue;
                        }

                        $invoice = CooperativeDuesInvoice::query()->firstOrCreate(
                            [
                                'cooperative_member_id' => $member->id,
                                'cooperative_contribution_type_id' => $type->id,
                                'period' => $period,
                            ],
                            [
                                'amount' => $type->default_amount,
                                'paid_amount' => 0,
                                'due_date' => $periodDate->day(10)->toDateString(),
                                'status' => 'UNPAID',
                            ],
                        );

                        if ($invoice->wasRecentlyCreated) {
                            $created++;
                        }
                    }
                }
            });

        return $created;
    }

    public function ensureOneTimeInvoice(CooperativeMember $member, string $code = 'POKOK'): ?CooperativeDuesInvoice
    {
        $type = CooperativeContributionType::query()
            ->where('code', $code)
            ->where('frequency', 'ONCE')
            ->where('is_active', true)
            ->first();

        if (! $type) {
            return null;
        }

        if ($this->hasPreviousInvoice($member->id, $type->id)) {
            return CooperativeDuesInvoice::query()
                ->where('cooperative_member_id', $member->id)
                ->where('cooperative_contribution_type_id', $type->id)
                ->oldest('id')
                ->first();
        }

        $periodDate = CarbonImmutable::parse($member->joined_at ?: now())->startOfMonth();
        $period = $periodDate->format('Y-m');

        $this->periodLockService->assertUnlocked($period);

        return CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => $period,
            'amount' => $type->default_amount,
            'paid_amount' => 0,
            'due_date' => $periodDate->day(10)->toDateString(),
            'status' => 'UNPAID',
        ]);
    }

    private function hasPreviousInvoice(int $memberId, int $typeId): bool
    {
        return CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $memberId)
            ->where('cooperative_contribution_type_id', $typeId)
            ->exists();
    }
}
