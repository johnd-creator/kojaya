<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $orgA;

    protected Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $this->orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);

        Employee::factory()->count(3)->create(['organization_id' => $this->orgA->id, 'status' => 'ACTIVE']);
        Employee::factory()->count(2)->create(['organization_id' => $this->orgB->id, 'status' => 'ACTIVE']);
    }

    public function test_system_admin_can_index_all_employees(): void
    {
        Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('System Admin');

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employee/Index')
                ->has('employees')
                ->count('employees.data', 5)
            );
    }

    public function test_hr_unit_only_indexes_own_organization_employees(): void
    {
        Role::firstOrCreate(['name' => 'HR Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('HR Unit');

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employee/Index')
                ->has('employees')
                ->count('employees.data', 3)
            );
    }

    public function test_admin_unit_only_indexes_own_organization_employees(): void
    {
        Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgB->id]);
        $user->assignRole('Admin Unit');

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employee/Index')
                ->has('employees')
                ->count('employees.data', 2)
            );
    }

    public function test_stats_reflect_user_organization_scope(): void
    {
        Role::firstOrCreate(['name' => 'HR Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('HR Unit');

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employee/Index')
                ->where('stats.total_active', 3)
            );
    }
}
