<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class P1FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_cannot_reject_overtime_without_reason(): void
    {
        $organization = Organization::factory()->create();
        $hrUser = $this->createUserWithRole('HR Unit', $organization);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $rule = OvertimeRule::create([
            'organization_id' => $organization->id,
            'name' => 'Lembur Reguler',
            'code' => 'OT-REG',
            'multiplier' => 1.5,
            'requires_approval' => true,
        ]);

        $overtimeRequest = OvertimeRequest::create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'overtime_rule_id' => $rule->id,
            'date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'total_hours' => 2,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($hrUser)->from(route('overtime.index'))->post(route('overtime.reject', $overtimeRequest), [
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('overtime.index'));
        $response->assertSessionHasErrors([
            'rejection_reason' => 'Alasan penolakan wajib diisi.',
        ]);
    }

    public function test_reimbursement_reject_requires_rejection_reason(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $reimbursement = Reimbursement::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'submission_date' => now()->toDateString(),
            'total_amount' => 150000,
            'status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($user)->from(route('reimbursements.show', $reimbursement))->post(route('reimbursements.reject', $reimbursement), [
            'rejection_reason' => '',
        ]);

        $response->assertRedirect(route('reimbursements.show', $reimbursement));
        $response->assertSessionHasErrors([
            'rejection_reason' => 'Alasan penolakan wajib diisi.',
        ]);
    }

    public function test_hr_cannot_update_leave_status_with_invalid_value(): void
    {
        $organization = Organization::factory()->create();
        $hrUser = $this->createUserWithRole('HR Unit', $organization);
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

        $response = $this->actingAs($hrUser)->from(route('leaves.index'))->put(route('leaves.update-status', $leave), [
            'status' => 'Pending',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $response->assertSessionHasErrors([
            'status' => 'Status persetujuan harus berupa Approved atau Rejected.',
        ]);
    }

    private function createUserWithRole(string $roleName, Organization $organization): User
    {
        Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->assignRole($roleName);

        return $user;
    }
}
