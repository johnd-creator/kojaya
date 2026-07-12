<?php

namespace Tests\Feature\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_scope_service_limits_query_to_user_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Member A',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Member B',
        ]);

        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->assignRole('Admin Koperasi');

        $service = app(OrganizationScopedQueryService::class);

        $scoped = CooperativeMember::query();
        $service->scopeVisibleTo($scoped, $adminA);

        $names = $scoped->pluck('name')->all();

        $this->assertContains('Member A', $names);
        $this->assertNotContains('Member B', $names);
    }

    public function test_scope_service_allows_view_cooperative_all_to_see_everything(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Member A',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Member B',
        ]);

        $pengurus = User::factory()->create(['organization_id' => $orgA->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $service = app(OrganizationScopedQueryService::class);

        $this->assertTrue($service->canViewAllOrganizations($pengurus));

        $scoped = CooperativeMember::query();
        $service->scopeVisibleTo($scoped, $pengurus);

        $this->assertSame(2, $scoped->count());
    }

    public function test_scope_organization_id_returns_null_for_global_users(): void
    {
        $pengurus = User::factory()->create();
        $pengurus->assignRole('Pengurus Koperasi');

        $service = app(OrganizationScopedQueryService::class);

        $this->assertNull($service->scopeOrganizationIdFor($pengurus));
    }

    public function test_scope_organization_id_returns_id_for_non_global_users(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $service = app(OrganizationScopedQueryService::class);

        $this->assertSame($org->id, $service->scopeOrganizationIdFor($admin));
    }

    public function test_admin_org_a_cannot_see_org_b_members_in_index(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->assignRole('Admin Koperasi');

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'no_anggota' => 'AAA',
            'member_no' => 'AAA',
            'name' => 'Visible Member',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'no_anggota' => 'BBB',
            'member_no' => 'BBB',
            'name' => 'Hidden Member',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('cooperative.members.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('members.data', 1)
            ->where('members.data.0.name', 'Visible Member')
        );
    }

    public function test_admin_org_a_cannot_read_org_b_loan_by_direct_id(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->assignRole('Admin Koperasi');
        $memberB = CooperativeMember::factory()->active()->create(['organization_id' => $orgB->id]);
        $loanB = Loan::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'organization_id' => $orgB->id,
        ]);

        Sanctum::actingAs($admin, ['cooperative.loan.read']);

        $this->getJson('/api/v1/loans/'.$loanB->id)->assertForbidden();
    }

    public function test_admin_org_a_stats_query_is_scoped(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->assignRole('Admin Koperasi');

        CooperativeMember::factory()->active()->create([
            'organization_id' => $orgA->id,
            'status' => 'ACTIVE',
        ]);
        CooperativeMember::factory()->active()->create([
            'organization_id' => $orgB->id,
            'status' => 'ACTIVE',
        ]);

        $service = app(OrganizationScopedQueryService::class);

        $statsQuery = CooperativeMember::query();
        $service->scopeVisibleTo($statsQuery, $admin);

        $this->assertSame(1, (clone $statsQuery)->where('status', 'ACTIVE')->count());
    }

    public function test_admin_org_a_resignations_do_not_include_org_b(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->assignRole('Admin Koperasi');

        $memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $orgA->id,
        ]);
        $memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $orgB->id,
        ]);

        \App\Models\MemberResignationRequest::query()->create([
            'cooperative_member_id' => $memberA->id,
            'user_id' => $admin->id,
            'status' => 'PENDING',
            'reason' => 'Pindah',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        \App\Models\MemberResignationRequest::query()->create([
            'cooperative_member_id' => $memberB->id,
            'user_id' => $admin->id,
            'status' => 'PENDING',
            'reason' => 'Pindah',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('cooperative.members.resignations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
        );

        $this->assertSame(1, $response->inertiaProps()['stats']['pending'] ?? null);
    }

    public function test_pengurus_can_see_all_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $pengurus = User::factory()->create(['organization_id' => $orgA->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'no_anggota' => 'AAA',
            'member_no' => 'AAA',
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'no_anggota' => 'BBB',
            'member_no' => 'BBB',
        ]);

        $response = $this->actingAs($pengurus)
            ->get(route('cooperative.members.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('members.data', 2)
        );
    }
}
