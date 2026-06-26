<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use Database\Seeders\AnggotaSeeder;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoMemberSeederTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_demo_members_are_active_approved_and_have_complete_mandatory_dues_history(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CooperativeSeeder::class);
        $this->seed(AnggotaSeeder::class);

        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();
        $member = CooperativeMember::query()->where('no_anggota', '001')->firstOrFail();

        $this->assertSame('ACTIVE', $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->validation_status);
        $this->assertSame('2025-01-01', $member->tanggal_aktif?->toDateString());
        $this->assertSame(0, CooperativeMember::query()
            ->whereIn('validation_status', [
                CooperativeMember::VALIDATION_PENDING,
                CooperativeMember::VALIDATION_PENDING_REVIEW,
                CooperativeMember::VALIDATION_REVISION,
            ])
            ->count());

        $wajibPeriods = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->orderBy('period')
            ->pluck('period')
            ->all();

        $this->assertSame($this->periodsBetween('2025-01', '2026-06'), $wajibPeriods);
        $this->assertSame(1, CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->where('cooperative_contribution_type_id', $pokok->id)
            ->where('amount', 200000)
            ->where('status', 'PAID')
            ->count());
        $this->assertGreaterThan(0, CooperativeDuesInvoice::query()
            ->whereHas('member', fn ($query) => $query->active())
            ->where('cooperative_contribution_type_id', $wajib->id)
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->count());
    }

    /**
     * @return array<int, string>
     */
    private function periodsBetween(string $start, string $end): array
    {
        $periods = [];
        $current = Carbon::createFromFormat('Y-m', $start)->startOfMonth();
        $last = Carbon::createFromFormat('Y-m', $end)->startOfMonth();

        while ($current->lessThanOrEqualTo($last)) {
            $periods[] = $current->format('Y-m');
            $current = $current->addMonth();
        }

        return $periods;
    }
}
