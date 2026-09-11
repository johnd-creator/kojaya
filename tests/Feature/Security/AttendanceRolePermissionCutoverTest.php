<?php

namespace Tests\Feature\Security;

use App\Enums\PermissionEnum;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceRolePermissionCutoverTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $employeeUserA;

    private Employee $employeeA;

    private User $employeeUserB;

    private Employee $employeeB;

    private User $hrUnitUserA;

    private User $hrPusatUser;

    private User $adminUnitUserA;

    private Attendance $attendanceA;

    private Attendance $attendanceB;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed initial permissions and roles
        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::factory()->create([
            'name' => 'Unit Alpha',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);
        $this->orgB = Organization::factory()->create([
            'name' => 'Unit Beta',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);

        $this->employeeUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->employeeUserA->assignRole('Employee');
        $this->employeeA = Employee::factory()->create([
            'user_id' => $this->employeeUserA->id,
            'organization_id' => $this->orgA->id,
            'first_name' => 'Alice',
            'status' => 'ACTIVE',
        ]);

        $this->employeeUserB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->employeeUserB->assignRole('Employee');
        $this->employeeB = Employee::factory()->create([
            'user_id' => $this->employeeUserB->id,
            'organization_id' => $this->orgB->id,
            'first_name' => 'Bob',
            'status' => 'ACTIVE',
        ]);

        $this->hrUnitUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->hrUnitUserA->assignRole('HR Unit');

        $this->hrPusatUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->hrPusatUser->assignRole('HR Pusat');

        $this->adminUnitUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->adminUnitUserA->assignRole('Admin Unit');

        $this->attendanceA = Attendance::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => 'PRESENT',
            'date' => today()->toDateString(),
            'clock_in' => '08:00:00',
        ]);

        $this->attendanceB = Attendance::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => 'PRESENT',
            'date' => today()->toDateString(),
            'clock_in' => '08:15:00',
        ]);
    }

    /**
     * Simulate pre-R1 production database state where:
     * - Employee has view_attendance_unit
     * - HR Pusat lacks view_attendance_all and approve_attendance
     * - HR Unit lacks view_attendance_unit and approve_attendance
     * - Admin Unit has view_attendance_unit and lacks approve_attendance
     */
    private function establishPreCutoverState(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $employeeRole = Role::where(['name' => 'Employee', 'guard_name' => $guard])->firstOrFail();
        $hrPusatRole = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->firstOrFail();
        $hrUnitRole = Role::where(['name' => 'HR Unit', 'guard_name' => $guard])->firstOrFail();
        $adminUnitRole = Role::where(['name' => 'Admin Unit', 'guard_name' => $guard])->firstOrFail();

        // 01: Employee has view_attendance_unit
        $employeeRole->givePermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value);

        // 02 & 03: HR Pusat lacks view_attendance_all and approve_attendance
        if ($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            $hrPusatRole->revokePermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value);
        }
        if ($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value)) {
            $hrPusatRole->revokePermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value);
        }

        // 04 & 05: HR Unit lacks view_attendance_unit and approve_attendance
        if ($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value)) {
            $hrUnitRole->revokePermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value);
        }
        if ($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value)) {
            $hrUnitRole->revokePermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value);
        }

        // Admin Unit has view_attendance_unit, lacks approve_attendance
        if (! $adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value)) {
            $adminUnitRole->givePermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value);
        }
        if ($adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value)) {
            $adminUnitRole->revokePermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function runCutoverMigration(): void
    {
        $migration = require database_path('migrations/2026_09_11_120000_cutover_attendance_role_permissions.php');
        $migration->up();
    }

    private function rollbackCutoverMigration(): void
    {
        $migration = require database_path('migrations/2026_09_11_120000_cutover_attendance_role_permissions.php');
        $migration->down();
    }

    /**
     * Test scenarios 01-15:
     * Full cutover lifecycle from pre-R1 state to post-cutover state.
     */
    public function test_scenarios_01_to_15_pre_cutover_state_cutover_execution_and_role_verification(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $this->establishPreCutoverState();

        $employeeRole = Role::where(['name' => 'Employee', 'guard_name' => $guard])->firstOrFail();
        $hrPusatRole = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->firstOrFail();
        $hrUnitRole = Role::where(['name' => 'HR Unit', 'guard_name' => $guard])->firstOrFail();
        $adminUnitRole = Role::where(['name' => 'Admin Unit', 'guard_name' => $guard])->firstOrFail();

        // SCENARIO 01: pre-cutover Employee has view_attendance_unit
        $this->assertTrue($employeeRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // SCENARIO 02: pre-cutover HR Pusat lacks view_attendance_all
        $this->assertFalse($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));

        // SCENARIO 03: pre-cutover HR Pusat lacks approve_attendance
        $this->assertFalse($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // SCENARIO 04: pre-cutover HR Unit lacks view_attendance_unit
        $this->assertFalse($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // SCENARIO 05: pre-cutover HR Unit lacks approve_attendance
        $this->assertFalse($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // SCENARIO 06: run attendance permission cutover
        $this->runCutoverMigration();

        $employeeRole->refresh();
        $hrPusatRole->refresh();
        $hrUnitRole->refresh();
        $adminUnitRole->refresh();

        // SCENARIO 07: Employee no longer has view_attendance_unit
        $this->assertFalse($employeeRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // SCENARIO 08: HR Pusat has view_attendance_all
        $this->assertTrue($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));

        // SCENARIO 09: HR Pusat has approve_attendance
        $this->assertTrue($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // SCENARIO 10: HR Unit has view_attendance_unit
        $this->assertTrue($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // SCENARIO 11: HR Unit has approve_attendance
        $this->assertTrue($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // SCENARIO 12: Admin Unit retains view_attendance_unit
        $this->assertTrue($adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // SCENARIO 13: Admin Unit does NOT gain approve_attendance
        $this->assertFalse($adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // SCENARIO 14: unrelated Employee permissions remain unchanged
        $this->assertTrue($employeeRole->hasPermissionTo('access_ess_portal'));
        $this->assertTrue($employeeRole->hasPermissionTo('view_employee_unit'));
        $this->assertTrue($employeeRole->hasPermissionTo('view_own_payslip'));

        // SCENARIO 15: unrelated HR permissions remain unchanged
        $this->assertTrue($hrPusatRole->hasPermissionTo('view_employee_all'));
        $this->assertTrue($hrPusatRole->hasPermissionTo('manage_departments'));
        $this->assertTrue($hrUnitRole->hasPermissionTo('view_employee_unit'));
        $this->assertTrue($hrUnitRole->hasPermissionTo('manage_employee_transfer'));
        $this->assertTrue($adminUnitRole->hasPermissionTo('manage_petty_cash'));
        $this->assertTrue($adminUnitRole->hasPermissionTo('view_project_unit'));
    }

    /**
     * SCENARIO 16: repeated/idempotent cutover leaves same final state
     */
    public function test_scenario_16_repeated_idempotent_cutover_leaves_same_final_state(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $this->establishPreCutoverState();

        // Run cutover once
        $this->runCutoverMigration();

        // Run cutover a second time
        $this->runCutoverMigration();

        $employeeRole = Role::where(['name' => 'Employee', 'guard_name' => $guard])->firstOrFail();
        $hrPusatRole = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->firstOrFail();
        $hrUnitRole = Role::where(['name' => 'HR Unit', 'guard_name' => $guard])->firstOrFail();
        $adminUnitRole = Role::where(['name' => 'Admin Unit', 'guard_name' => $guard])->firstOrFail();

        $this->assertFalse($employeeRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
        $this->assertTrue($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));
        $this->assertTrue($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));
        $this->assertTrue($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
        $this->assertTrue($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));
        $this->assertTrue($adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
        $this->assertFalse($adminUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // Verify no duplicate permission rows in role_has_permissions
        $empPermissions = $employeeRole->permissions()->pluck('name')->toArray();
        $this->assertSame(count($empPermissions), count(array_unique($empPermissions)));
    }

    /**
     * SCENARIO 17: Spatie permission cache does not preserve stale authorization
     */
    public function test_scenario_17_permission_cache_does_not_preserve_stale_authorization(): void
    {
        $this->establishPreCutoverState();

        // In pre-cutover state, user has the cached role permission
        $this->assertTrue($this->employeeUserA->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // Execute migration (which invalidates the cache)
        $this->runCutoverMigration();

        // Fresh instance without manual cache manipulation must immediately evaluate to false
        $freshUser = User::find($this->employeeUserA->id);
        $this->assertFalse($freshUser->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
    }

    /**
     * SCENARIO 18: legacy Employee loses admin attendance access after cutover
     */
    public function test_scenario_18_legacy_employee_loses_admin_attendance_access_after_cutover(): void
    {
        $this->establishPreCutoverState();

        // In pre-cutover state, Employee had view_attendance_unit and could access attendances.index
        $this->actingAs($this->employeeUserA)
            ->get(route('attendances.index'))
            ->assertOk();

        // Apply cutover
        $this->runCutoverMigration();

        // After cutover, Employee is 403 Forbidden
        $this->actingAs($this->employeeUserA->fresh())
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    /**
     * SCENARIO 19: Employee ESS/self-service remains functional after cutover
     */
    public function test_scenario_19_employee_ess_self_service_remains_functional_after_cutover(): void
    {
        $this->establishPreCutoverState();
        $this->runCutoverMigration();

        // 1. History read
        Sanctum::actingAs($this->employeeUserA->fresh(), ['attendance:read']);
        $historyResponse = $this->getJson('/api/ess/attendance/history')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($historyResponse);
        $this->assertSame($this->employeeA->id, $historyResponse[0]['employee_id']);

        // 2. Correction submission
        Sanctum::actingAs($this->employeeUserA->fresh(), ['attendance:write']);
        $correctionResponse = $this->postJson('/api/ess/attendance/correction', [
            'date' => today()->subDays(3)->toDateString(),
            'corrected_clock_in' => '08:30',
            'corrected_clock_out' => '17:30',
            'reason' => 'Client meeting outside office',
        ])->assertCreated();

        $this->assertSame($this->employeeA->id, $correctionResponse->json('data.employee_id'));
        $this->assertSame($this->orgA->id, $correctionResponse->json('data.organization_id'));
    }

    /**
     * SCENARIO 20: HR Unit can access unit-scoped attendance after cutover
     */
    public function test_scenario_20_hr_unit_can_access_unit_scoped_attendance_after_cutover(): void
    {
        $this->establishPreCutoverState();
        $this->runCutoverMigration();

        $this->actingAs($this->hrUnitUserA->fresh())
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 1)
                ->where('attendances.data.0.id', $this->attendanceA->id)
            );
    }

    /**
     * SCENARIO 21: HR Unit can perform authorized own-unit attendance mutation but not foreign
     */
    public function test_scenario_21_hr_unit_can_mutate_own_unit_but_not_foreign(): void
    {
        $this->establishPreCutoverState();
        $this->runCutoverMigration();

        // Own-unit mutation succeeds
        $this->actingAs($this->hrUnitUserA->fresh())
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'date' => '2026-03-10',
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'status' => 'PRESENT',
                'notes' => 'Recorded by HR Unit',
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'date' => '2026-03-10',
            'status' => 'PRESENT',
        ]);

        // Foreign-unit mutation is blocked
        $this->actingAs($this->hrUnitUserA->fresh())
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => '2026-03-10',
                'status' => 'ABSENT',
            ])
            ->assertForbidden();
    }

    /**
     * SCENARIO 22: HR Pusat can perform intended global attendance administration
     */
    public function test_scenario_22_hr_pusat_can_perform_intended_global_attendance_administration(): void
    {
        $this->establishPreCutoverState();
        $this->runCutoverMigration();

        // HR Pusat can view global attendances across units
        $this->actingAs($this->hrPusatUser->fresh())
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 2)
            );

        // HR Pusat can mutate attendance in foreign org B
        $this->actingAs($this->hrPusatUser->fresh())
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => '2026-03-11',
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'status' => 'PRESENT',
                'notes' => 'Recorded by HR Pusat',
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'date' => '2026-03-11',
        ]);
    }

    /**
     * Test rollback security semantics:
     * down() must NOT re-grant view_attendance_unit to Employee.
     */
    public function test_rollback_does_not_restore_insecure_employee_permission(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $this->establishPreCutoverState();

        // 1. Run cutover up()
        $this->runCutoverMigration();

        $employeeRole = Role::where(['name' => 'Employee', 'guard_name' => $guard])->firstOrFail();
        $hrPusatRole = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->firstOrFail();
        $hrUnitRole = Role::where(['name' => 'HR Unit', 'guard_name' => $guard])->firstOrFail();

        $this->assertFalse($employeeRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
        $this->assertTrue($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));
        $this->assertTrue($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));

        // 2. Run rollback down()
        $this->rollbackCutoverMigration();

        $employeeRole->refresh();
        $hrPusatRole->refresh();
        $hrUnitRole->refresh();

        // Rollback safely revokes granted HR permissions
        $this->assertFalse($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));
        $this->assertFalse($hrPusatRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));
        $this->assertFalse($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value));
        $this->assertFalse($hrUnitRole->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));

        // Rollback MUST NOT restore view_attendance_unit to Employee (fail-closed)
        $this->assertFalse(
            $employeeRole->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value),
            'Rollback must NOT restore view_attendance_unit to Employee role.'
        );
    }

    /**
     * Test deterministic creation if permissions or roles are missing from the database.
     */
    public function test_cutover_creates_missing_permissions_and_roles_deterministically(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Delete the attendance permissions
        Permission::whereIn('name', [
            PermissionEnum::ATTENDANCE_VIEW_ALL->value,
            PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
            PermissionEnum::ATTENDANCE_APPROVE->value,
        ])->delete();

        // Run cutover
        $this->runCutoverMigration();

        // Assert permissions were re-created and assigned
        $this->assertDatabaseHas('permissions', [
            'name' => PermissionEnum::ATTENDANCE_VIEW_ALL->value,
            'guard_name' => $guard,
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
            'guard_name' => $guard,
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => PermissionEnum::ATTENDANCE_APPROVE->value,
            'guard_name' => $guard,
        ]);

        $hrPusat = Role::where(['name' => 'HR Pusat', 'guard_name' => $guard])->firstOrFail();
        $this->assertTrue($hrPusat->hasPermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value));
        $this->assertTrue($hrPusat->hasPermissionTo(PermissionEnum::ATTENDANCE_APPROVE->value));
    }
}
