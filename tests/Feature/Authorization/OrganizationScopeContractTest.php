<?php

namespace Tests\Feature\Authorization;

use App\Contracts\OrganizationScopedModel;
use App\Enums\PermissionEnum;
use App\Exceptions\OrganizationScopeException;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class OrganizationScopeContractTest extends TestCase
{
    public function test_registered_models_are_scoped_for_search_count_and_pagination(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->assignRole('Admin Koperasi');

        CooperativeMember::factory()->count(2)->create([
            'organization_id' => $orgA->id,
            'user_id' => null,
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'user_id' => null,
        ]);

        $query = CooperativeMember::query();
        app(OrganizationScopeService::class)->scopeVisibleTo($query, $user);

        $this->assertSame(2, (clone $query)->count());
        $this->assertCount(2, (clone $query)->paginate(15)->items());
        $this->assertSame($orgA->id, (string) $query->firstOrFail()->organization_id);
    }

    public function test_nested_registered_model_uses_the_declared_organization_path(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->assignRole('Manajer Koperasi');

        $memberA = CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'user_id' => null,
        ]);
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'user_id' => null,
        ]);
        Loan::factory()->create([
            'organization_id' => $orgA->id,
            'cooperative_member_id' => $memberA->id,
        ]);
        Loan::factory()->create([
            'organization_id' => $orgB->id,
            'cooperative_member_id' => $memberB->id,
        ]);

        $query = Loan::query();
        app(OrganizationScopeService::class)->scopeVisibleTo($query, $user);

        $this->assertSame(1, $query->count());
        $this->assertSame($orgA->id, (string) $query->firstOrFail()->organization_id);
    }

    public function test_global_permission_sees_multiple_organizations(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => null]);
        $user->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);

        CooperativeMember::factory()->create(['organization_id' => $orgA->id, 'user_id' => null]);
        CooperativeMember::factory()->create(['organization_id' => $orgB->id, 'user_id' => null]);

        $query = CooperativeMember::query();
        app(OrganizationScopeService::class)->scopeVisibleTo($query, $user);

        $this->assertSame(2, $query->count());
    }

    public function test_non_global_user_without_organization_is_denied(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole('Manajer Koperasi');

        $this->expectException(AuthorizationException::class);

        app(OrganizationScopeService::class)->scopeVisibleTo(CooperativeMember::query(), $user);
    }

    public function test_unsupported_model_fails_closed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $model = new class extends Model {};

        $this->expectException(OrganizationScopeException::class);

        app(OrganizationScopeService::class)->scopeVisibleTo($model->newQuery(), $user);
    }

    public function test_broken_explicit_path_fails_closed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $model = new class extends Model implements OrganizationScopedModel
        {
            public function organizationScopePath(): string
            {
                return 'missingRelation.organization_id';
            }
        };

        $this->expectException(OrganizationScopeException::class);

        app(OrganizationScopeService::class)->assertVisible($user, $model);
    }
}
