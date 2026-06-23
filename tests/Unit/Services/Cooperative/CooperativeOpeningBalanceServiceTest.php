<?php

namespace Tests\Unit\Services\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\CooperativeMemberOpeningBalanceLine;
use App\Models\Organization;
use App\Services\Cooperative\CooperativeOpeningBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeOpeningBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_legacy_entry_with_member_source_marker(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2020-01-01',
        ]);

        $service = new CooperativeOpeningBalanceService;
        $service->sync($member, 250000);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
            'source_id' => $member->id,
            'credit' => 250000,
        ]);
    }

    public function test_sync_does_not_overwrite_wizard_posted_entry(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2020-01-01',
        ]);

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        // Batch wizard posted dengan ledger OPENING_BALANCE sumber line.
        $batch = CooperativeMemberOpeningBalanceBatch::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'status' => 'POSTED',
            'calculation_start_period' => '2020-01-01',
            'calculation_end_period' => '2020-01-31',
            'months_count' => 1,
            'total_amount' => 100000,
            'source_type' => 'BOARD_DECISION',
        ]);
        $line = CooperativeMemberOpeningBalanceLine::query()->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $wajib->id,
            'category_snapshot' => 'WAJIB',
            'period_start' => '2020-01-01',
            'period_end' => '2020-01-31',
            'months_count' => 1,
            'unit_amount' => 100000,
            'total_amount' => 100000,
            'calculation_method' => 'MANUAL',
        ]);
        $wizardEntry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'cooperative_contribution_type_id' => $wajib->id,
            'category_snapshot' => 'WAJIB',
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $line->id,
            'period' => '2020-01',
            'credit' => 100000,
            'debit' => 0,
            'posted_at' => '2026-06-23',
            'description' => 'Saldo awal dari wizard.',
        ]);

        $service = new CooperativeOpeningBalanceService;
        $service->sync($member, 500000);

        // Ledger wizard TIDAK boleh tertimpa.
        $wizardEntry->refresh();
        $this->assertSame((float) 100000, (float) $wizardEntry->credit);
        $this->assertSame(CooperativeMemberOpeningBalanceLine::class, $wizardEntry->source_type);
        $this->assertSame($line->id, $wizardEntry->source_id);

        // Entry legacy baru tidak boleh tercipta karena wizard sudah POSTED.
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
            'source_id' => $member->id,
        ]);
    }

    public function test_sync_does_not_overwrite_wizard_draft_entry(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2020-01-01',
        ]);

        CooperativeMemberOpeningBalanceBatch::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'status' => 'DRAFT',
            'calculation_start_period' => '2020-01-01',
            'calculation_end_period' => '2020-01-31',
            'months_count' => 1,
            'total_amount' => 100000,
            'source_type' => 'BOARD_DECISION',
        ]);

        $service = new CooperativeOpeningBalanceService;
        $service->sync($member, 500000);

        // Tidak ada entry legacy apapun yang tercipta saat ada DRAFT.
        $this->assertSame(
            0,
            CooperativeLedgerEntry::query()
                ->where('cooperative_member_id', $member->id)
                ->where('source_type', CooperativeMember::class)
                ->count()
        );
    }

    public function test_sync_does_not_touch_legacy_entry_when_wizard_batch_exists(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2020-01-01',
        ]);

        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        // Wizard batch posted untuk POKOK.
        $batch = CooperativeMemberOpeningBalanceBatch::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'status' => 'POSTED',
            'calculation_start_period' => '2020-01-01',
            'calculation_end_period' => '2020-01-31',
            'months_count' => 1,
            'total_amount' => 200000,
            'source_type' => 'BOARD_DECISION',
        ]);
        $line = CooperativeMemberOpeningBalanceLine::query()->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'category_snapshot' => 'POKOK',
            'period_start' => '2020-01-01',
            'period_end' => '2020-01-31',
            'months_count' => 1,
            'unit_amount' => 200000,
            'total_amount' => 200000,
            'calculation_method' => 'ONCE',
        ]);
        $wizardEntry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'category_snapshot' => 'POKOK',
            'cooperative_contribution_type_id' => $pokok->id,
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $line->id,
            'period' => '2020-01',
            'credit' => 200000,
            'debit' => 0,
            'posted_at' => '2026-06-23',
            'description' => 'Saldo awal pokok dari wizard.',
        ]);

        // Entry legacy (ringkasan) yang sudah ada, mis. dibuat oleh proses lama.
        $legacyEntry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
            'source_id' => $member->id,
            'credit' => 75000,
            'debit' => 0,
            'posted_at' => '2020-01-15',
            'description' => 'Legacy ringkasan manual.',
        ]);

        $service = new CooperativeOpeningBalanceService;
        $service->sync($member, 90000);

        // Wizard entry tidak berubah.
        $wizardEntry->refresh();
        $this->assertSame((float) 200000, (float) $wizardEntry->credit);

        // Legacy entry **tidak** boleh ter-update karena wizard sudah POSTED;
        // sync() menolak menulis ulang demi menjaga konsistensi ledger.
        $legacyEntry->refresh();
        $this->assertSame((float) 75000, (float) $legacyEntry->credit);
    }
}
