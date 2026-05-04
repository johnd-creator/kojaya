<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_attendance_index_and_record_attendance(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $otherEmployee = Employee::factory()->create(['organization_id' => $otherOrganization->id]);

        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'status' => 'PRESENT',
            'date' => '2026-02-10',
        ]);
        Attendance::factory()->create([
            'employee_id' => $otherEmployee->id,
            'organization_id' => $otherOrganization->id,
            'status' => 'ABSENT',
            'date' => '2026-02-10',
        ]);

        $this->actingAs($user)
            ->get(route('attendances.index', [
                'employee_id' => $employee->id,
                'organization_id' => $organization->id,
                'status' => 'PRESENT',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 1)
                ->where('attendances.data.0.employee_id', $employee->id)
                ->where('filters.status', 'PRESENT')
            );

        $this->actingAs($user)
            ->from(route('attendances.index'))
            ->post(route('attendances.store'), [
                'employee_id' => $employee->id,
                'organization_id' => $organization->id,
                'date' => '2026-02-11',
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'status' => 'PRESENT',
                'notes' => 'Masuk normal',
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'date' => '2026-02-11',
            'status' => 'PRESENT',
        ]);
    }

    public function test_employee_can_view_self_service_and_check_in_once(): void
    {
        $organization = Organization::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $workShift = WorkShift::factory()->create([
            'type' => 'NON_SHIFT',
            'is_flexible' => false,
            'end_time' => '17:00',
        ]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'work_shift_id' => $workShift->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-02-10 08:05:00'));

        $this->actingAs($user)
            ->get(route('attendance.self-service'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/SelfService')
                ->where('employee.id', $user->employee->id)
            );

        $this->actingAs($user)
            ->from(route('attendance.self-service'))
            ->post(route('attendance.check-in'), [])
            ->assertRedirect(route('attendance.self-service'));

        $attendance = Attendance::query()->where('employee_id', $user->employee->id)->where('date', '2026-02-10')->first();

        $this->assertNotNull($attendance);
        $this->assertSame('PRESENT', $attendance->status);
        $this->assertSame($workShift->id, $attendance->work_shift_id);
        $this->assertSame('17:00', $attendance->scheduled_end_time);

        $this->actingAs($user)
            ->from(route('attendance.self-service'))
            ->post(route('attendance.check-in'), [])
            ->assertRedirect(route('attendance.self-service'))
            ->assertSessionHas('error', 'You have already checked in today.');

        Carbon::setTestNow();
    }

    public function test_employee_can_check_out_and_overtime_is_calculated(): void
    {
        $organization = Organization::factory()->create([
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $workShift = WorkShift::factory()->create([
            'type' => 'NON_SHIFT',
            'is_flexible' => false,
            'end_time' => '17:00',
        ]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'work_shift_id' => $workShift->id,
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'organization_id' => $organization->id,
            'date' => '2026-02-10',
            'clock_in' => '08:00:00',
            'status' => 'PRESENT',
            'work_shift_id' => $workShift->id,
            'scheduled_end_time' => '17:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-02-10 18:30:00'));

        $this->actingAs($user)
            ->from(route('attendance.self-service'))
            ->post(route('attendances.checkOut'))
            ->assertRedirect(route('attendance.self-service'));

        $attendance = Attendance::query()->where('employee_id', $employee->id)->where('date', '2026-02-10')->first();

        $this->assertNotNull($attendance);
        $this->assertSame('18:30:00', $attendance->clock_out);
        $this->assertTrue((bool) $attendance->is_overtime);
        $this->assertSame(1.5, (float) $attendance->overtime_hours);

        Carbon::setTestNow();
    }

    public function test_employee_without_check_in_cannot_check_out(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->from(route('attendance.self-service'))
            ->post(route('attendances.checkOut'))
            ->assertRedirect(route('attendance.self-service'))
            ->assertSessionHas('error', 'You have not checked in today.');
    }
}
