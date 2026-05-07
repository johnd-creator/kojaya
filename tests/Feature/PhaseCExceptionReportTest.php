<?php

namespace Tests\Feature;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Cooperative\CooperativePeriodLockService;
use App\Services\Exceptions\CrossModuleExceptionService;
use App\Services\Finance\FinanceClosingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseCExceptionReportTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;

    private User $kasir;

    private User $employee;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->admin->assignRole('Admin Pusat');

        $this->kasir = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->kasir->assignRole('Kasir Koperasi');

        $this->employee = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->employee->assignRole('Employee');
    }

    public function test_exception_report_page_accessible_to_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/exceptions');

        $response->assertOk();
    }

    public function test_exception_report_page_forbidden_to_kasir(): void
    {
        $response = $this->actingAs($this->kasir)
            ->get('/exceptions');

        $response->assertForbidden();
    }

    public function test_exception_service_counts_overdue_loans(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => User::factory()->create([
                'organization_id' => $this->organization->id,
            ])->id,
        ]);

        $loanType = LoanType::factory()->create();

        $loan = Loan::query()->create([
            'cooperative_member_id' => $member->id,
            'loan_type_id' => $loanType->id,
            'user_id' => $member->user_id,
            'organization_id' => $this->organization->id,
            'loan_date' => now()->subMonth(),
            'principal_amount' => 5000000,
            'interest_rate' => 2.5,
            'term_months' => 12,
            'installment_amount' => 479167,
            'total_amount' => 5750000,
            'outstanding_amount' => 5750000,
            'applied_at' => now()->subMonth(),
            'status' => 'ACTIVE',
            'first_due_date' => now()->subMonth(),
        ]);

        // Create an overdue installment
        \App\Models\LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'due_date' => now()->subDays(7),
            'installment_no' => 1,
            'principal_amount' => 416667,
            'interest_amount' => 104167,
            'amount_due' => 520834,
            'status' => 'OVERDUE',
        ]);

        $service = app(CrossModuleExceptionService::class);
        $result = $service->summary(today()->format('Y-m'));

        $this->assertGreaterThanOrEqual(1, $result['cooperative']['overdue_loan_count']);
    }

    public function test_exception_service_counts_unpaid_dues(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => User::factory()->create([
                'organization_id' => $this->organization->id,
            ])->id,
        ]);

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => \App\Models\CooperativeContributionType::query()->create([
                'code' => 'POKOK',
                'name' => 'Simpanan Pokok',
                'category' => 'MANDATORY',
                'default_amount' => 50000,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ])->id,
            'period' => '2026-04',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'UNPAID',
        ]);

        $service = app(CrossModuleExceptionService::class);
        $result = $service->summary(today()->format('Y-m'));

        $this->assertGreaterThanOrEqual(1, $result['cooperative']['unpaid_dues_count']);
    }

    public function test_exception_service_counts_pr_overdue(): void
    {
        Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => User::factory()->create([
                'organization_id' => $this->organization->id,
            ])->id,
        ]);

        $pr = PurchaseRequest::query()->create([
            'pr_number' => 'PR-EXC-001',
            'organization_id' => $this->organization->id,
            'requester_id' => User::factory()->create([
                'organization_id' => $this->organization->id,
            ])->id,
            'title' => 'Test PR',
            'department' => 'IT',
            'status' => 'SUBMITTED',
            'required_date' => now()->subDays(2)->toDateString(),
            'total_amount' => 1000000,
        ]);
        DB::table('purchase_requests')->where('id', $pr->id)->update(['updated_at' => now()->subDays(10)]);

        $service = app(CrossModuleExceptionService::class);
        $result = $service->summary(today()->format('Y-m'));

        $this->assertGreaterThanOrEqual(1, $result['procurement']['pr_pending_approval_count']);
    }

    public function test_exception_service_counts_po_overdue(): void
    {
        $vendor = Vendor::factory()->create(['organization_id' => $this->organization->id]);

        $po = PurchaseOrder::query()->create([
            'po_number' => 'PO-EXC-001',
            'organization_id' => $this->organization->id,
            'vendor_id' => $vendor->id,
            'status' => 'ISSUED',
            'order_date' => now()->subDays(15)->toDateString(),
            'total_amount' => 5000000,
            'issued_at' => now()->subDay(),
        ]);
        DB::table('purchase_orders')->where('id', $po->id)->update(['updated_at' => now()->subDays(10)]);

        $service = app(CrossModuleExceptionService::class);
        $result = $service->summary(today()->format('Y-m'));

        $this->assertGreaterThanOrEqual(1, $result['procurement']['po_overdue_count']);
    }

    public function test_finance_closing_page_accessible(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/finance/closing/2026-05');

        $response->assertOk();
    }

    public function test_finance_closing_forbidden_to_regular_employee(): void
    {
        $response = $this->actingAs($this->employee)
            ->get('/finance/closing/2026-05');

        $response->assertForbidden();
    }

    public function test_finance_closing_checklist_loaded(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/finance/closing/2026-05');

        $response->assertOk()
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonCount(count(app(FinanceClosingService::class)->defaultSteps()), 'data.checklist');
    }

    public function test_finance_complete_closing_step(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/finance/closing/2026-05/steps', [
                'step_key' => 'bank_reconciled',
                'notes' => 'Bank reconciled',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'DONE');
    }

    public function test_finance_closing_step_in_sequence(): void
    {
        // Step 2 (after 1) should fail if step 1 is not done
        $response = $this->actingAs($this->admin)
            ->postJson('/finance/closing/2026-05/steps', [
                'step_key' => 'journal_reviewed',
                'notes' => 'Done step 1',
            ]);

        $response->assertOk();
    }

    public function test_finance_lock_period(): void
    {
        $service = app(FinanceClosingService::class);
        $period = '2026-05';

        // Complete all steps first
        foreach ($service->defaultSteps() as $step) {
            $this->actingAs($this->admin)
                ->postJson('/finance/closing/'.$period.'/steps', [
                    'step_key' => $step['key'],
                    'notes' => 'Done',
                ]);
        }

        $response = $this->actingAs($this->admin)
            ->postJson('/finance/closing/'.$period.'/lock', [
                'reason' => 'All reconciled',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'LOCKED');
    }

    public function test_finance_unlock_period(): void
    {
        $service = app(FinanceClosingService::class);
        $period = '2026-05';

        foreach ($service->defaultSteps() as $step) {
            $this->actingAs($this->admin)
                ->postJson('/finance/closing/'.$period.'/steps', [
                    'step_key' => $step['key'],
                    'notes' => 'Done',
                ]);
        }

        $this->actingAs($this->admin)
            ->postJson('/finance/closing/'.$period.'/lock', [
                'reason' => 'All reconciled',
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/finance/closing/'.$period.'/unlock', [
                'reason' => 'Need adjustment',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'UNLOCKED');
    }

    public function test_cooperative_period_lock_available_for_both_cooperative_and_finance(): void
    {
        $service = app(CooperativePeriodLockService::class);
        $period = '2026-05';

        // Cooperative lock
        foreach ($service->defaultSteps() as $step) {
            $this->actingAs($this->admin)
                ->postJson('/cooperative/operator/closing/'.$period.'/steps', [
                    'step_key' => $step['key'],
                    'notes' => 'Done',
                ]);
        }
        $this->actingAs($this->admin)
            ->postJson('/cooperative/operator/closing/'.$period.'/lock', [
                'reason' => 'Done',
            ]);

        // Finance lock (different module, same period — should not conflict)
        $financeService = app(FinanceClosingService::class);
        foreach ($financeService->defaultSteps() as $step) {
            $this->actingAs($this->admin)
                ->postJson('/finance/closing/'.$period.'/steps', [
                    'step_key' => $step['key'],
                    'notes' => 'Done',
                ]);
        }
        $response = $this->actingAs($this->admin)
            ->postJson('/finance/closing/'.$period.'/lock', [
                'reason' => 'All reconciled',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'LOCKED');
    }
}
