<?php

namespace Tests\Feature\Security;

use App\Enums\LoanStatus;
use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\LoanService;
use App\Services\Integrations\CooperativeNotificationDispatcher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class LoanWriteOffAuditLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_loan_can_be_written_off_with_authoritative_audit(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->active()->create(['organization_id' => $org->id]);

        $result = app(LoanService::class)->writeOff($loan, $actor, 'Bad debt decision.');

        $this->assertSame(LoanStatus::WrittenOff, $result->status);

        $audit = AuditLog::query()
            ->where('action', 'loan.writeoff.completed')
            ->where('subject_id', (string) $loan->id)
            ->sole();

        $this->assertSame(LoanStatus::Active->value, $audit->old_values['status']);
        $this->assertSame(LoanStatus::WrittenOff->value, $audit->new_values['status']);
        $this->assertSame($org->id, $audit->old_values['organization_id']);
        $this->assertTrue($audit->new_values['note_supplied']);
    }

    public function test_defaulted_loan_can_be_written_off(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->create([
            'status' => LoanStatus::Defaulted,
            'organization_id' => $org->id,
        ]);

        $result = app(LoanService::class)->writeOff($loan, $actor);

        $this->assertSame(LoanStatus::WrittenOff, $result->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.writeoff.completed',
            'subject_id' => (string) $loan->id,
        ]);
    }

    public function test_mandatory_audit_failure_rolls_back_loan_status_notes_and_approval_log(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->active()->create([
            'organization_id' => $org->id,
            'notes' => 'Original notes.',
        ]);
        $originalNotes = $loan->notes;

        $dispatcher = Mockery::mock(CooperativeNotificationDispatcher::class);
        $this->app->instance(CooperativeNotificationDispatcher::class, $dispatcher);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.requested')
            ->andReturn(new AuditLog);
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.completed')
            ->andThrow(new \RuntimeException('simulated mandatory audit failure'));
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.failed')
            ->andReturn(new AuditLog);
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(LoanService::class)->writeOff($loan, $actor, 'Failing note.');
            $this->fail('Expected mandatory audit failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated mandatory audit failure', $e->getMessage());
        }

        $loan->refresh();
        $this->assertSame(LoanStatus::Active, $loan->status);
        $this->assertSame($originalNotes, $loan->notes);
        $this->assertDatabaseMissing('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'to_status' => LoanStatus::WrittenOff->value,
        ]);
    }

    public function test_notification_not_sent_after_rollback(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->active()->create(['organization_id' => $org->id]);

        $dispatcher = Mockery::mock(CooperativeNotificationDispatcher::class);
        $dispatcher->shouldNotReceive('loanWrittenOff');
        $this->app->instance(CooperativeNotificationDispatcher::class, $dispatcher);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.requested')
            ->andReturn(new AuditLog);
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.completed')
            ->andThrow(new \RuntimeException('audit fail'));
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'loan.writeoff.failed')
            ->andReturn(new AuditLog);
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(LoanService::class)->writeOff($loan, $actor);
        } catch (\RuntimeException) {
            // expected
        }
    }

    public function test_paid_off_loan_does_not_emit_completed_and_throws(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->create([
            'status' => LoanStatus::PaidOff,
            'organization_id' => $org->id,
        ]);

        $this->expectException(ValidationException::class);
        app(LoanService::class)->writeOff($loan, $actor);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'loan.writeoff.completed',
            'subject_id' => (string) $loan->id,
        ]);
    }

    public function test_written_off_loan_does_not_emit_completed_again(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->create([
            'status' => LoanStatus::WrittenOff,
            'organization_id' => $org->id,
        ]);

        $this->expectException(ValidationException::class);
        app(LoanService::class)->writeOff($loan, $actor);

        $completedAudits = AuditLog::query()
            ->where('action', 'loan.writeoff.completed')
            ->where('subject_id', (string) $loan->id)
            ->count();

        $this->assertSame(0, $completedAudits);
    }

    public function test_requested_completed_and_failed_events_are_truthful(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $loan = Loan::factory()->active()->create(['organization_id' => $org->id]);

        app(LoanService::class)->writeOff($loan, $actor, 'Truthful test.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.writeoff.requested',
            'subject_id' => (string) $loan->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.writeoff.completed',
            'subject_id' => (string) $loan->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'loan.writeoff.failed',
            'subject_id' => (string) $loan->id,
        ]);
    }
}
