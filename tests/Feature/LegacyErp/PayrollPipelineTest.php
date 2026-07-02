<?php

namespace Tests\Feature;

use App\Enums\PayrollApprovalStatus;
use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_generate_payroll_for_active_employees(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Pusat');
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'organization_id' => $organization->id,
            'basic_salary' => 8000000,
            'hire_date' => now()->subYear()->toDateString(),
            'npwp_number' => '123456789012345',
        ]);

        $this->actingAs($finance)
            ->post(route('payrolls.generate'), [
                'period' => now()->format('Y-m'),
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('payrolls.index', [
                'period' => now()->format('Y-m'),
                'organization_id' => $organization->id,
            ]));

        $payroll = Payroll::query()->where('employee_id', $employee->id)->first();

        $this->assertNotNull($payroll);
        $this->assertSame(PayrollStatus::Draft->value, $payroll->status);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_id' => $payroll->id,
            'description' => 'Gaji Pokok',
            'type' => 'EARNING',
        ]);
    }

    public function test_generate_payroll_validates_period_format(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Pusat');

        $this->actingAs($finance)
            ->from(route('payrolls.index'))
            ->post(route('payrolls.generate'), [
                'period' => '2026/05',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('payrolls.index'))
            ->assertSessionHasErrors(['period']);
    }

    public function test_finance_can_submit_payroll_and_approve_it(): void
    {
        $organization = Organization::factory()->create();
        $requester = User::factory()->create(['organization_id' => $organization->id]);
        $requester->assignRole('Finance Pusat');
        $approver = User::factory()->create(['organization_id' => $organization->id]);
        $approver->assignRole('Finance Pusat');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'basic_salary' => 7000000,
        ]);
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $this->actingAs($requester)
            ->from(route('payrolls.index'))
            ->post(route('payrolls.submit-approval'), [
                'payroll_ids' => [$payroll->id],
                'notes' => 'Siap disetujui',
            ])
            ->assertRedirect(route('payrolls.index'));

        $approval = PayrollApproval::query()->where('payroll_id', $payroll->id)->first();

        $this->assertNotNull($approval);
        $this->assertSame(PayrollApprovalStatus::Pending->value, $approval->status);

        $this->actingAs($approver)
            ->from(route('payroll-approvals.index'))
            ->post(route('payroll-approvals.approve', $approval), [
                'notes' => 'Disetujui finance pusat',
            ])
            ->assertRedirect(route('payroll-approvals.index'));

        $approval->refresh();
        $payroll->refresh();

        $this->assertSame(PayrollApprovalStatus::Approved->value, $approval->status);
        $this->assertSame($approver->id, $approval->approver_id);
        $this->assertSame(PayrollStatus::Approved->value, $payroll->status);
    }

    public function test_non_finance_user_cannot_approve_payroll_submission(): void
    {
        $organization = Organization::factory()->create();
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employeeUser->assignRole('Employee');
        $requester = User::factory()->create(['organization_id' => $organization->id]);
        $requester->assignRole('Finance Pusat');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'basic_salary' => 6000000,
        ]);
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        $approval = PayrollApproval::create([
            'id' => Str::uuid()->toString(),
            'payroll_id' => $payroll->id,
            'payroll_batch_id' => Str::uuid()->toString(),
            'requester_id' => $requester->id,
            'status' => PayrollApprovalStatus::Pending->value,
            'requester_notes' => 'Menunggu approval',
            'requested_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->post(route('payroll-approvals.approve', $approval), [
                'notes' => 'Saya coba approve',
            ])
            ->assertForbidden();

        $this->assertSame(PayrollApprovalStatus::Pending->value, $approval->fresh()->status);
        $this->assertSame(PayrollStatus::Draft->value, $payroll->fresh()->status);
    }

    public function test_submit_for_approval_does_not_create_duplicate_pending_approvals_for_same_payroll(): void
    {
        $organization = Organization::factory()->create();
        $requester = User::factory()->create(['organization_id' => $organization->id]);
        $requester->assignRole('Finance Pusat');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'basic_salary' => 6500000,
        ]);
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'status' => PayrollStatus::Draft->value,
        ]);

        $payload = [
            'payroll_ids' => [$payroll->id],
            'notes' => 'Kirim approval pertama',
        ];

        $this->actingAs($requester)
            ->from(route('payrolls.index'))
            ->post(route('payrolls.submit-approval'), $payload)
            ->assertRedirect(route('payrolls.index'));

        $this->actingAs($requester)
            ->from(route('payrolls.index'))
            ->post(route('payrolls.submit-approval'), $payload)
            ->assertRedirect(route('payrolls.index'));

        $this->assertSame(1, PayrollApproval::query()->where('payroll_id', $payroll->id)->where('status', PayrollApprovalStatus::Pending->value)->count());
    }

    public function test_generate_payroll_is_idempotent_for_same_period(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Pusat');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'basic_salary' => 8000000,
            'hire_date' => now()->subYear()->toDateString(),
            'npwp_number' => '123456789012345',
        ]);

        $payload = [
            'period' => now()->format('Y-m'),
            'organization_id' => $organization->id,
        ];

        $this->actingAs($finance)->post(route('payrolls.generate'), $payload)->assertRedirect();
        $this->actingAs($finance)->post(route('payrolls.generate'), $payload)->assertRedirect();

        $this->assertSame(1, Payroll::query()->where('employee_id', $employee->id)->where('period', $payload['period'])->count());
    }
}
