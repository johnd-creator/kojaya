<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\LoanService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseCSegregationOfDutiesTest extends TestCase
{
    use DatabaseMigrations;

    private Organization $org;

    private User $creator;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->org = Organization::factory()->create();

        $this->creator = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->creator->assignRole('Anggota');

        $this->approver = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->approver->assignRole('Pengurus Koperasi');
        $this->approver->givePermissionTo([
            'approve_cooperative_loan',
            'approve_reimbursement',
            'approve_leave',
            'approve_overtime',
            'approve_pr',
            'approve_payroll',
            'manage_cooperative_payment',
        ]);
    }

    public function test_loan_creator_cannot_approve_own_loan(): void
    {
        $this->creator->givePermissionTo('approve_cooperative_loan');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $loanType = LoanType::factory()->create();

        $loan = Loan::create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->org->id,
            'loan_type_id' => $loanType->id,
            'user_id' => $this->creator->id,
            'principal_amount' => 10000000,
            'interest_rate' => 5,
            'term_months' => 12,
            'installment_amount' => 833333,
            'total_interest_amount' => 500000,
            'total_amount' => 10500000,
            'outstanding_amount' => 10500000,
            'applied_at' => now(),
            'first_due_date' => now()->addMonth(),
            'status' => 'APPLIED',
        ]);

        $this->expectException(ValidationException::class);

        app(LoanService::class)->approve($loan, $this->creator);
    }

    public function test_loan_can_be_approved_by_different_user(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $loanType = LoanType::factory()->create();

        $loan = Loan::create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->org->id,
            'loan_type_id' => $loanType->id,
            'user_id' => $this->creator->id,
            'principal_amount' => 10000000,
            'interest_rate' => 5,
            'term_months' => 12,
            'installment_amount' => 833333,
            'total_interest_amount' => 500000,
            'total_amount' => 10500000,
            'outstanding_amount' => 10500000,
            'applied_at' => now(),
            'first_due_date' => now()->addMonth(),
            'status' => 'APPLIED',
        ]);

        $result = app(LoanService::class)->approve($loan, $this->approver);

        $this->assertSame(LoanStatus::Approved, $result->status);
    }

    public function test_payment_creator_cannot_approve_own_payment(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'MANDATORY',
            'period' => 'MONTHLY',
            'amount' => 100000,
            'organization_id' => $this->org->id,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'due_date' => '2026-05-01',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $this->creator->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-12',
            'status' => 'PENDING',
            'organization_id' => $this->org->id,
        ]);

        $this->expectException(ValidationException::class);

        app(CooperativePaymentService::class)->approve($payment, $this->creator);
    }

    public function test_payment_can_be_approved_by_different_user(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'MANDATORY',
            'period' => 'MONTHLY',
            'amount' => 100000,
            'organization_id' => $this->org->id,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'due_date' => '2026-05-01',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $this->creator->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-12',
            'status' => 'PENDING',
            'organization_id' => $this->org->id,
        ]);

        $result = app(CooperativePaymentService::class)->approve($payment, $this->approver);

        $this->assertSame('APPROVED', $result->status);
    }

    public function test_reimbursement_creator_cannot_approve_own_via_policy(): void
    {
        Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
            'status' => 'SUBMITTED',
            'approver_id' => null,
        ]);

        $this->actingAs($this->creator);

        $response = $this->post(route('reimbursements.approve', $reimbursement));

        $response->assertForbidden();
    }

    public function test_reimbursement_can_be_approved_by_different_user(): void
    {
        Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
            'status' => 'SUBMITTED',
            'approver_id' => null,
        ]);

        $this->actingAs($this->approver);

        $response = $this->post(route('reimbursements.approve', $reimbursement));

        $response->assertRedirect();
        $this->assertSame('APPROVED', $reimbursement->fresh()->status);
    }

    public function test_leave_submitter_cannot_approve_own_leave(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $leaveType = LeaveType::factory()->create();

        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'Pending',
        ]);

        $this->actingAs($this->creator);

        $response = $this->put(route('leaves.update-status', $leave), [
            'status' => 'Approved',
        ]);

        $response->assertForbidden();
    }

    public function test_leave_can_be_approved_by_different_user(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $leaveType = LeaveType::factory()->create();

        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'Pending',
        ]);

        $this->actingAs($this->approver);

        $response = $this->put(route('leaves.update-status', $leave), [
            'status' => 'Approved',
        ]);

        $response->assertRedirect();
        $this->assertSame('Approved', $leave->fresh()->status);
    }

    public function test_overtime_submitter_cannot_approve_own_overtime(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $rule = OvertimeRule::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $overtime = OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $this->org->id,
            'overtime_rule_id' => $rule->id,
            'status' => 'PENDING',
            'approved_by' => null,
        ]);

        $this->actingAs($this->creator);

        $response = $this->post(route('overtime.approve', $overtime));

        $response->assertForbidden();
    }

    public function test_overtime_can_be_approved_by_different_user(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $rule = OvertimeRule::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $overtime = OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $this->org->id,
            'overtime_rule_id' => $rule->id,
            'status' => 'PENDING',
            'approved_by' => null,
        ]);

        $this->actingAs($this->approver);

        $response = $this->post(route('overtime.approve', $overtime));

        $response->assertRedirect();
        $this->assertSame('APPROVED', $overtime->fresh()->status);
    }

    public function test_purchase_request_creator_cannot_approve_own_pr(): void
    {
        $this->creator->givePermissionTo('approve_pr');

        $pr = PurchaseRequest::factory()->create([
            'organization_id' => $this->org->id,
            'unit_id' => $this->org->id,
            'requester_id' => $this->creator->id,
            'status' => 'SUBMITTED',
            'total_amount' => 1000000,
        ]);

        $this->actingAs($this->creator);

        $response = $this->post(route('procurement.prs.approve', ['purchaseRequest' => $pr, 'level' => 1]));

        $response->assertSessionHasErrors('approval');
        $this->assertSame('SUBMITTED', $pr->fresh()->status);
    }

    public function test_purchase_request_can_be_approved_by_different_user(): void
    {
        $procurementApprover = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $procurementApprover->assignRole('System Admin');
        $procurementApprover->givePermissionTo('approve_pr');

        $pr = PurchaseRequest::factory()->create([
            'organization_id' => $this->org->id,
            'unit_id' => $this->org->id,
            'requester_id' => $this->creator->id,
            'status' => 'SUBMITTED',
            'total_amount' => 1000000,
        ]);

        $this->actingAs($procurementApprover);

        $response = $this->post(route('procurement.prs.approve', ['purchaseRequest' => $pr, 'level' => 1]));

        $response->assertRedirect();
        $this->assertSame('APPROVED', $pr->fresh()->status);
        $this->assertSame('APPROVED', $pr->fresh()->status);
    }

    public function test_payroll_requester_cannot_approve_own_payroll(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $payroll = Payroll::factory()->create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'status' => 'PENDING',
        ]);

        $approval = PayrollApproval::create([
            'payroll_id' => $payroll->id,
            'requester_id' => $this->creator->id,
            'approver_id' => null,
            'status' => 'PENDING',
            'requested_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        $approval->approve($this->creator);
    }

    public function test_payroll_can_be_approved_by_different_user(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->org->id,
            'user_id' => $this->creator->id,
        ]);

        $payroll = Payroll::factory()->create([
            'organization_id' => $this->org->id,
            'employee_id' => $employee->id,
            'status' => 'PENDING',
        ]);

        $approval = PayrollApproval::create([
            'payroll_id' => $payroll->id,
            'requester_id' => $this->creator->id,
            'approver_id' => null,
            'status' => 'PENDING',
            'requested_at' => now(),
        ]);

        $approval->approve($this->approver);

        $this->assertSame('APPROVED', $approval->fresh()->status);
    }
}
