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

    private function hasPreviousInvoice(int $memberId, int $typeId): bool
    {
        return CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $memberId)
            ->where('cooperative_contribution_type_id', $typeId)
            ->exists();
    }
}
