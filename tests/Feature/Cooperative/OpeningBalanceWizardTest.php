<?php

namespace Tests\Feature\Cooperative;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\CooperativeMemberOpeningBalanceLine;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeOpeningBalanceWizardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class OpeningBalanceWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    private CooperativeMember $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('System Admin');

        $this->organization = Organization::factory()->create();

        $this->member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'tanggal_aktif' => '2016-06-15',
            'joined_at' => '2016-06-15',
        ]);
    }

    private function makePokok(): CooperativeContributionType
    {
        return CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);
    }

    private function makeWajib(): CooperativeContributionType
    {
        return CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
    }

    public function test_create_draft_persists_batch_and_lines(): void
    {
        Carbon::setTestNow('2026-06-23');

        try {
            $pokok = $this->makePokok();
            $wajib = $this->makeWajib();

            $service = app(CooperativeOpeningBalanceWizardService::class);

            $batch = $service->createDraft($this->member, [
                'calculation_start_period' => '2016-06-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$pokok->id, $wajib->id],
                'source_type' => 'MIGRATION_LEDGER',
                'source_reference' => 'REF-MIG-001',
                'source_document_date' => '2026-05-30',
                'notes' => 'Hasil rekonsiliasi Mei 2026.',
            ], $this->admin, $this->organization);

            $this->assertSame(OpeningBalanceBatchStatus::Draft, $batch->status);
            $this->assertSame(120, $batch->months_count);
            $this->assertEquals(6200000.0, (float) $batch->total_amount);
            $this->assertCount(2, $batch->lines);

            $this->assertDatabaseHas('cooperative_member_opening_balance_batches', [
                'id' => $batch->id,
                'cooperative_member_id' => $this->member->id,
                'status' => 'DRAFT',
                'source_type' => 'MIGRATION_LEDGER',
                'source_reference' => 'REF-MIG-001',
            ]);

            $this->assertDatabaseHas('cooperative_member_opening_balance_lines', [
                'opening_balance_batch_id' => $batch->id,
                'category_snapshot' => 'POKOK',
                'calculation_method' => 'ONCE',
                'total_amount' => '200000.00',
            ]);

            $this->assertDatabaseHas('cooperative_member_opening_balance_lines', [
                'opening_balance_batch_id' => $batch->id,
                'category_snapshot' => 'WAJIB',
                'calculation_method' => 'MONTHLY',
                'months_count' => 120,
                'total_amount' => '6000000.00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_post_creates_openinig_balance_ledger_entries_per_line(): void
    {
        Carbon::setTestNow('2026-06-23');

        try {
            $pokok = $this->makePokok();
            $wajib = $this->makeWajib();

            $service = app(CooperativeOpeningBalanceWizardService::class);
            $batch = $service->createDraft($this->member, [
                'calculation_start_period' => '2016-06-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$pokok->id, $wajib->id],
                'source_type' => 'BOARD_DECISION',
            ], $this->admin, $this->organization);

            $posted = $service->post($batch->fresh(), $this->admin);

            $this->assertSame(OpeningBalanceBatchStatus::Posted, $posted->status);
            $this->assertNotNull($posted->posted_at);
            $this->assertSame($this->admin->id, $posted->posted_by);

            $entries = CooperativeLedgerEntry::query()
                ->where('cooperative_member_id', $this->member->id)
                ->where('entry_type', 'OPENING_BALANCE')
                ->where('source_type', CooperativeMemberOpeningBalanceLine::class)
                ->whereIn('source_id', $batch->fresh()->lines->pluck('id'))
                ->get();

            $this->assertCount(2, $entries);

            $byCategory = $entries->keyBy('category_snapshot');
            $this->assertEquals(200000.0, (float) $byCategory['POKOK']->credit);
            $this->assertEquals(0.0, (float) $byCategory['POKOK']->debit);
            $this->assertEquals(6000000.0, (float) $byCategory['WAJIB']->credit);
            $this->assertSame('SAVINGS', $byCategory['POKOK']->ledger_scope);
            $this->assertSame('2016-06', $byCategory['WAJIB']->period);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_post_refuses_duplicate_pokok_when_no_void_yet(): void
    {
        $pokok = $this->makePokok();

        // Simulasikan state edge case: ada entry OPENING_BALANCE POKOK untuk
        // anggota ini tanpa reversal (mis. hasil migrasi manual lama), tanpa
        // melewati alur normal createDraft/post.
        $existingLine = CooperativeMemberOpeningBalanceLine::factory()
            ->pokok(200000)
            ->create([
                'opening_balance_batch_id' => CooperativeMemberOpeningBalanceBatch::factory()->create([
                    'cooperative_member_id' => $this->member->id,
                    'organization_id' => $this->organization->id,
                    'status' => OpeningBalanceBatchStatus::Posted,
                ])->id,
            ]);

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'cooperative_contribution_type_id' => $pokok->id,
            'category_snapshot' => 'POKOK',
            'source_type' => CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $existingLine->id,
            'debit' => 0,
            'credit' => 200000,
            'posted_at' => now()->toDateString(),
            'description' => 'Saldo awal POKOK legacy.',
        ]);

        $service = app(CooperativeOpeningBalanceWizardService::class);
        // Paksa anggota fresh dengan menonaktifkan activeOpeningBalanceBatch check
        // sementara agar kita bisa langsung membuat draft kedua.
        $this->member->forceFill([
            'tanggal_aktif' => '2018-01-01',
        ])->save();

        // Batch yang sudah ada berstatus POSTED akan memblokir createDraft
        // (assertMemberEligible). Untuk pengujian ini, kita bypass dengan
        // membuat batch draft lewat factory.
        $second = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'status' => OpeningBalanceBatchStatus::Draft,
        ]);
        CooperativeMemberOpeningBalanceLine::factory()->pokok(150000)->create([
            'opening_balance_batch_id' => $second->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Simpanan pokok');

        $service->post($second->fresh(), $this->admin);
    }

    public function test_post_allows_pokok_again_after_full_reversal(): void
    {
        $pokok = $this->makePokok();
        $service = app(CooperativeOpeningBalanceWizardService::class);

        $first = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);
        $service->post($first->fresh(), $this->admin);
        $service->void($first->fresh(), $this->admin, 'Salah nominal');

        $second = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);
        $postedSecond = $service->post($second->fresh(), $this->admin);

        $this->assertSame(OpeningBalanceBatchStatus::Posted, $postedSecond->status);
    }

    public function test_void_creates_reversal_entries_and_returns_balance(): void
    {
        $pokok = $this->makePokok();
        $wajib = $this->makeWajib();

        $service = app(CooperativeOpeningBalanceWizardService::class);
        $batch = $service->createDraft($this->member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$pokok->id, $wajib->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        $service->post($batch->fresh(), $this->admin);

        $voided = $service->void($batch->fresh(), $this->admin, 'Salah periode awal.');

        $this->assertSame(OpeningBalanceBatchStatus::Voided, $voided->status);
        $this->assertSame($this->admin->id, $voided->voided_by);
        $this->assertSame('Salah periode awal.', $voided->void_reason);

        $reversals = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $this->member->id)
            ->where('entry_type', 'OPENING_BALANCE_REVERSAL')
            ->whereIn('source_id', $batch->lines->pluck('id'))
            ->get();

        $this->assertCount(2, $reversals);
        $byCategory = $reversals->keyBy('category_snapshot');
        $this->assertEquals(200000.0, (float) $byCategory['POKOK']->debit);
        $this->assertEquals(0.0, (float) $byCategory['POKOK']->credit);
        $this->assertEquals(6000000.0, (float) $byCategory['WAJIB']->debit);

        // Net saldo simpanan harus kembali ke 0 setelah reversal.
        $netBalance = (float) CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $this->member->id)
            ->where('ledger_scope', 'SAVINGS')
            ->sum(\DB::raw('credit - debit'));

        $this->assertEquals(0.0, $netBalance);
    }

    public function test_create_draft_rejects_resigned_member(): void
    {
        $pokok = $this->makePokok();

        $this->member->forceFill([
            'status' => 'RESIGNED',
            'resigned_at' => '2024-01-01',
        ])->save();

        $service = app(CooperativeOpeningBalanceWizardService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RESIGNED');

        $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
        ], $this->admin, $this->organization);
    }

    public function test_create_draft_rejects_when_posted_batch_already_exists(): void
    {
        $pokok = $this->makePokok();

        $service = app(CooperativeOpeningBalanceWizardService::class);
        $first = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);
        $service->post($first->fresh(), $this->admin);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah memiliki batch saldo awal');

        $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);
    }

    public function test_savings_summary_includes_opening_balance_after_posting(): void
    {
        $pokok = $this->makePokok();
        $wajib = $this->makeWajib();

        $service = app(CooperativeOpeningBalanceWizardService::class);
        $batch = $service->createDraft($this->member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$pokok->id, $wajib->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);
        $service->post($batch->fresh(), $this->admin);

        $summary = app(\App\Services\Cooperative\SavingsSummaryService::class)
            ->summary($this->member->fresh());

        $this->assertEquals(200000.0, $summary['by_category']['POKOK']);
        $this->assertEquals(6000000.0, $summary['by_category']['WAJIB']);
        $this->assertEquals(6200000.0, $summary['total_balance']);
    }

    public function test_member_relations_and_active_batch_helper(): void
    {
        $pokok = $this->makePokok();

        $service = app(CooperativeOpeningBalanceWizardService::class);
        $batch = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        $service->post($batch->fresh(), $this->admin);

        $this->assertCount(1, $this->member->openingBalanceBatches);
        $this->assertSame($batch->id, $this->member->activeOpeningBalanceBatch()?->id);
        $this->assertInstanceOf(CooperativeMemberOpeningBalanceLine::class, $batch->lines->first());
        $this->assertSame($pokok->id, $batch->lines->first()->cooperative_contribution_type_id);
    }

    public function test_post_marks_first_savings_paid_at_when_null(): void
    {
        $pokok = $this->makePokok();
        $service = app(CooperativeOpeningBalanceWizardService::class);

        $this->assertDatabaseMissing('member_onboarding_progress', [
            'cooperative_member_id' => $this->member->id,
        ]);

        $batch = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        Carbon::setTestNow('2026-06-23 10:00:00');
        try {
            $service->post($batch->fresh(), $this->admin);
        } finally {
            Carbon::setTestNow();
        }

        $progress = \App\Models\MemberOnboardingProgress::query()
            ->where('cooperative_member_id', $this->member->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertNotNull($progress->first_savings_paid_at);
    }

    public function test_post_does_not_overwrite_existing_first_savings_paid_at(): void
    {
        $pokok = $this->makePokok();
        $service = app(CooperativeOpeningBalanceWizardService::class);

        $existing = \App\Models\MemberOnboardingProgress::query()->create([
            'cooperative_member_id' => $this->member->id,
            'first_savings_paid_at' => '2026-01-15 09:00:00',
        ]);

        $batch = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        Carbon::setTestNow('2026-06-23 10:00:00');
        try {
            $service->post($batch->fresh(), $this->admin);
        } finally {
            Carbon::setTestNow();
        }

        $progress = $existing->fresh();
        $this->assertSame('2026-01-15 09:00:00', $progress->first_savings_paid_at->toDateTimeString());
    }

    public function test_preview_reports_conflicts_when_saving_payment_already_exists(): void
    {
        $pokok = $this->makePokok();
        $wajib = $this->makeWajib();

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $wajib->id,
            'category_snapshot' => 'WAJIB',
            'source_type' => CooperativePayment::class,
            'source_id' => 1,
            'period' => '2024-03',
            'credit' => 50000,
            'debit' => 0,
            'posted_at' => '2024-03-15',
            'description' => 'Pembayaran wajib manual.',
        ]);

        $service = app(CooperativeOpeningBalanceWizardService::class);

        $preview = $service->preview($this->member, [
            'calculation_start_period' => '2016-06-01',
            'calculation_end_period' => '2026-05-31',
            'contribution_types' => [$pokok->id, $wajib->id],
        ]);

        $this->assertTrue($preview['has_conflicts']);
        $this->assertNotEmpty($preview['conflicts']);

        $wajibConflict = collect($preview['conflicts'])->firstWhere('category', 'WAJIB');
        $this->assertNotNull($wajibConflict);
        $this->assertTrue($wajibConflict['overlaps_calculation_period']);
        $this->assertSame('2024-03', $wajibConflict['period']);

        $pokokConflict = collect($preview['conflicts'])->firstWhere('category', 'POKOK');
        $this->assertNull($pokokConflict);
    }

    public function test_draft_and_post_write_audit_log_entries(): void
    {
        $pokok = $this->makePokok();
        $service = app(CooperativeOpeningBalanceWizardService::class);

        $batch = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'opening_balance.draft_created',
            'subject_type' => CooperativeMemberOpeningBalanceBatch::class,
            'subject_id' => $batch->id,
        ]);

        $service->post($batch->fresh(), $this->admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'opening_balance.posted',
            'subject_type' => CooperativeMemberOpeningBalanceBatch::class,
            'subject_id' => $batch->id,
        ]);
    }

    public function test_void_writes_audit_log_entry(): void
    {
        $pokok = $this->makePokok();
        $service = app(CooperativeOpeningBalanceWizardService::class);

        $batch = $service->createDraft($this->member, [
            'contribution_types' => [$pokok->id],
            'source_type' => 'BOARD_DECISION',
        ], $this->admin, $this->organization);

        $service->post($batch->fresh(), $this->admin);
        $service->void($batch->fresh(), $this->admin, 'Salah nominal migrasi.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'opening_balance.voided',
            'subject_type' => CooperativeMemberOpeningBalanceBatch::class,
            'subject_id' => $batch->id,
        ]);
    }
}
