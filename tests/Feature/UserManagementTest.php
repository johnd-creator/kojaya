<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'System Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'HR Unit', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage_users', 'guard_name' => 'web']);
    }

    private function adminUser(array $attributes = []): User
    {
        $admin = User::factory()->create($attributes);
        $admin->givePermissionTo('manage_users');

        return $admin;
    }

    public function test_user_can_view_user_management_index(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->adminUser(['organization_id' => $organization->id]);
        $managedUser = User::factory()->create(['organization_id' => $organization->id]);
        $managedUser->assignRole('HR Unit');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Index')
                ->has('users.data', 2)
                ->has('roles', 2)
                ->has('organizations', 1)
            );
    }

    public function test_user_can_create_user_and_assign_role_and_organization(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->adminUser(['organization_id' => $organization->id]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'name' => 'User HR',
                'email' => 'hr@example.test',
                'password' => 'password123',
                'role' => 'HR Unit',
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('users.index'));

        $createdUser = User::where('email', 'hr@example.test')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame($organization->id, $createdUser->organization_id);
        $this->assertTrue($createdUser->hasRole('HR Unit'));
    }

    public function test_user_can_update_managed_user_role_and_organization(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $admin = $this->adminUser(['organization_id' => $firstOrganization->id]);
        $managedUser = User::factory()->create([
            'organization_id' => $firstOrganization->id,
            'email' => 'managed@example.test',
        ]);
        $managedUser->assignRole('HR Unit');

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->put(route('users.update', $managedUser), [
                'name' => 'Managed Updated',
                'email' => 'managed@example.test',
                'password' => '',
                'role' => 'System Admin',
                'organization_id' => $secondOrganization->id,
            ])
            ->assertRedirect(route('users.index'));

        $managedUser->refresh();

        $this->assertSame('Managed Updated', $managedUser->name);
        $this->assertSame($secondOrganization->id, $managedUser->organization_id);
        $this->assertTrue($managedUser->hasRole('System Admin'));
    }

    public function test_user_can_delete_other_user(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->adminUser(['organization_id' => $organization->id]);
        $managedUser = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }

    public function test_user_cannot_delete_self(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->adminUser(['organization_id' => $organization->id]);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error', 'You cannot delete yourself.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }
}
