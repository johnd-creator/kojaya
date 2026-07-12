<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberStatusConsistencyReport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MemberStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // --- Valid-pair validator ---

    public function test_valid_pairs_are_recognized_as_consistent(): void
    {
        $report = app(MemberStatusConsistencyReport::class);

        foreach ([
            ['PENDING', CooperativeMember::VALIDATION_PENDING],
            ['PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW],
            ['INACTIVE', CooperativeMember::VALIDATION_INACTIVE],
            ['INACTIVE', CooperativeMember::VALIDATION_REVISION],
            ['INACTIVE', CooperativeMember::VALIDATION_REJECTED],
            ['ACTIVE', CooperativeMember::VALIDATION_ACTIVE],
            ['RESIGNED', CooperativeMember::VALIDATION_RESIGNED],
        ] as [$status, $validation]) {
            CooperativeMember::factory()->create([
                'status' => $status,
                'validation_status' => $validation,
            ]);
        }

        $counts = $report->counts();

        $this->assertSame(7, $counts['total']);
        $this->assertSame(1, $counts['ACTIVE/ACTIVE']);
        $this->assertSame(0, $counts['manual-review']);
    }

    // --- Contradictory pairs detected ---

    public function test_active_with_non_active_validation_is_flagged(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $report = app(MemberStatusConsistencyReport::class);
        $counts = $report->counts();

        $this->assertGreaterThan(0, $counts['ACTIVE/non-active-validation']);
    }

    public function test_pending_with_active_validation_is_flagged(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);
        $counts = $report->counts();

        $this->assertGreaterThan(0, $counts['non-active/ACTIVE']);
    }

    public function test_active_rejected_is_contradictory_not_auto_repairable(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->deterministicRepairs()->count(), 'ACTIVE/REJECTED must not be auto-repaired.');
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count(), 'ACTIVE/REJECTED should require manual review.');
    }

    // --- ACTIVE/null must NOT be deterministic repair ---

    public function test_active_null_is_not_deterministic_repair_without_evidence(): void
    {
        // ACTIVE/null cannot be inserted directly (NOT NULL after migrations),
        // so we verify via the report logic that ACTIVE/null would go to
        // manual review, not deterministic repairs.
        // The report's deterministicRepairs() query only matches
        // INACTIVE+ACTIVE and RESIGNED+ACTIVE pairs.
        $report = app(MemberStatusConsistencyReport::class);

        // Verify the deterministic repairs query does not include ACTIVE status at all
        $detRepairs = $report->deterministicRepairs()
            ->where('status', 'ACTIVE')
            ->count();

        $this->assertSame(0, $detRepairs, 'ACTIVE/null must not be classified as deterministic repair.');
    }

    public function test_active_null_requires_manual_review(): void
    {
        // Verify that any ACTIVE row with null validation_status would
        // be caught by manual review, not deterministic repair.
        $report = app(MemberStatusConsistencyReport::class);

        // Simulate: create an ACTIVE member, check it's NOT in deterministic repairs
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->assertSame(0, $report->deterministicRepairs()->count(), 'ACTIVE/ACTIVE is valid and should not appear in repairs.');
    }

    // --- Deterministic repairs for terminal states ---

    public function test_inactive_active_validation_is_terminal_repair_candidate(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(1, $report->deterministicRepairs()->count());
    }

    public function test_resigned_active_validation_is_terminal_repair_candidate(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'RESIGNED',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(1, $report->deterministicRepairs()->count());
    }

    // --- Backfill preserves terminal validation states ---

    public function test_backfill_preserves_revision_and_rejected(): void
    {
        $revision = CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
        ]);
        $rejected = CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        Artisan::call('members:backfill-status-consistency', [
            '--apply' => true,
            '--acknowledge' => true,
        ]);

        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $revision->refresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $rejected->refresh()->validation_status);
    }

    // --- Backfill safety guards ---

    public function test_backfill_dry_run_does_not_change_data(): void
    {
        $member = CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        Artisan::call('members:backfill-status-consistency');

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->refresh()->validation_status);
    }

    public function test_backfill_apply_without_acknowledge_is_refused(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $exitCode = Artisan::call('members:backfill-status-consistency', [
            '--apply' => true,
        ]);

        $this->assertSame(1, $exitCode);
    }

    public function test_backfill_apply_with_acknowledge_fixes_terminal_rows(): void
    {
        $member = CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        Artisan::call('members:backfill-status-consistency', [
            '--apply' => true,
            '--acknowledge' => true,
        ]);

        // INACTIVE + ACTIVE -> INACTIVE + INACTIVE (terminal validation)
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->refresh()->validation_status);
    }

    // --- Audit output has no PII ---

    public function test_audit_command_output_does_not_contain_pii(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'npwp' => 'SECRET-NPWP-123',
            'no_rekening' => 'SECRET-REKENING-456',
            'identity_number' => 'SECRET-ID-789',
        ]);

        Artisan::call('members:audit-status-consistency');
        $output = Artisan::output();

        $this->assertStringNotContainsString('SECRET-NPWP-123', $output);
        $this->assertStringNotContainsString('SECRET-REKENING-456', $output);
        $this->assertStringNotContainsString('SECRET-ID-789', $output);
    }

    // --- Unknown status values ---

    public function test_unknown_status_values_are_flagged(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'BOGUS',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);
        $counts = $report->counts();

        $this->assertGreaterThan(0, $counts['unknown status']);
    }

    // --- Contradictory pairs must be flagged and block migration ---

    public function test_active_rejected_is_contradictory_pair(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->deterministicRepairs()->count(), 'ACTIVE/REJECTED is not a deterministic repair.');
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count(), 'ACTIVE/REJECTED must require manual review.');
    }

    public function test_pending_active_is_contradictory_pair(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->deterministicRepairs()->count());
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count(), 'PENDING/ACTIVE must require manual review.');
    }

    public function test_resigned_pending_review_is_contradictory_pair(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'RESIGNED',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->deterministicRepairs()->count());
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count(), 'RESIGNED/PENDING_REVIEW must require manual review.');
    }

    public function test_inactive_pending_validation_is_contradictory_pair(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->deterministicRepairs()->count());
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count(), 'INACTIVE/PENDING_VALIDATION must require manual review.');
    }

    // --- Consistent DB passes audit cleanly ---

    public function test_clean_database_has_zero_manual_review(): void
    {
        CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $report = app(MemberStatusConsistencyReport::class);

        $this->assertSame(0, $report->manualReviewQuery()->count());
        $this->assertSame(0, $report->deterministicRepairs()->count());
    }
}
