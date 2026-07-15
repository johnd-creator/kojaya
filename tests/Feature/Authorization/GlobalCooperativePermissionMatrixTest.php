<?php

namespace Tests\Feature\Authorization;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PettyCashAccount;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class GlobalCooperativePermissionMatrixTest extends TestCase
{
    public function test_exact_role_matrix_controls_global_cooperative_visibility(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $allowed = ['System Admin', 'Admin Pusat', 'Pengurus Koperasi'];
        $roles = [
            'System Admin',
            'Admin Pusat',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
            'Admin Unit',
            'HR Pusat',
            'HR Unit',
            'Finance Pusat',
            'Finance Unit',
            'Employee',
            'Technician',
            'Project Manager',
            'Site Manager',
            'Anggota',
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
            $user->assignRole($role);

            $this->assertSame(
                in_array($role, $allowed, true),
                $user->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value),
                "Unexpected global permission for role [{$role}].",
            );
        }
    }

    public function test_explicit_permission_works_without_an_organization_and_removal_is_immediate(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['organization_id' => null]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $scope = app(OrganizationScopeService::class);

        $this->assertTrue($scope->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value)->global);

        $user->revokePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $scope->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
    }

    public function test_role_name_alone_and_manage_permissions_do_not_create_global_visibility(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Unit');
        $user->givePermissionTo('manage_petty_cash');

        $visibility = app(OrganizationScopeService::class)->visibilityFor($user, null);

        $this->assertFalse($visibility->global);
        $this->assertSame($organization->id, $visibility->organizationId);
    }

    public function test_manage_petty_cash_actor_remains_scoped_to_one_organization(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organizationA->id]);
        $user->assignRole('Admin Unit');
        $user->givePermissionTo('manage_petty_cash');
        PettyCashAccount::factory()->create(['organization_id' => $organizationA->id]);
        PettyCashAccount::factory()->create(['organization_id' => $organizationB->id]);

        $query = PettyCashAccount::query();
        app(OrganizationScopeService::class)->scopeVisibleTo($query, $user);

        $this->assertSame(1, $query->count());
        $this->assertSame($organizationA->id, $query->firstOrFail()->organization_id);
    }

    public function test_global_scope_is_validated_before_bypass_for_unknown_models(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['organization_id' => null]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $model = new class extends \Illuminate\Database\Eloquent\Model {};

        $this->expectException(\App\Exceptions\OrganizationScopeException::class);

        app(OrganizationScopeService::class)->scopeVisibleTo($model->newQuery(), $user);
    }

    public function test_global_permission_sees_multiple_cooperative_organizations(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['organization_id' => null]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        CooperativeMember::factory()->create(['organization_id' => $orgA->id, 'user_id' => null]);
        CooperativeMember::factory()->create(['organization_id' => $orgB->id, 'user_id' => null]);

        $query = CooperativeMember::query();
        app(OrganizationScopeService::class)->scopeVisibleTo(
            $query,
            $user,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        );

        $this->assertSame(2, $query->count());
    }
}
