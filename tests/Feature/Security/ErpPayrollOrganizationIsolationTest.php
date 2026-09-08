<?php

namespace Tests\Feature\Security;

use App\Contracts\OrganizationScopedModel;
use App\Enums\PayrollApprovalStatus;
use App\Enums\PayrollStatus;
use App\Enums\PermissionEnum;
use App\Models\Employee;
use App\Models\JobGrade;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\SalaryComponentType;
use App\Models\SalaryStructure;
use App\Models\ThrEntitlement;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ErpPayrollOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationScopeService $scopeService;

    private Organization $orgA;

    private Organization $orgB;

    private User $userA;

    private User $userB;

    private User $globalUser;

    private User $nullOrgUser;

    private Employee $employeeA;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->scopeService = app(OrganizationScopeService::class);

        $this->orgA = Organization::factory()->create(['name' => 'Unit Org A']);
        $this->orgB = Organization::factory()->create(['name' => 'Unit Org B']);

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $this->globalUser = User::factory()->create(['organization_id' => null]);
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_VIEW_ALL->value);

        $this->nullOrgUser = User::factory()->create(['organization_id' => null]);

        $this->employeeA = Employee::factory()->create([
            'organization_id' => $this->orgA->id,
            'user_id' => $this->userA->id,
            'email' => $this->userA->email,
            'status' => 'ACTIVE',
            'basic_salary' => 6000000,
            'bank_name' => 'BNI',
            'bank_account_number' => '1122334455',
            'bank_account_holder' => 'User A Employee',
        ]);

        $this->employeeB = Employee::factory()->create([
            'organization_id' => $this->orgB->id,
            'user_id' => $this->userB->id,
            'email' => $this->userB->email,
            'status' => 'ACTIVE',
            'basic_salary' => 7000000,
            'bank_name' => 'MANDIRI',
            'bank_account_number' => '9988776655',
            'bank_account_holder' => 'User B Employee',
        ]);
    }

    // ==========================================
    // P-01 to P-19: Payroll Core Isolation Matrix
    // ==========================================

    public function test_p01_unit_actor_cannot_list_payroll_records_from_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Index')
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $payrollA->id)
            );
    }

    public function test_p02_unit_actor_cannot_filter_payroll_by_foreign_organization_id(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $this->actingAs($this->userA)
            ->get(route('payrolls.index', ['organization_id' => $this->orgB->id]))
            ->assertForbidden();
    }

    public function test_p03_global_actor_can_view_all_payrolls_and_filter_by_organization(): void
    {
        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
        ]);

        // Without filter: sees both
        $this->actingAs($this->globalUser)
            ->get(route('payrolls.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Index')
                ->has('payrolls.data', 2)
            );

        // With orgA filter: sees only orgA
        $this->actingAs($this->globalUser)
            ->get(route('payrolls.index', ['organization_id' => $this->orgA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Index')
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $payrollA->id)
            );
    }

    public function test_p04_payroll_stats_are_strictly_scoped_to_unit_actor_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
            'net_salary' => 5000000,
        ]);
        Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
            'net_salary' => 9000000,
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.index', ['period' => '2026-03']), [
                'X-Inertia-Partial-Component' => 'Payroll/Index',
                'X-Inertia-Partial-Data' => 'stats',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total_records', 1)
                ->where('stats.total_net_salary', 5000000)
            );
    }

    public function test_p05_unit_actor_cannot_view_payroll_detail_belonging_to_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.show', $payrollB->id))
            ->assertNotFound();
    }

    public function test_p06_global_actor_can_view_payroll_detail_for_any_organization(): void
    {
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);

        $this->actingAs($this->globalUser)
            ->get(route('payrolls.show', $payrollB->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Show')
                ->where('payroll.id', $payrollB->id)
            );
    }

    public function test_p07_unit_actor_cannot_generate_payroll_for_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->post(route('payrolls.generate'), [
                'period' => '2026-03',
                'organization_id' => $this->orgB->id,
            ])
            ->assertForbidden();
    }

    public function test_p08_unit_actor_generating_payroll_infers_own_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->post(route('payrolls.generate'), [
                'period' => '2026-03',
            ])
            ->assertRedirect(route('payrolls.index', [
                'period' => '2026-03',
                'organization_id' => $this->orgA->id,
            ]));

        $this->assertDatabaseHas('payrolls', [
            'organization_id' => $this->orgA->id,
            'employee_id' => $this->employeeA->id,
            'period' => '2026-03',
        ]);
    }

    public function test_p09_user_without_organization_and_without_global_permission_cannot_generate_payroll(): void
    {
        $this->nullOrgUser->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->nullOrgUser)
            ->post(route('payrolls.generate'), [
                'period' => '2026-03',
                'organization_id' => $this->orgA->id,
            ])
            ->assertForbidden();
    }

    public function test_p10_global_actor_can_generate_payroll_for_specified_organization(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->globalUser)
            ->post(route('payrolls.generate'), [
                'period' => '2026-03',
                'organization_id' => $this->orgA->id,
            ])
            ->assertRedirect(route('payrolls.index', [
                'period' => '2026-03',
                'organization_id' => $this->orgA->id,
            ]));

        $this->assertDatabaseHas('payrolls', [
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
        ]);
    }

    public function test_p11_global_actor_cannot_generate_payroll_without_specifying_organization(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->globalUser)
            ->post(route('payrolls.generate'), [
                'period' => '2026-03',
            ])
            ->assertSessionHasErrors('organization_id');
    }

    public function test_p12_unit_actor_cannot_download_payroll_pdf_belonging_to_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.download-pdf', $payrollB->id))
            ->assertNotFound();
    }

    public function test_p13_employee_with_view_own_payslip_can_download_own_payroll_pdf(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::OWN_PAYSLIP_VIEW->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.download-pdf', $payrollA->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_p14_employee_cannot_download_another_employee_payroll_pdf_in_same_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::OWN_PAYSLIP_VIEW->value);

        $colleague = Employee::factory()->create([
            'organization_id' => $this->orgA->id,
        ]);
        $payrollColleague = Payroll::factory()->create([
            'employee_id' => $colleague->id,
            'organization_id' => $this->orgA->id,
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.download-pdf', $payrollColleague->id))
            ->assertForbidden();
    }

    public function test_p15_unit_actor_cannot_submit_foreign_payroll_for_approval(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollB->id],
                'notes' => 'Attempting cross-tenant approval submission',
            ])
            ->assertNotFound();
    }

    public function test_p16_unit_actor_can_submit_own_organization_draft_payroll_for_approval(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA->id],
                'notes' => 'Submission notes for Org A',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payroll_approvals', [
            'payroll_id' => $payrollA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requester_id' => $this->userA->id,
        ]);
    }

    public function test_p15a_atomic_submission_appended_foreign_payroll_fails_with_zero_approvals(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA->id, $payrollB->id],
                'notes' => 'Attempting mixed batch submission [A valid, B foreign]',
            ])
            ->assertNotFound();

        $this->assertSame(0, PayrollApproval::count());
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollA->id]);
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollB->id]);
        $this->assertSame(PayrollStatus::Draft->value, $payrollA->fresh()->status);
    }

    public function test_p15b_atomic_submission_prepended_foreign_payroll_fails_with_zero_approvals(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollB->id, $payrollA->id],
                'notes' => 'Attempting mixed batch submission [B foreign, A valid]',
            ])
            ->assertNotFound();

        $this->assertSame(0, PayrollApproval::count());
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollA->id]);
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollB->id]);
        $this->assertSame(PayrollStatus::Draft->value, $payrollA->fresh()->status);
    }

    public function test_p15c_global_actor_cannot_create_mixed_organization_approval_batch(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_VIEW_ALL->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->globalUser)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA->id, $payrollB->id],
                'notes' => 'Global actor attempting mixed organization batch',
            ])
            ->assertSessionHasErrors('payroll_ids');

        $this->assertSame(0, PayrollApproval::count());
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollA->id]);
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollB->id]);
    }

    public function test_p16a_unit_actor_submitting_multiple_own_payrolls_creates_single_atomic_batch(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA1 = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $employeeA2 = Employee::factory()->create(['organization_id' => $this->orgA->id]);
        $payrollA2 = Payroll::factory()->create([
            'employee_id' => $employeeA2->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA1->id, $payrollA2->id],
                'notes' => 'Submitting batch of 2 payrolls for Org A',
            ])
            ->assertSessionHas('success');

        $approvals = PayrollApproval::whereIn('payroll_id', [$payrollA1->id, $payrollA2->id])->get();
        $this->assertCount(2, $approvals);
        $this->assertSame(1, $approvals->pluck('payroll_batch_id')->unique()->count());
        $this->assertSame($this->userA->id, $approvals[0]->requester_id);
        $this->assertSame($this->userA->id, $approvals[1]->requester_id);
        $this->assertSame(PayrollApprovalStatus::Pending->value, $approvals[0]->status);
        $this->assertSame(PayrollApprovalStatus::Pending->value, $approvals[1]->status);
    }

    public function test_p17_unit_actor_cannot_export_bank_transfer_for_batch_of_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Approved->value,
        ]);

        $batchId = 'batch-org-b-999';
        PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userB->id,
            'approver_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.export-bank', ['batch' => $batchId, 'bank' => 'mandiri']))
            ->assertSessionHas('error');
    }

    public function test_p18_unit_actor_can_export_bank_transfer_for_approved_batch_within_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'net_salary' => 5500000,
            'status' => PayrollStatus::Approved->value,
        ]);

        $batchId = 'batch-org-a-111';
        PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userA->id,
            'approver_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        // Export BNI
        $response = $this->actingAs($this->userA)
            ->get(route('payrolls.export-bank', ['batch' => $batchId, 'bank' => 'bni']));
        $response->assertOk();
        $this->assertStringContainsString('1122334455', $response->getContent());
        $this->assertStringContainsString('5500000.00', $response->getContent());
    }

    public function test_p18a_mixed_organization_batch_fails_closed_without_partial_export_for_unit_actor(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'net_salary' => 5000000,
            'status' => PayrollStatus::Approved->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'net_salary' => 7000000,
            'status' => PayrollStatus::Approved->value,
        ]);

        $batchId = 'mixed-batch-fail-closed-unit';
        PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userA->id,
            'approver_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);
        PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userB->id,
            'approver_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->userA)
            ->get(route('payrolls.export-bank', ['batch' => $batchId, 'bank' => 'bni']));

        $response->assertSessionHas('error');
        $this->assertFalse($response->headers->has('content-disposition'));
        $content = (string) $response->getContent();
        $this->assertStringNotContainsString('1122334455', $content);
        $this->assertStringNotContainsString('9988776655', $content);
        $this->assertStringNotContainsString('5000000', $content);
        $this->assertStringNotContainsString('7000000', $content);
    }

    public function test_p18b_mixed_organization_batch_fails_closed_for_global_actor(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_VIEW_ALL->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'net_salary' => 5000000,
            'status' => PayrollStatus::Approved->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'net_salary' => 7000000,
            'status' => PayrollStatus::Approved->value,
        ]);

        $batchId = 'mixed-batch-fail-closed-global';
        PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userA->id,
            'approver_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);
        PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userB->id,
            'approver_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->globalUser)
            ->get(route('payrolls.export-bank', ['batch' => $batchId, 'bank' => 'bni']));

        $response->assertSessionHas('error');
        $this->assertFalse($response->headers->has('content-disposition'));
        $content = (string) $response->getContent();
        $this->assertStringNotContainsString('1122334455', $content);
        $this->assertStringNotContainsString('9988776655', $content);
        $this->assertStringNotContainsString('5000000', $content);
        $this->assertStringNotContainsString('7000000', $content);
    }

    public function test_p18c_same_org_approved_batch_exports_all_members_completely(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $employeeA2 = Employee::factory()->create([
            'organization_id' => $this->orgA->id,
            'bank_name' => 'BNI',
            'bank_account_number' => '1122339999',
            'bank_account_holder' => 'Second Employee',
        ]);

        $payrollA1 = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'net_salary' => 5500000,
            'status' => PayrollStatus::Approved->value,
        ]);
        $payrollA2 = Payroll::factory()->create([
            'employee_id' => $employeeA2->id,
            'organization_id' => $this->orgA->id,
            'net_salary' => 6500000,
            'status' => PayrollStatus::Approved->value,
        ]);

        $batchId = 'batch-org-a-complete';
        PayrollApproval::create([
            'payroll_id' => $payrollA1->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userA->id,
            'approver_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);
        PayrollApproval::create([
            'payroll_id' => $payrollA2->id,
            'payroll_batch_id' => $batchId,
            'requester_id' => $this->userA->id,
            'approver_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Approved->value,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->userA)
            ->get(route('payrolls.export-bank', ['batch' => $batchId, 'bank' => 'bni']));

        $response->assertOk();
        $this->assertTrue($response->headers->has('content-disposition'));
        $content = (string) $response->getContent();
        $this->assertStringContainsString('1122334455', $content);
        $this->assertStringContainsString('1122339999', $content);
        $this->assertStringContainsString('5500000.00', $content);
        $this->assertStringContainsString('6500000.00', $content);
    }

    public function test_p19_actor_without_organization_and_without_view_payroll_all_fails_closed_on_export(): void
    {
        $this->nullOrgUser->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $this->actingAs($this->nullOrgUser)
            ->get(route('payrolls.export-bank', ['batch' => 'any-batch', 'bank' => 'bni']))
            ->assertForbidden();
    }

    // ==========================================
    // T-01 to T-09: THR Isolation Matrix
    // ==========================================

    public function test_t01_unit_actor_cannot_list_thr_records_from_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $thrA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'is_thr' => true,
            'thr_amount' => 5000000,
            'period' => '2026-05',
        ]);
        $thrB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'is_thr' => true,
            'thr_amount' => 7000000,
            'period' => '2026-05',
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.thr'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Thr')
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $thrA->id)
            );
    }

    public function test_t02_unit_actor_cannot_filter_thr_by_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $this->actingAs($this->userA)
            ->get(route('payrolls.thr', ['organization_id' => $this->orgB->id]))
            ->assertForbidden();
    }

    public function test_t03_global_actor_can_view_all_thr_and_filter_by_organization(): void
    {
        $thrA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'is_thr' => true,
            'thr_amount' => 5000000,
            'period' => '2026-05',
        ]);
        $thrB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'is_thr' => true,
            'thr_amount' => 7000000,
            'period' => '2026-05',
        ]);

        $this->actingAs($this->globalUser)
            ->get(route('payrolls.thr'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Thr')
                ->has('payrolls.data', 2)
            );

        $this->actingAs($this->globalUser)
            ->get(route('payrolls.thr', ['organization_id' => $this->orgA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Thr')
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $thrA->id)
            );
    }

    public function test_t04_thr_stats_are_strictly_scoped_to_unit_actor_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'is_thr' => true,
            'thr_amount' => 5000000,
            'period' => '2026-05',
        ]);
        Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'is_thr' => true,
            'thr_amount' => 7000000,
            'period' => '2026-05',
        ]);

        $this->actingAs($this->userA)
            ->get(route('payrolls.thr', ['year' => '2026']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Thr')
                ->where('stats.total_thr', 5000000)
            );
    }

    public function test_t05_unit_actor_cannot_preview_thr_for_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->postJson(route('payrolls.thr.preview'), [
                'year' => 2026,
                'organization_id' => $this->orgB->id,
            ])
            ->assertForbidden();
    }

    public function test_t06_unit_actor_previewing_thr_infers_own_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->postJson(route('payrolls.thr.preview'), [
                'year' => 2026,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'total_employees',
                'total_thr',
                'organization_name',
                'breakdown',
            ])
            ->assertJsonPath('organization_name', $this->orgA->name);
    }

    public function test_t07_unit_actor_cannot_generate_thr_for_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->post(route('payrolls.thr.generate'), [
                'year' => 2026,
                'organization_id' => $this->orgB->id,
            ])
            ->assertForbidden();
    }

    public function test_t08_unit_actor_can_generate_thr_for_own_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->userA)
            ->post(route('payrolls.thr.generate'), [
                'year' => 2026,
            ])
            ->assertRedirect(route('payrolls.thr', [
                'year' => 2026,
                'organization_id' => $this->orgA->id,
            ]));

        $this->assertDatabaseHas('payrolls', [
            'organization_id' => $this->orgA->id,
            'employee_id' => $this->employeeA->id,
            'is_thr' => true,
            'period' => '2026-05',
        ]);
    }

    public function test_t09_user_without_organization_and_without_view_payroll_all_cannot_manage_thr(): void
    {
        $this->nullOrgUser->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $this->actingAs($this->nullOrgUser)
            ->postJson(route('payrolls.thr.preview'), [
                'year' => 2026,
            ])
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->post(route('payrolls.thr.generate'), [
                'year' => 2026,
            ])
            ->assertForbidden();
    }

    // ==========================================
    // A-01 to A-09: Payroll Approvals Matrix
    // ==========================================

    public function test_a01_unit_actor_cannot_list_approvals_from_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
        ]);
        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);
        $approvalB = PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->get(route('payroll-approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Approval')
                ->has('approvals.data', 1)
                ->where('approvals.data.0.id', $approvalA->id)
            );
    }

    public function test_a02_unit_actor_cannot_filter_approvals_by_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $this->actingAs($this->userA)
            ->get(route('payroll-approvals.index', ['organization_id' => $this->orgB->id]))
            ->assertForbidden();
    }

    public function test_a03_global_actor_can_view_all_approvals_and_filter_by_organization(): void
    {
        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
        ]);
        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);
        $approvalB = PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->globalUser)
            ->get(route('payroll-approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Approval')
                ->has('approvals.data', 2)
            );

        $this->actingAs($this->globalUser)
            ->get(route('payroll-approvals.index', ['organization_id' => $this->orgA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Approval')
                ->has('approvals.data', 1)
                ->where('approvals.data.0.id', $approvalA->id)
            );
    }

    public function test_a04_approval_stats_are_strictly_scoped_to_unit_actor_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
        ]);
        PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
        ]);
        PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->get(route('payroll-approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.pending_count', 1)
            );
    }

    public function test_a05_unit_actor_cannot_approve_approval_of_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approvalB = PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->post(route('payroll-approvals.approve', $approvalB->id), [
                'notes' => 'Attempting cross-tenant approval',
            ])
            ->assertNotFound();
    }

    public function test_a06_unit_actor_cannot_reject_approval_of_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approvalB = PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->post(route('payroll-approvals.reject', $approvalB->id), [
                'notes' => 'Attempting cross-tenant rejection',
            ])
            ->assertNotFound();
    }

    public function test_a07_unit_actor_can_approve_approval_within_organization(): void
    {
        $approver = User::factory()->create(['organization_id' => $this->orgA->id]);
        $approver->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('payroll-approvals.approve', $approvalA->id), [
                'notes' => 'Approved by Org A approver',
            ])
            ->assertSessionHas('success');

        $this->assertSame(PayrollApprovalStatus::Approved->value, $approvalA->fresh()->status);
        $this->assertSame(PayrollStatus::Approved->value, $payrollA->fresh()->status);
    }

    public function test_a08_unit_actor_can_reject_approval_within_organization(): void
    {
        $approver = User::factory()->create(['organization_id' => $this->orgA->id]);
        $approver->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('payroll-approvals.reject', $approvalA->id), [
                'notes' => 'Rejected by Org A approver',
            ])
            ->assertSessionHas('success');

        $this->assertSame(PayrollApprovalStatus::Rejected->value, $approvalA->fresh()->status);
        $this->assertSame(PayrollStatus::Draft->value, $payrollA->fresh()->status);
    }

    public function test_a09_requester_cannot_approve_own_submission_segregation_of_duties(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_APPROVE->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->userA)
            ->post(route('payroll-approvals.approve', $approvalA->id), [
                'notes' => 'Self-approval attempt',
            ])
            ->assertSessionHasErrors('approved_by');

        $this->assertSame(PayrollApprovalStatus::Pending->value, $approvalA->fresh()->status);
    }

    // ==========================================
    // E-01 to E-04: ESS Isolation Matrix
    // ==========================================

    public function test_e01_ess_user_only_sees_own_payslips_in_portal(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::ESS_PORTAL_ACCESS->value);

        $payrollOwn = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'period' => '2026-03',
        ]);
        $payrollOther = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
        ]);

        $this->actingAs($this->userA)
            ->get(route('ess.payslips'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ESS/Payslips')
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $payrollOwn->id)
            );
    }

    public function test_e02_ess_mobile_user_only_sees_own_payslips_in_api(): void
    {
        Sanctum::actingAs($this->userA, ['ess:read']);

        $payrollOwn = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => 'PAID',
            'period' => '2026-03',
        ]);
        $payrollOther = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => 'PAID',
            'period' => '2026-03',
        ]);

        $response = $this->getJson('/api/ess/payslips');
        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $payrollOwn->id);
    }

    public function test_e03_ess_mobile_user_cannot_download_foreign_employee_payslip(): void
    {
        Sanctum::actingAs($this->userA, ['ess:read']);

        $payrollOther = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => 'PAID',
        ]);

        $this->get("/api/ess/payslips/{$payrollOther->id}/download")
            ->assertForbidden();
    }

    public function test_e04_signed_payslip_download_url_enforces_tenant_isolation_for_unit_staff(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
        ]);

        $signedUrl = URL::temporarySignedRoute('download.payslip', now()->addMinutes(30), ['id' => $payrollB->id]);

        $this->actingAs($this->userA)
            ->get($signedUrl)
            ->assertForbidden();
    }

    // ==========================================
    // S-01 to S-08: Salary Structure Matrix
    // ==========================================

    public function test_s01_unit_actor_cannot_list_salary_structures_from_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();

        $structA = SalaryStructure::factory()->create([
            'organization_id' => $this->orgA->id,
            'job_grade_id' => $grade->id,
        ]);
        $structB = SalaryStructure::factory()->create([
            'organization_id' => $this->orgB->id,
            'job_grade_id' => $grade->id,
        ]);

        $this->actingAs($this->userA)
            ->get(route('salary-structures.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 1)
                ->where('structures.data.0.id', $structA->id)
            );
    }

    public function test_s02_unit_actor_cannot_filter_salary_structures_by_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);

        $this->actingAs($this->userA)
            ->get(route('salary-structures.index', ['organization_id' => $this->orgB->id]))
            ->assertForbidden();
    }

    public function test_s03_global_actor_can_view_all_salary_structures_and_filter(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();

        $structA = SalaryStructure::factory()->create([
            'organization_id' => $this->orgA->id,
            'job_grade_id' => $grade->id,
        ]);
        $structB = SalaryStructure::factory()->create([
            'organization_id' => $this->orgB->id,
            'job_grade_id' => $grade->id,
        ]);

        $this->actingAs($this->globalUser)
            ->get(route('salary-structures.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 2)
            );

        $this->actingAs($this->globalUser)
            ->get(route('salary-structures.index', ['organization_id' => $this->orgA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 1)
                ->where('structures.data.0.id', $structA->id)
            );
    }

    public function test_s04_unit_actor_cannot_create_salary_structure_for_foreign_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $componentType = SalaryComponentType::factory()->create();

        $this->actingAs($this->userA)
            ->post(route('salary-structures.store'), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => $this->orgB->id,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'items' => [
                    ['component_type_id' => $componentType->id, 'amount' => 5000000],
                ],
            ])
            ->assertForbidden();
    }

    public function test_s05_unit_actor_creating_salary_structure_infers_own_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $componentType = SalaryComponentType::factory()->create();

        $this->actingAs($this->userA)
            ->post(route('salary-structures.store'), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'items' => [
                    ['component_type_id' => $componentType->id, 'amount' => 5000000],
                ],
            ])
            ->assertRedirect(route('salary-structures.index'));

        $this->assertDatabaseHas('salary_structures', [
            'organization_id' => $this->orgA->id,
            'job_grade_id' => $grade->id,
        ]);
    }

    public function test_s06_unit_actor_cannot_update_salary_structure_belonging_to_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $componentType = SalaryComponentType::factory()->create();

        $structB = SalaryStructure::factory()->create([
            'organization_id' => $this->orgB->id,
            'job_grade_id' => $grade->id,
        ]);

        $this->actingAs($this->userA)
            ->put(route('salary-structures.update', $structB->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'items' => [
                    ['component_type_id' => $componentType->id, 'amount' => 6000000],
                ],
            ])
            ->assertNotFound();
    }

    public function test_s07_unit_actor_cannot_delete_salary_structure_belonging_to_another_organization(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();

        $structB = SalaryStructure::factory()->create([
            'organization_id' => $this->orgB->id,
            'job_grade_id' => $grade->id,
        ]);

        $this->actingAs($this->userA)
            ->delete(route('salary-structures.destroy', $structB->id))
            ->assertNotFound();

        $this->assertDatabaseHas('salary_structures', ['id' => $structB->id]);
    }

    public function test_s08_user_without_organization_and_without_view_payroll_all_cannot_manage_salary_structures(): void
    {
        $this->nullOrgUser->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $componentType = SalaryComponentType::factory()->create();

        $this->actingAs($this->nullOrgUser)
            ->post(route('salary-structures.store'), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'items' => [
                    ['component_type_id' => $componentType->id, 'amount' => 5000000],
                ],
            ])
            ->assertForbidden();
    }

    public function test_s09_payroll_lookup_uses_global_salary_structure_when_no_org_specific_structure_exists(): void
    {
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create(['code' => 'BASE']);

        // Explicit NULL organization_id
        $globalStruct = SalaryStructure::factory()->create([
            'organization_id' => null,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'min_tenure_months' => 0,
            'max_tenure_months' => null,
            'effective_from' => '2026-01-01',
        ]);
        $globalStruct->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 4200000,
        ]);

        $this->employeeA->update([
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'hire_date' => '2026-01-01',
        ]);

        $resolved = SalaryStructure::lookupFor($this->employeeA, Carbon::parse('2026-06-01'));
        $this->assertNotNull($resolved);
        $this->assertSame($globalStruct->id, $resolved->id);
        $this->assertNull($resolved->organization_id);
        $this->assertEquals(4200000, $resolved->totalGross());
    }

    public function test_s10_payroll_lookup_prefers_org_specific_structure_over_global_structure(): void
    {
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create(['code' => 'BASE']);

        $globalStruct = SalaryStructure::factory()->create([
            'organization_id' => null,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'effective_from' => '2026-01-01',
        ]);
        $globalStruct->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 4000000,
        ]);

        $orgAStruct = SalaryStructure::factory()->create([
            'organization_id' => $this->orgA->id,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'effective_from' => '2026-01-01',
        ]);
        $orgAStruct->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 6500000,
        ]);

        $this->employeeA->update([
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'hire_date' => '2026-01-01',
        ]);
        $this->employeeB->update([
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'hire_date' => '2026-01-01',
        ]);

        // Employee A gets Org A specific structure
        $resolvedA = SalaryStructure::lookupFor($this->employeeA, Carbon::parse('2026-06-01'));
        $this->assertNotNull($resolvedA);
        $this->assertSame($orgAStruct->id, $resolvedA->id);
        $this->assertSame($this->orgA->id, $resolvedA->organization_id);
        $this->assertEquals(6500000, $resolvedA->totalGross());

        // Employee B (Org B has no specific structure) falls back to global structure
        $resolvedB = SalaryStructure::lookupFor($this->employeeB, Carbon::parse('2026-06-01'));
        $this->assertNotNull($resolvedB);
        $this->assertSame($globalStruct->id, $resolvedB->id);
        $this->assertNull($resolvedB->organization_id);
        $this->assertEquals(4000000, $resolvedB->totalGross());
    }

    public function test_s11_tenant_actor_cannot_mutate_or_delete_global_salary_structure(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create();

        $globalStruct = SalaryStructure::factory()->create([
            'organization_id' => null,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'min_tenure_months' => 0,
            'effective_from' => '2026-01-01',
        ]);
        $globalStruct->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 4000000,
        ]);

        // Attempt update
        $this->actingAs($this->userA)
            ->put(route('salary-structures.update', $globalStruct->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'min_tenure_months' => 12,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 9999999],
                ],
            ])
            ->assertNotFound();

        $globalStruct->refresh();
        $this->assertSame(0, $globalStruct->min_tenure_months);
        $this->assertEquals(4000000, $globalStruct->items->first()->amount);

        // Attempt delete
        $this->actingAs($this->userA)
            ->delete(route('salary-structures.destroy', $globalStruct->id))
            ->assertNotFound();

        $this->assertDatabaseHas('salary_structures', ['id' => $globalStruct->id]);
    }

    public function test_s12_tenant_actor_cannot_rebind_own_salary_structure_to_global_or_foreign_scope(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create();

        $structA = SalaryStructure::factory()->create([
            'organization_id' => $this->orgA->id,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'effective_from' => '2026-01-01',
        ]);
        $structA->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 5000000,
        ]);

        // Attempt to convert to global (organization_id = null)
        $this->actingAs($this->userA)
            ->put(route('salary-structures.update', $structA->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => null,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 5000000],
                ],
            ])
            ->assertForbidden();

        $structA->refresh();
        $this->assertSame($this->orgA->id, $structA->organization_id);

        // Attempt to rebind to foreign org B
        $this->actingAs($this->userA)
            ->put(route('salary-structures.update', $structA->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => $this->orgB->id,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 5000000],
                ],
            ])
            ->assertForbidden();

        $structA->refresh();
        $this->assertSame($this->orgA->id, $structA->organization_id);
    }

    public function test_s13_tenant_actor_cannot_take_ownership_of_global_salary_structure(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create();

        $globalStruct = SalaryStructure::factory()->create([
            'organization_id' => null,
            'employee_type' => 'Organic',
            'job_grade_id' => $grade->id,
            'effective_from' => '2026-01-01',
        ]);
        $globalStruct->items()->create([
            'salary_component_type_id' => $comp->id,
            'amount' => 4000000,
        ]);

        // Tenant user tries to update global structure with own org ID
        $this->actingAs($this->userA)
            ->put(route('salary-structures.update', $globalStruct->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => $this->orgA->id,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 4000000],
                ],
            ])
            ->assertNotFound();

        $globalStruct->refresh();
        $this->assertNull($globalStruct->organization_id);
    }

    public function test_s14_authorized_central_administrator_can_create_update_and_delete_global_salary_structure(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_VIEW_ALL->value);
        $grade = JobGrade::factory()->create();
        $comp = SalaryComponentType::factory()->create();

        // 1. Create global structure
        $this->actingAs($this->globalUser)
            ->post(route('salary-structures.store'), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => null,
                'min_tenure_months' => 0,
                'max_tenure_months' => 36,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 4500000],
                ],
            ])
            ->assertRedirect(route('salary-structures.index'));

        $created = SalaryStructure::whereNull('organization_id')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->organization_id);
        $this->assertSame(36, $created->max_tenure_months);
        $this->assertEquals(4500000, $created->items->first()->amount);

        // 2. Update global structure
        $this->actingAs($this->globalUser)
            ->put(route('salary-structures.update', $created->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $grade->id,
                'organization_id' => null,
                'min_tenure_months' => 6,
                'max_tenure_months' => null,
                'effective_from' => '2026-01-01',
                'items' => [
                    ['component_type_id' => $comp->id, 'amount' => 5000000],
                ],
            ])
            ->assertRedirect(route('salary-structures.index'));

        $created->refresh();
        $this->assertNull($created->organization_id);
        $this->assertSame(6, $created->min_tenure_months);
        $this->assertNull($created->max_tenure_months);
        $this->assertEquals(5000000, $created->items->first()->amount);

        // 3. Delete global structure
        $this->actingAs($this->globalUser)
            ->delete(route('salary-structures.destroy', $created->id))
            ->assertRedirect(route('salary-structures.index'));

        $this->assertDatabaseMissing('salary_structures', ['id' => $created->id]);
    }

    public function test_s15_authorized_central_administrator_can_view_and_filter_global_salary_structures(): void
    {
        $this->globalUser->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);
        $this->globalUser->givePermissionTo(PermissionEnum::PAYROLL_VIEW_ALL->value);
        $this->userA->givePermissionTo(PermissionEnum::SALARY_STRUCTURES_MANAGE->value);

        $grade = JobGrade::factory()->create();

        $globalStruct = SalaryStructure::factory()->create([
            'organization_id' => null,
            'job_grade_id' => $grade->id,
        ]);
        SalaryStructure::factory()->create([
            'organization_id' => $this->orgA->id,
            'job_grade_id' => $grade->id,
        ]);

        // Global user sees both global and org-specific
        $this->actingAs($this->globalUser)
            ->get(route('salary-structures.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 2)
            );

        // Global user filters by global
        $this->actingAs($this->globalUser)
            ->get(route('salary-structures.index', ['organization_id' => 'global']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 1)
                ->where('structures.data.0.id', $globalStruct->id)
            );

        // Tenant user cannot filter by global
        $this->actingAs($this->userA)
            ->get(route('salary-structures.index', ['organization_id' => 'global']))
            ->assertForbidden();
    }

    // ==========================================
    // R-01 to R-04: Reports Isolation Matrix
    // ==========================================

    public function test_r01_unit_actor_cannot_download_payslip_of_foreign_employee_in_reports(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::REPORTS_VIEW->value);
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'period' => '2026-03',
        ]);

        $this->actingAs($this->userA)
            ->get("/projects/1/api/reports/payslip/{$this->employeeB->id}/2026-03")
            ->assertNotFound();
    }

    public function test_r02_unit_actor_payroll_summary_report_is_strictly_scoped(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::REPORTS_VIEW->value);
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        // Attempting to query foreign organization explicitly throws 403
        $this->actingAs($this->userA)
            ->get("/projects/1/api/reports/payroll-summary?period_from=2026-01&period_to=2026-03&organization_id={$this->orgB->id}")
            ->assertForbidden();
    }

    public function test_r03_unit_actor_payroll_detail_report_is_strictly_scoped(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::REPORTS_VIEW->value);
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        // Attempting to query foreign organization explicitly throws 403
        $this->actingAs($this->userA)
            ->get("/projects/1/api/reports/payroll-detail?period=2026-03&organization_id={$this->orgB->id}")
            ->assertForbidden();
    }

    public function test_r04_user_without_view_payroll_all_cannot_access_consolidated_payroll(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::REPORTS_VIEW->value);
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_VIEW_UNIT->value);

        $this->actingAs($this->userA)
            ->getJson(route('reports.consolidated-payroll', [
                'period_from' => '2026-01',
                'period_to' => '2026-03',
            ]))
            ->assertForbidden();
    }

    // ==========================================
    // M-01 to M-06: Model & Service Scoping Matrix
    // ==========================================

    public function test_m01_payroll_model_contract_and_path(): void
    {
        $payroll = new Payroll;
        $this->assertInstanceOf(OrganizationScopedModel::class, $payroll);
        $this->assertSame('organization_id', $payroll->organizationScopePath());
        $this->assertSame('organization_id', $this->scopeService->pathFor($payroll));
    }

    public function test_m02_payroll_approval_model_contract_and_path(): void
    {
        $approval = new PayrollApproval;
        $this->assertInstanceOf(OrganizationScopedModel::class, $approval);
        $this->assertSame('payroll.organization_id', $approval->organizationScopePath());
        $this->assertSame('payroll.organization_id', $this->scopeService->pathFor($approval));
    }

    public function test_m03_salary_structure_model_contract_and_path(): void
    {
        $structure = new SalaryStructure;
        $this->assertInstanceOf(OrganizationScopedModel::class, $structure);
        $this->assertSame('organization_id', $structure->organizationScopePath());
        $this->assertSame('organization_id', $this->scopeService->pathFor($structure));
    }

    public function test_m04_thr_entitlement_model_contract_and_path(): void
    {
        $entitlement = new ThrEntitlement;
        $this->assertInstanceOf(OrganizationScopedModel::class, $entitlement);
        $this->assertSame('organization_id', $entitlement->organizationScopePath());
        $this->assertSame('organization_id', $this->scopeService->pathFor($entitlement));
    }

    public function test_m05_scope_service_has_registered_paths_and_global_permissions(): void
    {
        $paths = $this->scopeService->registeredPaths();
        $this->assertArrayHasKey(Payroll::class, $paths);
        $this->assertArrayHasKey(PayrollApproval::class, $paths);
        $this->assertArrayHasKey(SalaryStructure::class, $paths);
        $this->assertArrayHasKey(ThrEntitlement::class, $paths);

        $globals = $this->scopeService->registeredGlobalPermissions();
        $this->assertSame('view_payroll_all', $globals[Payroll::class]);
        $this->assertSame('view_payroll_all', $globals[PayrollApproval::class]);
        $this->assertSame('view_payroll_all', $globals[SalaryStructure::class]);
        $this->assertSame('view_payroll_all', $globals[ThrEntitlement::class]);
    }

    public function test_m06_scope_visible_to_isolates_payroll_domain_models(): void
    {
        $payrollA = Payroll::factory()->create(['organization_id' => $this->orgA->id]);
        $payrollB = Payroll::factory()->create(['organization_id' => $this->orgB->id]);

        $scopedPayrolls = $this->scopeService->scopeVisibleTo(Payroll::query(), $this->userA, 'view_payroll_all')->get();
        $this->assertTrue($scopedPayrolls->contains('id', $payrollA->id));
        $this->assertFalse($scopedPayrolls->contains('id', $payrollB->id));

        $approvalA = PayrollApproval::create([
            'payroll_id' => $payrollA->id,
            'payroll_batch_id' => 'batch-a',
            'requester_id' => $this->userA->id,
            'status' => 'PENDING',
            'requested_at' => now(),
        ]);
        $approvalB = PayrollApproval::create([
            'payroll_id' => $payrollB->id,
            'payroll_batch_id' => 'batch-b',
            'requester_id' => $this->userB->id,
            'status' => 'PENDING',
            'requested_at' => now(),
        ]);

        $scopedApprovals = $this->scopeService->scopeVisibleTo(PayrollApproval::query(), $this->userA, 'view_payroll_all')->get();
        $this->assertTrue($scopedApprovals->contains('id', $approvalA->id));
        $this->assertFalse($scopedApprovals->contains('id', $approvalB->id));
    }

    // ==========================================
    // R2-01 to R2-06: Approval Anti-Enumeration & Oracle Elimination (SEC-P1-09 R2)
    // ==========================================

    public function test_r2_01_submitting_foreign_existing_payroll_returns_404_not_found_with_zero_approvals(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollB->id],
                'notes' => 'Attempting submission of foreign payroll',
            ])
            ->assertNotFound();

        $this->assertSame(0, PayrollApproval::count());
        $this->assertDatabaseMissing('payroll_approvals', ['payroll_id' => $payrollB->id]);
        $this->assertSame(PayrollStatus::Draft->value, $payrollB->fresh()->status);
    }

    public function test_r2_02_submitting_nonexistent_numeric_payroll_returns_404_not_found_with_zero_approvals(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $nonexistentPayrollId = 99999999;
        $this->assertDatabaseMissing('payrolls', ['id' => $nonexistentPayrollId]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$nonexistentPayrollId],
                'notes' => 'Attempting submission of nonexistent payroll ID',
            ])
            ->assertNotFound();

        $this->assertSame(0, PayrollApproval::count());
    }

    public function test_r2_03_foreign_existing_and_nonexistent_payroll_ids_produce_identical_anti_enumeration_404_responses(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $nonexistentPayrollId = 99999998;
        $this->assertDatabaseMissing('payrolls', ['id' => $nonexistentPayrollId]);

        // Request 1: Foreign existing ID
        $foreignResponse = $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollB->id],
            ]);
        $foreignResponse->assertNotFound();

        // Request 2: Nonexistent ID
        $nonexistentResponse = $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$nonexistentPayrollId],
            ]);
        $nonexistentResponse->assertNotFound();

        // Anti-enumeration assertion: Both responses produce identical 404 status codes
        $this->assertSame($foreignResponse->status(), $nonexistentResponse->status());
        $this->assertSame(404, $foreignResponse->status());
        $this->assertSame(0, PayrollApproval::count());
    }

    public function test_r2_04_mixed_batches_with_valid_and_foreign_or_nonexistent_fail_with_zero_approvals_regardless_of_order(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $payrollB = Payroll::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $nonexistentId = 99999997;

        // Permutation 1: [valid_own, foreign_existing]
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA->id, $payrollB->id],
            ])
            ->assertNotFound();
        $this->assertSame(0, PayrollApproval::count());

        // Permutation 2: [foreign_existing, valid_own]
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollB->id, $payrollA->id],
            ])
            ->assertNotFound();
        $this->assertSame(0, PayrollApproval::count());

        // Permutation 3: [valid_own, nonexistent]
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA->id, $nonexistentId],
            ])
            ->assertNotFound();
        $this->assertSame(0, PayrollApproval::count());

        // Permutation 4: [nonexistent, valid_own]
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$nonexistentId, $payrollA->id],
            ])
            ->assertNotFound();
        $this->assertSame(0, PayrollApproval::count());

        // Defense in depth: Verify original valid payroll remains unmutated DRAFT
        $this->assertSame(PayrollStatus::Draft->value, $payrollA->fresh()->status);
        $this->assertSame(0, PayrollApproval::count());
    }

    public function test_r2_05_malformed_structural_validation_returns_422_unprocessable_entity(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        // Case 1: Missing payroll_ids
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'notes' => 'Missing payroll IDs',
            ])
            ->assertSessionHasErrors('payroll_ids');

        // Case 2: payroll_ids is not an array
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => 'not-an-array',
            ])
            ->assertSessionHasErrors('payroll_ids');

        // Case 3: payroll_ids item is not an integer
        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => ['string-id-instead-of-int'],
            ])
            ->assertSessionHasErrors('payroll_ids.0');

        $this->assertSame(0, PayrollApproval::count());
    }

    public function test_r2_06_unit_actor_submitting_valid_own_organization_payrolls_succeeds(): void
    {
        $this->userA->givePermissionTo(PermissionEnum::PAYROLL_PROCESS->value);

        $payrollA1 = Payroll::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $employeeA2 = Employee::factory()->create(['organization_id' => $this->orgA->id]);
        $payrollA2 = Payroll::factory()->create([
            'employee_id' => $employeeA2->id,
            'organization_id' => $this->orgA->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($this->userA)
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payrollA1->id, $payrollA2->id],
                'notes' => 'Valid approval submission for Org A',
            ])
            ->assertSessionHas('success');

        $approvals = PayrollApproval::whereIn('payroll_id', [$payrollA1->id, $payrollA2->id])->get();
        $this->assertCount(2, $approvals);
        $this->assertSame(1, $approvals->pluck('payroll_batch_id')->unique()->count());
        $this->assertSame($this->userA->id, $approvals[0]->requester_id);
        $this->assertSame($this->userA->id, $approvals[1]->requester_id);
        $this->assertSame(PayrollApprovalStatus::Pending->value, $approvals[0]->status);
        $this->assertSame(PayrollApprovalStatus::Pending->value, $approvals[1]->status);
    }
}
