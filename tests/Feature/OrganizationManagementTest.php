<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::query()->firstOrCreate(['name' => 'manage_organizations', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('manage_organizations');
    }

    public function test_user_can_view_organization_index(): void
    {
        $parent = Organization::factory()->create(['level' => 'L0']);
        Organization::factory()->create(['parent_id' => $parent->id, 'level' => 'L1']);

        $this->actingAs($this->adminUser)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organization/Index')
                ->has('organizations', 2)
            );
    }

    public function test_user_can_create_and_update_organization(): void
    {
        $parent = Organization::factory()->create();

        $this->actingAs($this->adminUser)
            ->from(route('organizations.index'))
            ->post(route('organizations.store'), [
                'name' => 'Unit Operasional',
                'code' => 'OPS01',
                'type' => 'BRANCH',
                'level' => 'L1',
                'parent_id' => $parent->id,
                'address' => 'Bandung',
                'phone' => '08123456789',
                'email' => 'ops@example.test',
                'is_active' => true,
            ])
            ->assertRedirect(route('organizations.index'));

        $organization = Organization::query()->where('code', 'OPS01')->first();

        $this->assertNotNull($organization);

        $this->actingAs($this->adminUser)
            ->from(route('organizations.index'))
            ->put(route('organizations.update', $organization->id), [
                'name' => 'Unit Operasional Updated',
                'code' => 'OPS01',
                'type' => 'SITE',
                'level' => 'L2',
                'parent_id' => $parent->id,
                'address' => 'Jakarta',
                'phone' => '08999999999',
                'email' => 'ops-updated@example.test',
                'is_active' => false,
            ])
            ->assertRedirect(route('organizations.index'));

        $organization->refresh();

        $this->assertSame('Unit Operasional Updated', $organization->name);
        $this->assertSame('SITE', $organization->type);
        $this->assertSame('L2', $organization->level);
        $this->assertFalse($organization->is_active);
    }

    public function test_organization_code_must_be_unique(): void
    {
        Organization::factory()->create(['code' => 'DUPL01']);

        $this->actingAs($this->adminUser)
            ->from(route('organizations.index'))
            ->post(route('organizations.store'), [
                'name' => 'Duplikat',
                'code' => 'DUPL01',
                'type' => 'BRANCH',
                'level' => 'L1',
                'parent_id' => null,
                'is_active' => true,
            ])
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHasErrors(['code']);
    }

    public function test_organization_with_child_units_cannot_be_deleted(): void
    {
        $parent = Organization::factory()->create();
        Organization::factory()->create(['parent_id' => $parent->id, 'level' => 'L1']);

        $this->actingAs($this->adminUser)
            ->delete(route('organizations.destroy', $parent->id))
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHas('error', 'Cannot delete organization with child units.');

        $this->assertDatabaseHas('organizations', ['id' => $parent->id]);
    }

    public function test_organization_with_assigned_users_cannot_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($this->adminUser)
            ->delete(route('organizations.destroy', $organization->id))
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHas('error', 'Cannot delete organization with assigned users.');

        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
    }

    public function test_user_can_switch_active_organization_context(): void
    {
        $organization = Organization::factory()->create();
        $this->adminUser->givePermissionTo(Permission::findOrCreate('view_cooperative_all', 'web'));

        $this->actingAs($this->adminUser)
            ->from(route('organizations.index'))
            ->post(route('switch-organization'), [
                'organization_id' => $organization->id,
            ])
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHas('success', 'Organization context updated successfully.');

        $this->assertSame($organization->id, session('active_organization_id'));
    }
}
