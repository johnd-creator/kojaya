<?php

namespace Tests\Feature;

use App\Http\Middleware\LogActivity;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class P0SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_bypasses_registered_policies(): void
    {
        Role::create(['name' => 'System Admin']);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $employee = Employee::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $employee));
    }

    public function test_employee_policy_requires_employee_permission(): void
    {
        Permission::create(['name' => 'edit_employee']);
        $organization = Organization::factory()->create();
        $role = Role::create(['name' => 'HR Unit']);
        $role->givePermissionTo('edit_employee');
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($role);

        $employee = Employee::factory()->create([
            'organization_id' => Organization::factory()->create()->id,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('update', $employee));

        Permission::create(['name' => 'view_employee_unit']);
        $role->givePermissionTo('view_employee_unit');
        $employee->update(['organization_id' => $organization->id]);

        $this->assertTrue(Gate::forUser($user)->allows('update', $employee));
    }

    public function test_budget_form_requests_are_not_denied_by_default(): void
    {
        $this->assertTrue((new StoreBudgetRequest)->authorize());
        $this->assertTrue((new UpdateBudgetRequest)->authorize());
    }

    public function test_log_activity_does_not_log_mutating_requests_without_authenticated_user(): void
    {
        $auditLogService = Mockery::mock(AuditLogService::class);
        $auditLogService->shouldNotReceive('log');
        $middleware = new LogActivity($auditLogService);

        $response = $middleware->handle(Request::create('/employees/1', 'PUT'), function (): Response {
            return new Response('', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_leave_status_update_requires_hr_policy(): void
    {
        Role::create(['name' => 'Employee']);
        Role::create(['name' => 'HR Unit']);

        $organization = Organization::factory()->create();
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employeeUser->assignRole('Employee');
        $hrUser = User::factory()->create(['organization_id' => $organization->id]);
        $hrUser->assignRole('HR Unit');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $leaveType = LeaveType::create([
            'name' => 'Cuti Tahunan',
            'default_days_allowance' => 12,
            'requires_attachment' => false,
            'is_paid' => true,
        ]);
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'status' => 'Pending',
        ]);

        $this->actingAs($employeeUser)
            ->put(route('leaves.update-status', $leave), ['status' => 'Approved'])
            ->assertForbidden();

        $this->actingAs($hrUser)
            ->put(route('leaves.update-status', $leave), ['status' => 'Approved'])
            ->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'Approved',
            'approver_id' => $hrUser->id,
        ]);
    }

    public function test_api_tokens_must_have_matching_ability(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['cooperative:read']);

        $this->getJson('/api/user')->assertForbidden();

        Sanctum::actingAs($user, ['profile:read']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
