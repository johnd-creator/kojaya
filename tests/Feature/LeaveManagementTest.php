<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Employee', 'guard_name' => 'web']);
        Role::create(['name' => 'HR Unit', 'guard_name' => 'web']);
    }

    public function test_employee_can_submit_leave_request_and_working_days_are_calculated(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
        $user->assignRole('Employee');
        $leaveType = LeaveType::factory()->create(['requires_attachment' => false]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(4);

        $this->actingAs($user)
            ->from(route('leaves.self-service'))
            ->post(route('leaves.store'), [
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'reason' => 'Cuti tahunan keluarga',
            ])
            ->assertRedirect(route('leaves.self-service'));

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $user->employee->id,
            'leave_type_id' => $leaveType->id,
            'total_days' => 5,
            'status' => 'Pending',
        ]);
    }

    public function test_leave_request_requires_attachment_when_leave_type_requires_it(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
        $leaveType = LeaveType::factory()->create(['requires_attachment' => true]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);

        $this->actingAs($user)
            ->from(route('leaves.self-service'))
            ->post(route('leaves.store'), [
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->copy()->addDay()->toDateString(),
                'reason' => 'Cuti sakit tanpa lampiran',
            ])
            ->assertRedirect(route('leaves.self-service'))
            ->assertSessionHasErrors(['attachment']);

        $this->assertDatabaseCount('leaves', 0);
    }

    public function test_hr_unit_can_approve_leave_request(): void
    {
        $organization = Organization::factory()->create();
        $approver = User::factory()->create(['organization_id' => $organization->id]);
        $approver->assignRole('HR Unit');

        $leave = Leave::factory()->create();

        $this->actingAs($approver)
            ->put(route('leaves.update-status', $leave), [
                'status' => 'Approved',
            ])
            ->assertRedirect(route('leaves.index'));

        $leave->refresh();

        $this->assertSame('Approved', $leave->status);
        $this->assertSame($approver->id, $leave->approver_id);
    }

    public function test_employee_cannot_approve_leave_request(): void
    {
        $organization = Organization::factory()->create();
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employeeUser->assignRole('Employee');
        $leave = Leave::factory()->create();

        $this->actingAs($employeeUser)
            ->put(route('leaves.update-status', $leave), [
                'status' => 'Approved',
            ])
            ->assertForbidden();

        $leave->refresh();

        $this->assertSame('Pending', $leave->status);
        $this->assertNull($leave->approver_id);
    }
}
