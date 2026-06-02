<?php

namespace Tests\Feature\Cooperative;

use App\Models\ApprovalLog;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\LoanCalculatorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CooperativeLoanFeatureTest extends TestCase
{
    use DatabaseMigrations;

    public function test_admin_can_create_approve_and_disburse_loan(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $approver = User::factory()->create();
        $approver->assignRole('System Admin');
        $member = $this->member();
        $loanType = $this->loanType();

        $this->actingAs($admin)->post(route('cooperative.loans.store'), [
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1200000,
            'term_months' => 6,
            'first_due_date' => '2026-06-15',
            'purpose' => 'Modal usaha',
            'notes' => 'Pengajuan awal',
        ])->assertRedirect();

        $loan = Loan::query()->latest('id')->firstOrFail();

        $this->assertSame($member->id, $loan->cooperative_member_id);
        $this->assertSame('APPLIED', $loan->status->value);
        $this->assertCount(6, $loan->installments);
        $this->assertSame('1354000.00', $loan->total_amount);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'to_status' => 'APPLIED',
        ]);

        $this->actingAs($approver)->post(route('cooperative.loans.approve', $loan), [
            'notes' => 'Disetujui pengurus',
        ])->assertRedirect();

        $this->assertSame('APPROVED', $loan->refresh()->status->value);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'from_status' => 'APPLIED',
            'to_status' => 'APPROVED',
        ]);

        $this->actingAs($approver)->post(route('cooperative.loans.disburse', $loan), [
            'reference_no' => 'DISB-001',
        ])->assertRedirect();

        $this->assertSame('ACTIVE', $loan->refresh()->status->value);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'source_type' => Loan::class,
            'source_id' => $loan->id,
            'entry_type' => 'LOAN_DISBURSEMENT',
            'debit' => 1200000,
        ]);
    }

    public function test_active_loan_payment_updates_outstanding_installment_and_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $loan = $this->activeLoan();

        $this->actingAs($admin)->post(route('cooperative.loans.pay', $loan), [
            'amount' => 334000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-06-15',
            'reference_no' => 'PAY-001',
            'notes' => 'Angsuran pertama',
        ])->assertRedirect();

        $payment = LoanPayment::query()->latest('id')->firstOrFail();
        $loan->refresh();
        $firstInstallment = $loan->installments()->orderBy('installment_no')->firstOrFail();

        $this->assertSame($loan->id, $payment->loan_id);
        $this->assertSame('972000.00', $loan->outstanding_amount);
        $this->assertSame('PAID', $firstInstallment->status->value);
        $this->assertSame('334000.00', $firstInstallment->amount_paid);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $loan->cooperative_member_id,
            'source_type' => LoanPayment::class,
            'source_id' => $payment->id,
            'entry_type' => 'LOAN_PAYMENT',
            'credit' => 334000,
        ]);
    }

    public function test_member_api_can_apply_and_only_view_own_loans(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $memberUser = User::factory()->create();
        $memberUser->assignRole('Anggota');
        $member = $this->member([
            'user_id' => $memberUser->id,
            'email' => $memberUser->email,
        ]);
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Anggota');
        $otherMember = $this->member([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
        ]);
        $loanType = $this->loanType();
        $otherLoan = $this->activeLoan($otherMember, $loanType);

        Sanctum::actingAs($memberUser, ['cooperative:read', 'cooperative:write']);

        $this->postJson('/api/v1/loans/apply', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 900000,
            'term_months' => 3,
            'first_due_date' => '2026-06-15',
            'purpose' => 'Kebutuhan keluarga',
        ])->assertCreated()
            ->assertJsonPath('data.cooperative_member_id', $member->id)
            ->assertJsonPath('data.status', 'APPLIED');

        $this->getJson('/api/v1/loans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cooperative_member_id', $member->id);

        $this->getJson("/api/v1/loans/{$otherLoan->id}")
            ->assertForbidden();
    }

    public function test_loan_calculator_endpoints_work_for_web_and_api(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $loanType = $this->loanType();

        $this->actingAs($admin)
            ->get(route('cooperative.loans.calculator'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Loans/Calculator')
                ->where('preview', null)
            );

        Sanctum::actingAs($admin, ['cooperative:read']);

        $this->postJson('/api/v1/loans/calculator', [
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 4,
            'first_due_date' => '2026-06-10',
        ])->assertOk()
            ->assertJsonPath('data.installment_amount', 270000)
            ->assertJsonPath('data.total_interest_amount', 80000)
            ->assertJsonPath('data.schedule.0.amount_due', 280000);
    }

    public function test_loan_show_page_loads_approval_logs(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $loan = $this->activeLoan();

        ApprovalLog::query()->create([
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'from_status' => 'APPROVED',
            'to_status' => 'ACTIVE',
            'approved_by' => $admin->id,
            'note' => 'Pencairan selesai.',
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.loans.show', $loan))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Loans/Show')
                ->where('loan.id', $loan->id)
                ->where('approvalLogs.0.to_status', 'ACTIVE')
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): CooperativeMember
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => fake()->uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ],
        );

        return CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'KOP-2026-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'status' => 'ACTIVE',
            'joined_at' => '2026-05-01',
            ...$attributes,
        ]);
    }

    private function loanType(): LoanType
    {
        return LoanType::query()->create([
            'code' => 'PINJ-UMUM',
            'name' => 'Pinjaman Umum',
            'description' => 'Pinjaman anggota reguler',
            'interest_rate' => 2,
            'admin_fee' => 10000,
            'late_fee_per_day' => 5000,
            'min_amount' => 500000,
            'max_amount' => 5000000,
            'min_term_months' => 3,
            'max_term_months' => 12,
            'is_active' => true,
        ]);
    }

    private function activeLoan(?CooperativeMember $member = null, ?LoanType $loanType = null): Loan
    {
        $member ??= $this->member();
        $loanType ??= $this->loanType();
        $calculation = app(LoanCalculatorService::class)->calculate($loanType, 1200000, 4, '2026-06-15');

        $loan = Loan::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => $calculation['principal_amount'],
            'interest_rate' => $calculation['interest_rate'],
            'admin_fee' => $calculation['admin_fee'],
            'late_fee_per_day' => $calculation['late_fee_per_day'],
            'term_months' => $calculation['term_months'],
            'installment_amount' => $calculation['installment_amount'],
            'total_interest_amount' => $calculation['total_interest_amount'],
            'total_amount' => $calculation['total_amount'],
            'outstanding_amount' => $calculation['total_amount'],
            'applied_at' => '2026-05-01',
            'first_due_date' => '2026-06-15',
            'approved_at' => now(),
            'disbursed_at' => now(),
            'status' => 'ACTIVE',
            'reference_no' => 'DISB-AUTO',
        ]);

        $loan->installments()->createMany(
            collect($calculation['schedule'])->map(fn (array $installment): array => [
                ...$installment,
                'amount_paid' => 0,
                'status' => 'PENDING',
            ])->all(),
        );

        return $loan->load('installments');
    }
}
