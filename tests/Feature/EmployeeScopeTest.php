<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
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
}
