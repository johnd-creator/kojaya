<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JobGrade;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkShift;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HrMasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // HR Pusat has manage_departments/positions/job_grades/work_shifts
        $this->hrUser = User::factory()->create();
        $this->hrUser->assignRole('HR Pusat');
    }

    public function test_user_can_filter_department_index_by_search_and_organization(): void
    {
        $user = $this->hrUser;
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        Department::factory()->create([
            'code' => 'FIN-01',
            'name' => 'Finance',
            'organization_id' => $organization->id,
        ]);
        Department::factory()->create([
            'code' => 'HR-01',
            'name' => 'Human Resource',
            'organization_id' => $organization->id,
        ]);
        Department::factory()->create([
            'code' => 'FIN-02',
            'name' => 'Finance Regional',
            'organization_id' => $otherOrganization->id,
        ]);

        $this->actingAs($user)
            ->get(route('departments.index', [
                'search' => 'FIN',
                'organization_id' => $organization->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Department/Index')
                ->has('departments.data', 1)
                ->where('departments.data.0.code', 'FIN-01')
                ->where('filters.search', 'FIN')
                ->where('filters.organization_id', $organization->id)
            );
    }

    public function test_user_can_create_update_and_delete_department(): void
    {
        $user = $this->hrUser;
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->from(route('departments.index'))
            ->post(route('departments.store'), [
                'code' => 'OPS-01',
                'name' => 'Operations',
                'description' => 'Tim operasional',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('departments.index'));

        $department = Department::query()->where('code', 'OPS-01')->first();
        $this->assertNotNull($department);

        $this->actingAs($user)
            ->from(route('departments.index'))
            ->put(route('departments.update', $department->id), [
                'code' => 'OPS-01',
                'name' => 'Operations Updated',
                'description' => 'Tim operasional pusat',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('departments.index'));

        $department->refresh();
        $this->assertSame('Operations Updated', $department->name);

        $this->actingAs($user)
            ->delete(route('departments.destroy', $department->id))
            ->assertRedirect(route('departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_user_can_filter_position_index_and_manage_position(): void
    {
        $user = $this->hrUser;
        $organization = Organization::factory()->create();
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $jobGrade = JobGrade::factory()->create(['level' => 2]);
        $otherDepartment = Department::factory()->create();
        $otherGrade = JobGrade::factory()->create(['level' => 5]);

        Position::factory()->create([
            'code' => 'POS-001',
            'name' => 'Supervisor Operasional',
            'department_id' => $department->id,
            'job_grade_id' => $jobGrade->id,
        ]);
        Position::factory()->create([
            'code' => 'POS-002',
            'name' => 'Manager Finance',
            'department_id' => $otherDepartment->id,
            'job_grade_id' => $otherGrade->id,
        ]);

        $this->actingAs($user)
            ->get(route('positions.index', [
                'search' => 'Supervisor',
                'department_id' => $department->id,
                'job_grade_id' => $jobGrade->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Position/Index')
                ->has('positions.data', 1)
                ->where('positions.data.0.code', 'POS-001')
            );

        $this->actingAs($user)
            ->from(route('positions.index'))
            ->post(route('positions.store'), [
                'code' => 'POS-003',
                'name' => 'Koordinator Gudang',
                'description' => 'Mengelola gudang',
                'department_id' => $department->id,
                'job_grade_id' => $jobGrade->id,
            ])
            ->assertRedirect(route('positions.index'));

        $position = Position::query()->where('code', 'POS-003')->first();
        $this->assertNotNull($position);

        $this->actingAs($user)
            ->from(route('positions.index'))
            ->put(route('positions.update', $position->id), [
                'code' => 'POS-003',
                'name' => 'Koordinator Gudang Updated',
                'description' => 'Mengelola gudang pusat',
                'department_id' => $department->id,
                'job_grade_id' => $jobGrade->id,
            ])
            ->assertRedirect(route('positions.index'));

        $this->assertSame('Koordinator Gudang Updated', $position->fresh()->name);
    }

    public function test_user_can_manage_job_grades(): void
    {
        $user = $this->hrUser;
        JobGrade::factory()->create(['code' => 'JG-A', 'level' => 1]);
        JobGrade::factory()->create(['code' => 'JG-B', 'level' => 3]);

        $this->actingAs($user)
            ->get(route('job-grades.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('JobGrade/Index')
                ->has('jobGrades', 2)
            );

        $this->actingAs($user)
            ->from(route('job-grades.index'))
            ->post(route('job-grades.store'), [
                'code' => 'JG-C',
                'name' => 'Grade C',
                'level' => 4,
            ])
            ->assertRedirect(route('job-grades.index'));

        $jobGrade = JobGrade::query()->where('code', 'JG-C')->first();
        $this->assertNotNull($jobGrade);

        $this->actingAs($user)
            ->from(route('job-grades.index'))
            ->put(route('job-grades.update', $jobGrade->id), [
                'code' => 'JG-C',
                'name' => 'Grade C Updated',
                'level' => 5,
            ])
            ->assertRedirect(route('job-grades.index'));

        $jobGrade->refresh();
        $this->assertSame('Grade C Updated', $jobGrade->name);
        $this->assertSame(5, $jobGrade->level);
    }

    public function test_user_can_manage_work_shifts(): void
    {
        $user = $this->hrUser;
        WorkShift::factory()->create(['name' => 'Shift Pagi', 'type' => 'SHIFT', 'start_time' => '08:00', 'end_time' => '16:00']);
        WorkShift::factory()->create(['name' => 'Non Shift', 'type' => 'NON_SHIFT', 'start_time' => '09:00', 'end_time' => '17:00']);

        $this->actingAs($user)
            ->get(route('work-shifts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WorkShift/Index')
                ->has('shifts', 2)
            );

        $this->actingAs($user)
            ->from(route('work-shifts.index'))
            ->post(route('work-shifts.store'), [
                'name' => 'Shift Malam',
                'type' => 'SHIFT',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'is_flexible' => false,
                'flexible_minutes' => 0,
            ])
            ->assertRedirect(route('work-shifts.index'));

        $shift = WorkShift::query()->where('name', 'Shift Malam')->first();
        $this->assertNotNull($shift);

        $this->actingAs($user)
            ->from(route('work-shifts.index'))
            ->put(route('work-shifts.update', $shift->id), [
                'name' => 'Shift Malam Updated',
                'type' => 'SHIFT',
                'start_time' => '21:00',
                'end_time' => '05:00',
                'is_flexible' => true,
                'flexible_minutes' => 30,
            ])
            ->assertRedirect(route('work-shifts.index'));

        $shift->refresh();
        $this->assertSame('Shift Malam Updated', $shift->name);
        $this->assertTrue($shift->is_flexible);
        $this->assertSame(30, $shift->flexible_minutes);
    }
}
