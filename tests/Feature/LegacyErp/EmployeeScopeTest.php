<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeScopeTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $orgA;

    protected Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $this->orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);

        // Employees in each org
        Employee::factory()->count(3)->create(['organization_id' => $this->orgA->id]);
        Employee::factory()->count(2)->create(['organization_id' => $this->orgB->id]);
    }

    #[Test]
    public function system_admin_can_see_all_employees(): void
    {
        Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);
        $role = Role::findByName('System Admin');
        $role->syncPermissions([Permission::findOrCreate('view_employee_all', 'web')]);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('System Admin');

        $this->actingAs($user);

        $employees = Employee::query()->forUser()->get();

        $this->assertCount(5, $employees);
    }

    #[Test]
    public function admin_pusat_can_see_all_employees(): void
    {
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);
        $role = Role::findByName('Admin Pusat');
        $role->syncPermissions([Permission::findOrCreate('view_employee_all', 'web')]);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Admin Pusat');

        $this->actingAs($user);

        $employees = Employee::query()->forUser()->get();

        $this->assertCount(5, $employees);
    }

    #[Test]
    public function hr_unit_only_sees_own_organization_employees(): void
    {
        Role::firstOrCreate(['name' => 'HR Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('HR Unit');

        $this->actingAs($user);

        $employees = Employee::query()->forUser()->get();

        $this->assertCount(3, $employees);
        $employees->each(fn ($e) => $this->assertEquals($this->orgA->id, $e->organization_id));
    }

    #[Test]
    public function admin_unit_only_sees_own_organization_employees(): void
    {
        Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgB->id]);
        $user->assignRole('Admin Unit');

        $this->actingAs($user);

        $employees = Employee::query()->forUser()->get();

        $this->assertCount(2, $employees);
        $employees->each(fn ($e) => $this->assertEquals($this->orgB->id, $e->organization_id));
    }

    #[Test]
    public function for_organization_scope_filters_by_given_org(): void
    {
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);
        $role = Role::findByName('Admin Pusat');
        $role->syncPermissions([Permission::findOrCreate('view_employee_all', 'web')]);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Admin Pusat');

        $this->actingAs($user);

        $employees = Employee::query()->forOrganization($this->orgB->id)->get();

        $this->assertCount(2, $employees);
    }

    #[Test]
    public function guest_sees_no_employees(): void
    {
        $employees = Employee::query()->forUser()->get();

        $this->assertCount(0, $employees);
    }

    #[Test]
    public function scoped_actor_cannot_switch_active_organization_in_session(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => 'web']);
        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole($role);
        $this->actingAs($user);
        session(['active_organization_id' => $this->orgB->id]);

        $this->expectException(AuthorizationException::class);
        Employee::query()->forActiveOrganization()->get();
    }

    #[Test]
    public function scoped_actor_cannot_switch_organization_through_the_endpoint(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => 'web']);
        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->post(route('switch-organization'), ['organization_id' => $this->orgB->id])
            ->assertForbidden();

        $this->assertNull(session('active_organization_id'));
    }

    #[Test]
    public function global_actor_can_select_another_valid_organization_in_session(): void
    {
        $role = Role::firstOrCreate(['name' => 'Central Employee Viewer', 'guard_name' => 'web']);
        $role->syncPermissions([Permission::findOrCreate('view_employee_all', 'web')]);
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole($role);
        $this->actingAs($user);
        session(['active_organization_id' => $this->orgB->id]);

        $employees = Employee::query()->forActiveOrganization()->get();

        $this->assertCount(2, $employees);
        $employees->each(fn ($employee) => $this->assertSame($this->orgB->id, $employee->organization_id));
    }

    #[Test]
    public function global_actor_can_switch_organization_through_the_endpoint(): void
    {
        $role = Role::firstOrCreate(['name' => 'Global Cooperative Viewer', 'guard_name' => 'web']);
        $role->syncPermissions([Permission::findOrCreate('view_cooperative_all', 'web')]);
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->post(route('switch-organization'), ['organization_id' => $this->orgB->id])
            ->assertRedirect();

        $this->assertSame($this->orgB->id, session('active_organization_id'));
    }

    #[Test]
    public function null_organization_non_global_actor_is_denied(): void
    {
        $role = Role::firstOrCreate(['name' => 'Unscoped Employee Viewer', 'guard_name' => 'web']);
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole($role);
        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);
        Employee::query()->forUser()->get();
    }
}
