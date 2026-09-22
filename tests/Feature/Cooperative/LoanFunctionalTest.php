<?php

namespace Tests\Feature\Cooperative;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\ApprovalLog;
use App\Models\AuditLog;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanFunctionalTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_loan001_member_api_applies_with_bounds_and_rejects_out_of_range_without_mutation(): void
    {
        $organization = Organization::factory()->create();
        $memberUser = $this->user('Anggota', $organization);
        $member = $this->member($organization, $memberUser);
        $maxMemberUser = $this->user('Anggota', $organization);
        $maxMember = $this->member($organization, $maxMemberUser);
        $overLimitMemberUser = $this->user('Anggota', $organization);
        $overLimitMember = $this->member($organization, $overLimitMemberUser);
        $loanType = $this->loanType(['min_amount' => 500000, 'max_amount' => 2000000]);
        $payload = [
            'loan_type_id' => $loanType->id,
            'term_months' => 3,
            'first_due_date' => now()->addMonth()->toDateString(),
        ];

        Sanctum::actingAs($memberUser, ['member:read', 'member:write']);

        $this->postJson('/api/v1/member/loans', [
            ...$payload,
            'principal_amount' => 500000,
        ])->assertCreated()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.loan_type_id', $loanType->id)
            ->assertJsonPath('data.principal_amount', 500000)
            ->assertJsonPath('data.interest_rate', (float) $loanType->interest_rate)
            ->assertJsonPath('data.status', LoanStatus::Applied->value);

        $loan = Loan::query()->where('cooperative_member_id', $member->id)->firstOrFail();
        $this->assertSame($organization->id, $loan->organization_id);
        $this->assertCount(3, $loan->installments);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'to_status' => LoanStatus::Applied->value,
        ]);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', Loan::class)->count());

        $this->postJson('/api/v1/member/loans', [
            ...$payload,
            'principal_amount' => 499999,
        ])->assertUnprocessable();

        Sanctum::actingAs($maxMemberUser, ['member:read', 'member:write']);
        $this->postJson('/api/v1/member/loans', [
            ...$payload,
            'principal_amount' => 2000000,
        ])->assertCreated();

        Sanctum::actingAs($overLimitMemberUser, ['member:read', 'member:write']);
        $this->postJson('/api/v1/member/loans', [
            ...$payload,
            'principal_amount' => 2000001,
        ])->assertUnprocessable();

        $this->assertSame(2, Loan::query()->count());
        $this->assertSame(0, Loan::query()->where('cooperative_member_id', $overLimitMember->id)->count());
        $this->assertSame(0, LoanPayment::query()->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
    }

    public function test_loan002_admin_application_requires_active_member_and_organization_scope(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $admin = $this->user('Admin Koperasi', $organizationA);
        $activeMember = $this->member($organizationA);
        $inactiveMember = $this->member($organizationA, null, ['status' => 'INACTIVE', 'validation_status' => 'INACTIVE']);
        $foreignMember = $this->member($organizationB);
        $loanType = $this->loanType();
        $payload = fn (int $memberId): array => [
            'cooperative_member_id' => $memberId,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 3,
            'first_due_date' => now()->addMonth()->toDateString(),
        ];

        $this->actingAs($admin)->post(route('cooperative.loans.store'), $payload($activeMember->id))
            ->assertRedirect();
        $this->assertDatabaseHas('loans', [
            'cooperative_member_id' => $activeMember->id,
            'status' => LoanStatus::Applied->value,
        ]);

        $loansBefore = Loan::query()->count();
        $logsBefore = ApprovalLog::query()->count();
        $this->actingAs($admin)->post(route('cooperative.loans.store'), $payload($inactiveMember->id))
            ->assertSessionHasErrors('loan');
        $this->assertSame($loansBefore, Loan::query()->count());
        $this->assertSame($logsBefore, ApprovalLog::query()->count());

        $this->actingAs($admin)->post(route('cooperative.loans.store'), $payload($foreignMember->id))
            ->assertNotFound();
        $this->assertSame($loansBefore, Loan::query()->count());
        $this->assertSame(3, LoanInstallment::query()->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
    }

    public function test_loan003_calculator_validates_tenor_rate_and_does_not_persist(): void
    {
        $admin = $this->user('System Admin', Organization::factory()->create());
        $loanType = $this->loanType(['interest_rate' => 2, 'admin_fee' => 10000]);
        $calculatorPayload = [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 4,
            'first_due_date' => now()->addMonth()->toDateString(),
        ];

        Sanctum::actingAs($admin, ['cooperative:read']);
        $this->postJson('/api/v1/loans/calculator', $calculatorPayload)
            ->assertOk()
            ->assertJsonPath('data.principal_amount', 1000000)
            ->assertJsonPath('data.total_interest_amount', 80000)
            ->assertJsonPath('data.total_amount', 1090000)
            ->assertJsonPath('data.schedule.0.amount_due', 280000);

        $this->postJson('/api/v1/loans/calculator', [...$calculatorPayload, 'term_months' => 0])
            ->assertUnprocessable();
        $this->postJson('/api/v1/loans/calculator', [...$calculatorPayload, 'term_months' => -1])
            ->assertUnprocessable();
        $this->postJson('/api/v1/loans/calculator', [...$calculatorPayload, 'principal_amount' => 0])
            ->assertUnprocessable();

        $negativeRateType = $this->loanType(['interest_rate' => -2]);
        $this->postJson('/api/v1/loans/calculator', [
            ...$calculatorPayload,
            'loan_type_id' => $negativeRateType->id,
        ])->assertUnprocessable();

        $this->assertSame(0, Loan::query()->count());
        $this->assertSame(0, LoanInstallment::query()->count());
        $this->assertSame(0, LoanPayment::query()->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
    }

    public function test_loan004_only_manager_can_review_and_wrong_state_is_non_mutating(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->user('Admin Koperasi', $organization);
        $cashier = $this->user('Kasir Koperasi', $organization);
        $manager = $this->user('Manajer Koperasi', $organization);
        $loan = $this->appliedLoan($organization, $admin);
        $approvalCount = ApprovalLog::query()->where('subject_id', (string) $loan->id)->count();

        foreach ([$admin, $cashier] as $unauthorized) {
            $this->actingAs($unauthorized)->post(route('cooperative.loans.review', $loan), [
                'notes' => 'Tidak berwenang',
            ])->assertForbidden();
        }

        $this->assertSame(LoanStatus::Applied, $loan->fresh()->status);
        $this->assertNull($loan->fresh()->manager_reviewed_by);
        $this->assertSame($approvalCount, ApprovalLog::query()->where('subject_id', (string) $loan->id)->count());

        $this->actingAs($manager)->post(route('cooperative.loans.review', $loan), [
            'notes' => 'Review manager',
        ])->assertRedirect();

        $this->assertSame(LoanStatus::ManagerApproved, $loan->fresh()->status);
        $this->assertSame($manager->id, $loan->fresh()->manager_reviewed_by);
        $reviewLogCount = ApprovalLog::query()->where('subject_id', (string) $loan->id)->count();

        $this->actingAs($manager)->post(route('cooperative.loans.review', $loan), [
            'notes' => 'Review ulang',
        ])->assertRedirect();
        $this->assertSame(LoanStatus::ManagerApproved, $loan->fresh()->status);
        $this->assertSame($reviewLogCount, ApprovalLog::query()->where('subject_id', (string) $loan->id)->count());
    }

    public function test_loan005_final_approval_requires_manager_review_and_segregates_actors(): void
    {
        $organization = Organization::factory()->create();
        $creator = $this->user('Admin Koperasi', $organization);
        $manager = $this->user('Manajer Koperasi', $organization);
        $pengurus = $this->user('Pengurus Koperasi', $organization);
        $loan = $this->appliedLoan($organization, $creator);
        $approvalCount = ApprovalLog::query()->where('subject_id', (string) $loan->id)->count();

        $this->actingAs($pengurus)->post(route('cooperative.loans.approve', $loan), [
            'notes' => 'Bypass review',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame(LoanStatus::Applied, $loan->status);
        $this->assertNull($loan->approved_by);
        $this->assertNull($loan->approved_at);
        $this->assertSame($approvalCount, ApprovalLog::query()->where('subject_id', (string) $loan->id)->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());

        $this->actingAs($manager)->post(route('cooperative.loans.review', $loan), [
            'notes' => 'Manager review',
        ])->assertRedirect();
        $this->actingAs($manager)->post(route('cooperative.loans.approve', $loan), [
            'notes' => 'Self final approval',
        ])->assertForbidden();
        $this->assertSame(LoanStatus::ManagerApproved, $loan->fresh()->status);
        $this->assertNull($loan->fresh()->approved_by);

        $this->actingAs($pengurus)->post(route('cooperative.loans.approve', $loan), [
            'notes' => 'Final approval',
        ])->assertRedirect();
        $this->assertSame(LoanStatus::Approved, $loan->fresh()->status);
        $this->assertSame($pengurus->id, $loan->fresh()->approved_by);
        $this->assertDatabaseHas('approval_logs', [
            'subject_id' => (string) $loan->id,
            'from_status' => LoanStatus::ManagerApproved->value,
            'to_status' => LoanStatus::Approved->value,
        ]);
    }

    public function test_loan006_web_rejection_requires_reason_and_authorized_actor(): void
    {
        $organization = Organization::factory()->create();
        $creator = $this->user('Admin Koperasi', $organization);
        $manager = $this->user('Manajer Koperasi', $organization);
        $cashier = $this->user('Kasir Koperasi', $organization);
        $loan = $this->appliedLoan($organization, $creator);

        $this->actingAs($manager)->post(route('cooperative.loans.reject', $loan), [
            'rejection_reason' => 'Dokumen belum lengkap',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame(LoanStatus::Rejected, $loan->status);
        $this->assertSame('Dokumen belum lengkap', $loan->rejection_reason);
        $this->assertSame($manager->id, $loan->rejected_by);
        $this->assertNotNull($loan->rejected_at);
        $this->assertDatabaseHas('approval_logs', [
            'subject_id' => (string) $loan->id,
            'to_status' => LoanStatus::Rejected->value,
            'note' => 'Dokumen belum lengkap',
        ]);

        $missingReasonLoan = $this->appliedLoan($organization, $creator);
        $this->actingAs($manager)->post(route('cooperative.loans.reject', $missingReasonLoan))
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame(LoanStatus::Applied, $missingReasonLoan->fresh()->status);
        $this->assertDatabaseMissing('approval_logs', [
            'subject_id' => (string) $missingReasonLoan->id,
            'to_status' => LoanStatus::Rejected->value,
        ]);

        $tooLongReasonLoan = $this->appliedLoan($organization, $creator);
        $this->actingAs($manager)->post(route('cooperative.loans.reject', $tooLongReasonLoan), [
            'rejection_reason' => str_repeat('x', 1001),
        ])->assertSessionHasErrors('rejection_reason');
        $this->assertSame(LoanStatus::Applied, $tooLongReasonLoan->fresh()->status);

        $unauthorizedLoan = $this->appliedLoan($organization, $creator);
        $this->actingAs($cashier)->post(route('cooperative.loans.reject', $unauthorizedLoan), [
            'rejection_reason' => 'Tidak boleh',
        ])->assertForbidden();
        $this->assertSame(LoanStatus::Applied, $unauthorizedLoan->fresh()->status);

        $activeLoan = $this->activeLoan($organization);
        $this->actingAs($manager)->post(route('cooperative.loans.reject', $activeLoan), [
            'rejection_reason' => 'Sudah dicairkan',
        ])->assertRedirect();
        $this->assertSame(LoanStatus::Active, $activeLoan->fresh()->status);
    }

    public function test_loan007_disbursement_requires_reference_approved_state_and_is_single_effect(): void
    {
        $organization = Organization::factory()->create();
        $creator = $this->user('Admin Koperasi', $organization);
        $manager = $this->user('Manajer Koperasi', $organization);
        $pengurus = $this->user('Pengurus Koperasi', $organization);
        $admin = $this->user('Admin Koperasi', $organization);
        $approvedLoan = $this->approvedLoan($organization, $creator, $manager, $pengurus);

        $this->actingAs($admin)->post(route('cooperative.loans.disburse', $approvedLoan))
            ->assertSessionHasErrors('reference_no');
        $this->assertSame(LoanStatus::Approved, $approvedLoan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());

        $this->actingAs($admin)->post(route('cooperative.loans.disburse', $approvedLoan), [
            'reference_no' => 'DISB-FUNC08-001',
        ])->assertRedirect();
        $approvedLoan->refresh();
        $this->assertSame(LoanStatus::Active, $approvedLoan->status);
        $this->assertSame('DISB-FUNC08-001', $approvedLoan->reference_no);
        $this->assertNotNull($approvedLoan->disbursed_at);
        $this->assertSame($admin->id, $approvedLoan->disbursed_by);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('entry_type', 'LOAN_DISBURSEMENT')->count());

        $this->actingAs($admin)->post(route('cooperative.loans.disburse', $approvedLoan), [
            'reference_no' => 'DISB-FUNC08-002',
        ])->assertRedirect();
        $this->assertSame('DISB-FUNC08-001', $approvedLoan->fresh()->reference_no);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('entry_type', 'LOAN_DISBURSEMENT')->count());

        $appliedLoan = $this->appliedLoan($organization, $creator);
        $this->actingAs($admin)->post(route('cooperative.loans.disburse', $appliedLoan), [
            'reference_no' => 'DISB-INVALID',
        ])->assertRedirect();
        $this->assertSame(LoanStatus::Applied, $appliedLoan->fresh()->status);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('entry_type', 'LOAN_DISBURSEMENT')->count());
    }

    public function test_loan008_partial_repayment_allocates_fifo_and_rejects_over_allocation_atomically(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->user('System Admin', $organization);
        $loan = $this->scheduledActiveLoan($organization);

        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 50,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $loan->refresh();
        $installments = $loan->installments()->orderBy('installment_no')->get();
        $this->assertSame('250.00', $loan->outstanding_amount);
        $this->assertSame('50.00', $installments[0]->amount_paid);
        $this->assertSame(InstallmentStatus::Partial, $installments[0]->status);
        $this->assertSame('0.00', $installments[1]->amount_paid);
        $this->assertSame(1, LoanPayment::query()->where('loan_id', $loan->id)->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('source_type', LoanPayment::class)->count());

        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 150,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $installments = $loan->refresh()->installments()->orderBy('installment_no')->get();
        $this->assertSame('100.00', $installments[0]->amount_paid);
        $this->assertSame(InstallmentStatus::Paid, $installments[0]->status);
        $this->assertSame('100.00', $installments[1]->amount_paid);
        $this->assertSame(InstallmentStatus::Paid, $installments[1]->status);
        $this->assertSame('100.00', $loan->fresh()->outstanding_amount);

        $paymentCount = LoanPayment::query()->where('loan_id', $loan->id)->count();
        $ledgerCount = CooperativeLedgerEntry::query()->where('source_type', LoanPayment::class)->count();
        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 101,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
        $this->assertSame($paymentCount, LoanPayment::query()->where('loan_id', $loan->id)->count());
        $this->assertSame($ledgerCount, CooperativeLedgerEntry::query()->where('source_type', LoanPayment::class)->count());
        $this->assertSame('100.00', $loan->fresh()->outstanding_amount);
    }

    public function test_loan009_full_payoff_closes_all_installments_and_rejects_replay(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->user('System Admin', $organization);
        $loan = $this->scheduledActiveLoan($organization);

        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 300,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'reference_no' => 'PAYOFF-FUNC08-001',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame(LoanStatus::PaidOff, $loan->status);
        $this->assertSame('0.00', $loan->outstanding_amount);
        $this->assertSame(1, LoanPayment::query()->where('loan_id', $loan->id)->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('source_type', LoanPayment::class)->count());
        $this->assertSame(3, $loan->installments()->where('status', InstallmentStatus::Paid)->count());
        $this->assertSame(0, $loan->installments()->whereIn('status', [
            InstallmentStatus::Pending,
            InstallmentStatus::Partial,
            InstallmentStatus::Overdue,
        ])->count());
        $this->assertSame(300, $loan->installments()->sum('amount_paid'));

        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 1,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
        ])->assertSessionHasErrors('status');
        $this->assertSame(1, LoanPayment::query()->where('loan_id', $loan->id)->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('source_type', LoanPayment::class)->count());
        $this->assertSame(LoanStatus::PaidOff, $loan->fresh()->status);
    }

    public function test_loan010_authorized_pengurus_can_write_off_active_and_defaulted_loan_with_ledger_and_audit(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->user('Pengurus Koperasi', $organization);
        $loan = $this->scheduledActiveLoan($organization);

        $this->assertTrue(collect(app('router')->getRoutes()->getRoutes())
            ->contains(fn ($route): bool => $route->uri() === 'cooperative/loans/{loan}/write-off'));
        $this->assertTrue($pengurus->can('writeOff', $loan));

        $response = $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Bad debt decision by board FUNC-08',
        ]);
        $response->assertRedirect();

        $loan->refresh();
        $this->assertSame(LoanStatus::WrittenOff, $loan->status);
        $this->assertStringContainsString('Bad debt decision by board FUNC-08', (string) $loan->notes);

        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'from_status' => LoanStatus::Active->value,
            'to_status' => LoanStatus::WrittenOff->value,
            'note' => 'Bad debt decision by board FUNC-08',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.writeoff.completed',
            'subject_id' => (string) $loan->id,
        ]);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $loan->cooperative_member_id,
            'organization_id' => $organization->id,
            'source_type' => Loan::class,
            'source_id' => $loan->id,
            'entry_type' => 'LOAN_WRITE_OFF',
            'ledger_scope' => 'LOAN',
            'debit' => 0,
            'credit' => 300,
        ]);
        $this->assertSame(1, CooperativeLedgerEntry::query()
            ->where('source_type', Loan::class)
            ->where('source_id', $loan->id)
            ->where('entry_type', 'LOAN_WRITE_OFF')
            ->count());

        // Also verify DEFAULTED -> WRITTEN_OFF
        $member = $this->member($organization);
        $loanType = $this->loanType();
        $defaultedLoan = Loan::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 500000,
            'interest_rate' => 0,
            'admin_fee' => 0,
            'late_fee_per_day' => 0,
            'term_months' => 3,
            'installment_amount' => 500000,
            'total_interest_amount' => 0,
            'total_amount' => 500000,
            'outstanding_amount' => 500000,
            'applied_at' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'status' => LoanStatus::Defaulted,
            'disbursed_at' => now(),
            'reference_no' => 'DISB-FUNC08-DEFAULTED',
        ]);

        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $defaultedLoan), [
            'notes' => 'Pailit debitur defaulted',
        ])->assertRedirect();

        $defaultedLoan->refresh();
        $this->assertSame(LoanStatus::WrittenOff, $defaultedLoan->status);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $defaultedLoan->id,
            'from_status' => LoanStatus::Defaulted->value,
            'to_status' => LoanStatus::WrittenOff->value,
            'note' => 'Pailit debitur defaulted',
        ]);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => Loan::class,
            'source_id' => $defaultedLoan->id,
            'entry_type' => 'LOAN_WRITE_OFF',
            'credit' => 500000,
        ]);
    }

    public function test_loan010_unauthorized_and_cross_organization_actors_are_forbidden_with_zero_mutation(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->user('Admin Koperasi', $organization);
        $cashier = $this->user('Kasir Koperasi', $organization);
        $otherOrg = Organization::factory()->create();
        $crossOrgPengurus = $this->user('Pengurus Koperasi', $otherOrg);
        $loan = $this->scheduledActiveLoan($organization);

        $initialNotes = $loan->notes;
        $initialStatus = $loan->status;

        // Admin Koperasi: 403 Forbidden
        $this->assertFalse($admin->can('writeOff', $loan));
        $this->actingAs($admin)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Admin write-off attempt',
        ])->assertForbidden();

        $this->assertSame($initialStatus, $loan->fresh()->status);
        $this->assertSame($initialNotes, $loan->fresh()->notes);
        $this->assertSame(0, ApprovalLog::query()->where('subject_id', (string) $loan->id)->where('to_status', LoanStatus::WrittenOff->value)->count());
        $this->assertSame(0, AuditLog::query()->where('subject_id', (string) $loan->id)->where('action', 'loan.writeoff.completed')->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', Loan::class)->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // Kasir Koperasi: 403 Forbidden
        $this->assertFalse($cashier->can('writeOff', $loan));
        $this->actingAs($cashier)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Cashier write-off attempt',
        ])->assertForbidden();

        $this->assertSame($initialStatus, $loan->fresh()->status);
        $this->assertSame(0, ApprovalLog::query()->where('subject_id', (string) $loan->id)->where('to_status', LoanStatus::WrittenOff->value)->count());
        $this->assertSame(0, AuditLog::query()->where('subject_id', (string) $loan->id)->where('action', 'loan.writeoff.completed')->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', Loan::class)->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // Cross-organization Pengurus: 403 Forbidden
        $this->assertFalse($crossOrgPengurus->can('writeOff', $loan));
        $this->actingAs($crossOrgPengurus)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Cross-org write-off attempt',
        ])->assertForbidden();

        $this->assertSame($initialStatus, $loan->fresh()->status);
        $this->assertSame(0, ApprovalLog::query()->where('subject_id', (string) $loan->id)->where('to_status', LoanStatus::WrittenOff->value)->count());
        $this->assertSame(0, AuditLog::query()->where('subject_id', (string) $loan->id)->where('action', 'loan.writeoff.completed')->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', Loan::class)->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());
    }

    public function test_loan010_invalid_state_and_empty_reason_are_rejected_with_zero_mutation(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->user('Pengurus Koperasi', $organization);
        $loan = $this->scheduledActiveLoan($organization);

        // Empty reason rejected
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => '',
        ])->assertSessionHasErrors('reason');

        $this->assertSame(LoanStatus::Active, $loan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // APPLIED state rejected
        $appliedLoan = $this->appliedLoan($organization, $pengurus);
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $appliedLoan), [
            'reason' => 'Attempt on applied loan',
        ])->assertSessionHasErrors('status');

        $this->assertSame(LoanStatus::Applied, $appliedLoan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_id', $appliedLoan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // MANAGER_APPROVED state rejected
        $manager = $this->user('Manajer Koperasi', $organization);
        app(LoanServiceContract::class)->managerReview($appliedLoan, $manager, 'Manager review');
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $appliedLoan->fresh()), [
            'reason' => 'Attempt on manager approved loan',
        ])->assertSessionHasErrors('status');

        $this->assertSame(LoanStatus::ManagerApproved, $appliedLoan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_id', $appliedLoan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // APPROVED state rejected
        $creator = $this->user('Admin Koperasi', $organization);
        $approvedLoan = $this->approvedLoan($organization, $creator, $manager, $pengurus);
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $approvedLoan), [
            'reason' => 'Attempt on approved loan',
        ])->assertSessionHasErrors('status');

        $this->assertSame(LoanStatus::Approved, $approvedLoan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_id', $approvedLoan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());

        // PAID_OFF state rejected
        $paidOffLoan = $this->scheduledActiveLoan($organization);
        $paidOffLoan->forceFill(['status' => LoanStatus::PaidOff, 'outstanding_amount' => 0])->save();
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $paidOffLoan), [
            'reason' => 'Attempt on paid off loan',
        ])->assertSessionHasErrors('status');

        $this->assertSame(LoanStatus::PaidOff, $paidOffLoan->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_id', $paidOffLoan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());
    }

    public function test_loan010_repeated_write_off_is_rejected_and_prevents_duplicate_effects(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->user('Pengurus Koperasi', $organization);
        $loan = $this->scheduledActiveLoan($organization);

        // Initial write-off succeeds
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Initial write-off FUNC-08',
        ])->assertRedirect();

        $this->assertSame(LoanStatus::WrittenOff, $loan->fresh()->status);
        $approvalCount = ApprovalLog::query()->where('subject_id', (string) $loan->id)->where('to_status', LoanStatus::WrittenOff->value)->count();
        $auditCount = AuditLog::query()->where('subject_id', (string) $loan->id)->where('action', 'loan.writeoff.completed')->count();
        $ledgerCount = CooperativeLedgerEntry::query()->where('source_type', Loan::class)->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count();

        $this->assertSame(1, $approvalCount);
        $this->assertSame(1, $auditCount);
        $this->assertSame(1, $ledgerCount);

        // Repeated write-off attempt is rejected
        $this->actingAs($pengurus)->post(route('cooperative.loans.write-off', $loan), [
            'reason' => 'Duplicate write-off attempt',
        ])->assertSessionHasErrors('status');

        $this->assertSame(LoanStatus::WrittenOff, $loan->fresh()->status);
        $this->assertSame(1, ApprovalLog::query()->where('subject_id', (string) $loan->id)->where('to_status', LoanStatus::WrittenOff->value)->count());
        $this->assertSame(1, AuditLog::query()->where('subject_id', (string) $loan->id)->where('action', 'loan.writeoff.completed')->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('source_type', Loan::class)->where('source_id', $loan->id)->where('entry_type', 'LOAN_WRITE_OFF')->count());
    }

    private function user(string $role, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($role);

        return $user;
    }

    private function member(Organization $organization, ?User $user = null, array $attributes = []): CooperativeMember
    {
        return CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user?->id,
            'member_no' => 'MBR-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'status' => 'ACTIVE',
            'validation_status' => 'ACTIVE',
            'joined_at' => now()->subYear()->toDateString(),
            ...$attributes,
        ]);
    }

    private function loanType(array $attributes = []): LoanType
    {
        return LoanType::factory()->create($attributes);
    }

    private function appliedLoan(Organization $organization, User $creator): Loan
    {
        $member = $this->member($organization);
        $loanType = $this->loanType();

        return app(LoanServiceContract::class)->apply([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 3,
            'first_due_date' => now()->addMonth()->toDateString(),
        ], $creator);
    }

    private function approvedLoan(Organization $organization, User $creator, User $manager, User $pengurus): Loan
    {
        $loan = $this->appliedLoan($organization, $creator);
        app(LoanServiceContract::class)->managerReview($loan, $manager, 'Manager review');
        app(LoanServiceContract::class)->approve($loan, $pengurus, 'Final approval');

        return $loan->refresh();
    }

    private function activeLoan(Organization $organization): Loan
    {
        return $this->scheduledActiveLoan($organization);
    }

    private function scheduledActiveLoan(Organization $organization): Loan
    {
        $member = $this->member($organization);
        $loanType = $this->loanType(['min_amount' => 1, 'max_amount' => 1000000]);
        $loan = Loan::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 240,
            'interest_rate' => 0,
            'admin_fee' => 0,
            'late_fee_per_day' => 0,
            'term_months' => 3,
            'installment_amount' => 100,
            'total_interest_amount' => 0,
            'total_amount' => 300,
            'outstanding_amount' => 300,
            'applied_at' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'status' => LoanStatus::Active,
            'disbursed_at' => now(),
            'reference_no' => 'DISB-FUNC08-ACTIVE',
        ]);

        foreach ([1, 2, 3] as $number) {
            $loan->installments()->create([
                'installment_no' => $number,
                'due_date' => now()->addMonths($number)->toDateString(),
                'principal_amount' => 100,
                'interest_amount' => 0,
                'fee_amount' => 0,
                'penalty_amount' => 0,
                'amount_due' => 100,
                'amount_paid' => 0,
                'status' => InstallmentStatus::Pending,
            ]);
        }

        return $loan->load('installments');
    }
}
