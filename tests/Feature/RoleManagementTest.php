<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'users.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'roles.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage_roles', 'guard_name' => 'web']);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage_roles');

        return $admin;
    }

    public function test_user_can_view_role_index_with_user_counts(): void
    {
        $admin = $this->adminUser();
        $role = Role::create(['name' => 'HR Unit', 'guard_name' => 'web']);
        User::factory()->count(2)->create()->each(fn (User $user) => $user->assignRole($role));

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Role/Index')
                ->has('roles', 1)
                ->where('roles.0.name', 'HR Unit')
                ->where('roles.0.users_count', 2)
            );
    }

    public function test_user_can_view_role_edit_page_with_permissions(): void
    {
        $admin = $this->adminUser();
        $role = Role::create(['name' => 'Finance Unit', 'guard_name' => 'web']);
        $role->givePermissionTo('users.view');

        $this->actingAs($admin)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Role/Edit')
                ->where('role.name', 'Finance Unit')
                ->has('role.permissions', 1)
                ->has('permissions', 6)
            );
    }

    public function test_user_can_update_role_permissions(): void
    {
        $admin = $this->adminUser();
        $role = Role::create(['name' => 'Project Manager', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'permissions' => ['users.view', 'roles.manage'],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('roles.manage'));
        $this->assertFalse($role->fresh()->hasPermissionTo('users.manage'));
    }

    public function test_role_update_validates_unknown_permissions(): void
    {
        $admin = $this->adminUser();
        $role = Role::create(['name' => 'Admin Unit', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->from(route('roles.edit', $role))
            ->put(route('roles.update', $role), [
                'permissions' => ['permissions.unknown'],
            ])
            ->assertRedirect(route('roles.edit', $role))
            ->assertSessionHasErrors('permissions.0');
    }
}
