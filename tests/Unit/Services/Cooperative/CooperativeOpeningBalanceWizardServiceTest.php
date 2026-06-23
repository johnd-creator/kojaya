<?php

namespace Tests\Unit\Services\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Services\Cooperative\CooperativeOpeningBalanceWizardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CooperativeOpeningBalanceWizardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): CooperativeOpeningBalanceWizardService
    {
        return new CooperativeOpeningBalanceWizardService(new \App\Services\AuditLogService);
    }

    public function test_months_between_is_inclusive_of_both_ends(): void
    {
        $service = $this->makeService();

        $this->assertSame(1, $service->monthsBetween('2026-06-01', '2026-06-15'));
        $this->assertSame(12, $service->monthsBetween('2026-06-01', '2027-05-31'));
        $this->assertSame(120, $service->monthsBetween('2016-06-01', '2026-05-31'));
    }

    public function test_months_between_returns_zero_when_end_before_start(): void
    {
        $service = $this->makeService();

        $this->assertSame(0, $service->monthsBetween('2026-06-01', '2026-05-31'));
        $this->assertSame(0, $service->monthsBetween('2026-06-15', '2026-06-01'));
    }

    public function test_months_between_handles_year_boundary(): void
    {
        $service = $this->makeService();

        // Inclusive count: Dec 2025 .. Jan 2027 = 14 bulan
        $this->assertSame(14, $service->monthsBetween('2025-12-15', '2027-01-10'));
    }

    public function test_preview_uses_default_contribution_amounts(): void
    {
        $service = $this->makeService();

        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2016-06-15',
            'joined_at' => '2016-06-15',
        ]);

        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$pokok->id, $wajib->id],
        ]);

        $this->assertSame(120, $preview['months_count']);
        $this->assertCount(2, $preview['lines']);
        $this->assertEquals(6200000.0, $preview['total_amount']);

        $byCategory = collect($preview['lines'])->keyBy('category_snapshot');

        $this->assertSame(200000.0, (float) $byCategory['POKOK']['total_amount']);
        $this->assertSame(6000000.0, (float) $byCategory['WAJIB']['total_amount']);
        $this->assertSame(1, $byCategory['POKOK']['months_count']);
        $this->assertSame(120, $byCategory['WAJIB']['months_count']);
    }

    public function test_preview_respects_override_amount_with_reason(): void
    {
        $service = $this->makeService();

        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2016-06-15',
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2024-01-01',
            'calculation_end_period' => '2024-12-31',
            'contribution_types' => [$wajib->id],
            'overrides' => [
                $wajib->id => [
                    'unit_amount' => 75000,
                    'reason' => 'Tarif 2024 berbeda dari default.',
                ],
            ],
        ]);

        $this->assertSame(12, $preview['months_count']);
        $this->assertCount(1, $preview['lines']);
        $this->assertSame(75000.0, (float) $preview['lines'][0]['unit_amount']);
        $this->assertSame(900000.0, (float) $preview['lines'][0]['total_amount']);
        $this->assertSame('Tarif 2024 berbeda dari default.', $preview['lines'][0]['override_reason']);
    }

    public function test_preview_treats_manual_categories_as_zero_months(): void
    {
        $service = $this->makeService();

        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2024-01-01',
        ]);

        $sukarela = CooperativeContributionType::query()->create([
            'code' => 'SUKARELA',
            'name' => 'Simpanan Sukarela',
            'category' => 'SUKARELA',
            'default_amount' => 150000,
            'frequency' => 'OPEN',
            'is_active' => true,
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2024-01-01',
            'calculation_end_period' => '2024-12-31',
            'contribution_types' => [$sukarela->id],
        ]);

        $this->assertSame(0, $preview['lines'][0]['months_count']);
        $this->assertSame('MANUAL', $preview['lines'][0]['calculation_method']);
        $this->assertSame(150000.0, (float) $preview['lines'][0]['total_amount']);
    }

    public function test_preview_includes_current_month_when_flag_set(): void
    {
        $service = $this->makeService();

        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2026-01-15',
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        Carbon::setTestNow('2026-06-23');

        try {
            $preview = $service->preview($member, [
                'calculation_start_period' => '2026-01-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$wajib->id],
                'include_current_month' => true,
            ]);

            $this->assertSame(6, $preview['months_count']);
            $this->assertSame('2026-06-30', $preview['calculation_end_period']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_preview_skips_unknown_or_inactive_contribution_types(): void
    {
        $service = $this->makeService();

        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
        ]);

        CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => false,
        ]);

        $preview = $service->preview($member, [
            'contribution_types' => [999999],
        ]);

        $this->assertSame([], $preview['lines']);
        $this->assertSame(0.0, $preview['total_amount']);
    }

    public function test_preview_normalizes_period_to_start_and_end_of_month(): void
    {
        $service = $this->makeService();
        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2016-06-15',
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2016-06-15',
            'calculation_end_period' => '2026-05-23',
        ]);

        $this->assertSame('2016-06-01', $preview['calculation_start_period']);
        $this->assertSame('2026-05-31', $preview['calculation_end_period']);
    }

    public function test_preview_detects_legacy_opening_balance_without_category(): void
    {
        $service = $this->makeService();
        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2016-06-15',
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        // Entry legacy tanpa category_snapshot + tanpa contribution_type_id.
        \App\Models\CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => \App\Models\CooperativeMember::class,
            'source_id' => $member->id,
            'credit' => 150000,
            'debit' => 0,
            'posted_at' => '2020-01-15',
            'description' => 'Saldo awal legacy tanpa kategori.',
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$wajib->id],
        ]);

        $this->assertTrue($preview['has_conflicts']);
        $legacyConflict = collect($preview['conflicts'])->firstWhere('is_legacy_uncategorized', true);
        $this->assertNotNull($legacyConflict);
        $this->assertStringContainsString('legacy tanpa kategori', $legacyConflict['message']);
    }

    public function test_preview_detects_entry_with_contribution_type_id_but_null_category_snapshot(): void
    {
        $service = $this->makeService();
        $organization = \App\Models\Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2016-06-15',
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        // Entry SAVING_PAYMENT dengan contribution_type_id tapi tanpa category_snapshot.
        \App\Models\CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $wajib->id,
            'category_snapshot' => null,
            'source_type' => \App\Models\CooperativePayment::class,
            'source_id' => 555,
            'period' => '2024-03',
            'credit' => 50000,
            'debit' => 0,
            'posted_at' => '2024-03-15',
            'description' => 'Pembayaran wajib tanpa category_snapshot.',
        ]);

        $preview = $service->preview($member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$wajib->id],
        ]);

        $this->assertTrue($preview['has_conflicts']);
        $wajibConflict = collect($preview['conflicts'])->firstWhere('entry_type', 'SAVING_PAYMENT');
        $this->assertNotNull($wajibConflict);
        $this->assertSame('WAJIB', $wajibConflict['category']);
        $this->assertTrue($wajibConflict['overlaps_calculation_period']);
    }
}
