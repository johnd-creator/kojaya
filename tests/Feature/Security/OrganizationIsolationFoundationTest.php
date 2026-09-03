<?php

namespace Tests\Feature\Security;

use App\Contracts\OrganizationScopedModel;
use App\Contracts\OrganizationScopedQueryService;
use App\Enums\PermissionEnum;
use App\Exceptions\OrganizationScopeException;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Organization;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationIsolationFoundationTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationScopeService $scopeService;

    private OrganizationScopedQueryService $scopedQueryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->scopeService = app(OrganizationScopeService::class);
        $this->scopedQueryService = app(OrganizationScopedQueryService::class);
    }

    /**
     * RULE A & RULE D — Direct Ownership:
     * User belonging to Org A sees Org A records, and does not see Org B records.
     * assertVisible allows Org A records and denies Org B records.
     */
    public function test_characterization_direct_ownership_scoping_and_assertion_for_employee(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo(PermissionEnum::EMPLOYEE_VIEW_UNIT->value);

        $employeeA = Employee::factory()->create(['organization_id' => $orgA->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        // Scoping
        $scopedQuery = Employee::query();
        $this->scopeService->scopeVisibleTo($scopedQuery, $userA);
        $results = $scopedQuery->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains('id', $employeeA->id));
        $this->assertFalse($results->contains('id', $employeeB->id));

        // assertVisible
        $this->scopeService->assertVisible($userA, $employeeA);

        $this->expectException(AuthorizationException::class);
        $this->scopeService->assertVisible($userA, $employeeB);
    }

    /**
     * RULE E — Relational Ownership:
     * Model whose ownership is derived through relationship (RewardRedemption -> member.organization_id).
     * Actor in Org A cannot see or assert visibility for relational object owned through Member B / Org B.
     */
    public function test_characterization_relational_ownership_scoping_and_assertion_for_reward_redemption(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo(PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value);

        $memberA = CooperativeMember::factory()->create(['organization_id' => $orgA->id]);
        $memberB = CooperativeMember::factory()->create(['organization_id' => $orgB->id]);

        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionA = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
        ]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
        ]);

        // Scoping
        $scopedQuery = RewardRedemption::query();
        $this->scopeService->scopeVisibleTo($scopedQuery, $userA);
        $results = $scopedQuery->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains('id', $redemptionA->id));
        $this->assertFalse($results->contains('id', $redemptionB->id));

        // assertVisible on relational model
        $this->scopeService->assertVisible($userA, $redemptionA);

        $this->expectException(AuthorizationException::class);
        $this->scopeService->assertVisible($userA, $redemptionB);
    }

    /**
     * RULE B — Explicit Global Access:
     * Cross-organization access requires an explicit domain/model global permission.
     */
    public function test_characterization_explicit_global_permission_allows_cross_organization_queries(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $globalCoopUser = User::factory()->create(['organization_id' => $orgA->id]);
        $globalCoopUser->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);

        $memberA = CooperativeMember::factory()->create(['organization_id' => $orgA->id]);
        $memberB = CooperativeMember::factory()->create(['organization_id' => $orgB->id]);

        $scopedQuery = CooperativeMember::query();
        $this->scopeService->scopeVisibleTo($scopedQuery, $globalCoopUser);

        $this->assertSame(2, $scopedQuery->count());
        $this->scopeService->assertVisible($globalCoopUser, $memberA);
        $this->scopeService->assertVisible($globalCoopUser, $memberB);

        // Global employee user
        $globalHrUser = User::factory()->create(['organization_id' => null]);
        $globalHrUser->givePermissionTo(PermissionEnum::EMPLOYEE_VIEW_ALL->value);

        $employeeA = Employee::factory()->create(['organization_id' => $orgA->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        $scopedHrQuery = Employee::query();
        $this->scopeService->scopeVisibleTo($scopedHrQuery, $globalHrUser);

        $hrResults = $scopedHrQuery->get();
        $this->assertTrue($hrResults->contains('id', $employeeA->id));
        $this->assertTrue($hrResults->contains('id', $employeeB->id));
        $this->scopeService->assertVisible($globalHrUser, $employeeA);
        $this->scopeService->assertVisible($globalHrUser, $employeeB);
    }

    /**
     * RULE B — Administrative Role Without Global Permission:
     * A role that merely looks administrative (e.g. "Admin Koperasi") must NOT bypass organization boundaries.
     */
    public function test_characterization_administrative_role_with_unit_only_permission_cannot_bypass_organization_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->assignRole('Admin Koperasi'); // Has manage_cooperative_member, view_cooperative_unit, etc. NOT view_cooperative_all

        $this->assertFalse($adminA->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value));

        $memberA = CooperativeMember::factory()->create(['organization_id' => $orgA->id]);
        $memberB = CooperativeMember::factory()->create(['organization_id' => $orgB->id]);

        $query = CooperativeMember::query();
        $this->scopeService->scopeVisibleTo($query, $adminA);

        $results = $query->get();
        $this->assertCount(1, $results);
        $this->assertSame($memberA->id, $results->first()->id);

        $this->scopeService->assertVisible($adminA, $memberA);

        $this->expectException(AuthorizationException::class);
        $this->scopeService->assertVisible($adminA, $memberB);
    }

    /**
     * RULE C — Null Organization Fails Closed:
     * User with organization_id = null and NO global permission fails closed.
     * Never returns an unfiltered query.
     */
    public function test_characterization_null_organization_fails_closed(): void
    {
        $orgA = Organization::factory()->create();
        Employee::factory()->create(['organization_id' => $orgA->id]);

        $nullOrgUser = User::factory()->create(['organization_id' => null]);
        $nullOrgUser->givePermissionTo(PermissionEnum::EMPLOYEE_VIEW_UNIT->value);

        $this->expectException(AuthorizationException::class);
        $this->scopeService->scopeVisibleTo(Employee::query(), $nullOrgUser);
    }

    /**
     * Section 13 — Unsupported Model Fails Closed:
     * Model without recognized organization contract/registry throws OrganizationScopeException.
     * Even a System Admin or global user cannot bypass an unsupported model contract.
     */
    public function test_characterization_unsupported_model_fails_closed_for_all_users(): void
    {
        $orgA = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->assignRole('System Admin');

        $unsupportedModel = new class extends Model {};

        $this->expectException(OrganizationScopeException::class);
        $this->scopeService->scopeVisibleTo($unsupportedModel->newQuery(), $admin);
    }

    /**
     * Section 24 — Broken Path Fails Closed:
     * Structurally invalid paths throw OrganizationScopeException.
     */
    public function test_characterization_broken_path_fails_closed(): void
    {
        $orgA = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $orgA->id]);

        $brokenModel = new class extends Model implements OrganizationScopedModel
        {
            public function organizationScopePath(): string
            {
                return 'nonExistentRelation.organization_id';
            }
        };

        $this->expectException(OrganizationScopeException::class);
        $this->scopeService->scopeVisibleTo($brokenModel->newQuery(), $user);
    }

    /**
     * Section 19 — Safe Visible Object Resolution:
     * resolveVisible scopes the query first, then finds the ID.
     * If the ID belongs to another organization, it throws ModelNotFoundException (404),
     * avoiding leaking foreign record existence.
     */
    public function test_safe_visible_object_resolution_primitive(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo(PermissionEnum::EMPLOYEE_VIEW_UNIT->value);

        $employeeA = Employee::factory()->create(['organization_id' => $orgA->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        // Resolving own organization's object succeeds
        $resolvedA = $this->scopeService->resolveVisible(Employee::class, $userA, $employeeA->id);
        $this->assertSame($employeeA->id, $resolvedA->id);

        // Resolving from a pre-existing builder succeeds
        $builderResolved = $this->scopeService->resolveVisible(Employee::query()->where('status', $employeeA->status), $userA, $employeeA->id);
        $this->assertSame($employeeA->id, $builderResolved->id);

        // Resolving foreign organization's object throws ModelNotFoundException (404)
        $this->expectException(ModelNotFoundException::class);
        $this->scopeService->resolveVisible(Employee::class, $userA, $employeeB->id);
    }

    /**
     * Section 19 — Cooperative resolveVisible on OrganizationScopedQueryService:
     */
    public function test_cooperative_scoped_query_service_resolve_visible(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->assignRole('Admin Koperasi');

        $memberA = CooperativeMember::factory()->create(['organization_id' => $orgA->id]);
        $memberB = CooperativeMember::factory()->create(['organization_id' => $orgB->id]);

        $resolved = $this->scopedQueryService->resolveVisible(CooperativeMember::class, $userA, $memberA->id);
        $this->assertSame($memberA->id, $resolved->id);

        $this->expectException(ModelNotFoundException::class);
        $this->scopedQueryService->resolveVisible(CooperativeMember::class, $userA, $memberB->id);
    }

    /**
     * RULE F & Section 29 — Parent / Child Security:
     * /{parentId}/{childId} endpoints must resolve the authorized parent first,
     * then resolve the child through the parent relationship.
     * Foreign parent access returns 404.
     * Swapping IDs (valid parent A + child B belonging to parent B) returns 404.
     * Foreign child in DB remains completely unchanged.
     */
    public function test_parent_child_isolation_prevents_cross_parent_swapping_and_foreign_child_access(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo([
            PermissionEnum::EMPLOYEE_VIEW_UNIT->value,
            PermissionEnum::EMPLOYEE_EDIT->value,
        ]);

        $employeeA = Employee::factory()->create(['organization_id' => $orgA->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        $certA = EmployeeCertificate::query()->create([
            'employee_id' => $employeeA->id,
            'certificate_type' => \App\Enums\CertificateType::TRAINING,
            'certificate_number' => 'CERT-A-001',
            'issuing_authority' => 'Issuer A',
            'issue_date' => now()->subYear(),
            'expiry_date' => now()->addYears(2),
        ]);

        $certB = EmployeeCertificate::query()->create([
            'employee_id' => $employeeB->id,
            'certificate_type' => \App\Enums\CertificateType::TRAINING,
            'certificate_number' => 'CERT-B-001',
            'issuing_authority' => 'Issuer B',
            'issue_date' => now()->subYear(),
            'expiry_date' => now()->addYears(2),
        ]);

        Sanctum::actingAs($userA, ['employee-documents:read', 'employee-documents:write']);

        // 1. Authorized parent + child succeeds
        $responseA = $this->getJson("/api/employees/{$employeeA->id}/certificates/{$certA->id}");
        $responseA->assertOk();
        $responseA->assertJsonPath('data.id', $certA->id);

        // 2. Foreign parent + child denied (404)
        $responseForeignParent = $this->getJson("/api/employees/{$employeeB->id}/certificates/{$certB->id}");
        $responseForeignParent->assertNotFound();

        // 3. Parent A + Child B (cross-parent ID swap attack) denied (404)
        $responseSwapped = $this->getJson("/api/employees/{$employeeA->id}/certificates/{$certB->id}");
        $responseSwapped->assertNotFound();

        // 4. Mutation attempt on swapped child is denied (404)
        $responseMutation = $this->putJson("/api/employees/{$employeeA->id}/certificates/{$certB->id}", [
            'certificate_number' => 'TAMPERED-001',
            'issuing_authority' => 'Attacker',
            'issue_date' => '2025-01-01',
        ]);
        $responseMutation->assertNotFound();

        // 5. Assert foreign child B state in DB remains completely unchanged
        $certB->refresh();
        $this->assertSame('CERT-B-001', $certB->certificate_number);
        $this->assertSame('Issuer B', $certB->issuing_authority);
        $this->assertSame($employeeB->id, $certB->employee_id);
    }

    /**
     * Section 26 — Representative Cross-Org Read:
     * User A from Org A cannot read Org B member or redemption details.
     */
    public function test_representative_cross_org_read_is_denied(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->assignRole('Admin Koperasi');

        $memberA = CooperativeMember::factory()->create(['organization_id' => $orgA->id]);
        $memberB = CooperativeMember::factory()->create(['organization_id' => $orgB->id]);

        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
        ]);

        // Reading member of Org B returns 403
        $this->actingAs($adminA)->get(route('cooperative.members.show', $memberA))->assertOk();
        $this->actingAs($adminA)->get(route('cooperative.members.show', $memberB))->assertForbidden();

        // Reading redemption of Org B returns 403
        $this->actingAs($adminA)->get(route('cooperative.redemptions.show', $redemptionB))->assertForbidden();
    }

    /**
     * Section 27 — Representative Cross-Org Mutation:
     * Org A actor cannot mutate Org B object.
     * Foreign database row remains completely unchanged.
     */
    public function test_representative_cross_org_mutation_is_denied_and_foreign_state_is_unchanged(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->assignRole('Admin Koperasi');

        $memberA = CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Original Member A',
        ]);
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Original Member B',
            'email' => 'memberb@example.com',
            'phone' => '081299998888',
        ]);

        $beforeState = $memberB->fresh()->toArray();

        // Mutating Org A member succeeds
        $this->actingAs($adminA)->put(route('cooperative.members.update', $memberA), [
            'name' => 'Updated Member A',
            'nama_anggota' => 'Updated Member A',
            'email' => 'updated-a@example.com',
            'phone' => '081211112222',
            'no_telp' => '081211112222',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ])->assertRedirect();

        $this->assertSame('Updated Member A', $memberA->fresh()->name);

        // Mutating Org B member is denied with 403 Forbidden
        $response = $this->actingAs($adminA)->put(route('cooperative.members.update', $memberB), [
            'name' => 'Hacked Member B',
            'nama_anggota' => 'Hacked Member B',
            'email' => 'hacked-b@example.com',
            'phone' => '081266667777',
            'no_telp' => '081266667777',
            'jenis_anggota' => 'ALB',
            'jenis_kelamin' => 'P',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);
        $response->assertForbidden();

        // Foreign DB state remains completely unchanged
        $afterState = $memberB->fresh()->toArray();
        $this->assertSame($beforeState['name'], $afterState['name']);
        $this->assertSame($beforeState['email'], $afterState['email']);
        $this->assertSame($beforeState['phone'], $afterState['phone']);
        $this->assertSame($beforeState['organization_id'], $afterState['organization_id']);
    }

    /**
     * RULE G & Section 30 — Client Ownership Forgery:
     * Never trust ownership identifiers from normal scoped requests.
     * When creating a member with organization_id = Org B, the validation layer prohibits it.
     * When updating a member, organization_id is not mass-assignable/prohibited from request.
     */
    public function test_client_ownership_forgery_is_prevented_on_creation_and_update(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->assignRole('Admin Koperasi');

        // 1. Attempting to create a member in Org B by submitting organization_id in request
        // The validation layer explicitly prohibits client assignment of tenant ownership (Rule G).
        $response = $this->actingAs($adminA)->post(route('cooperative.members.store'), [
            'name' => 'Forged Org Member',
            'nama_anggota' => 'Forged Org Member',
            'email' => 'forged@example.com',
            'phone' => '081234567890',
            'organization_id' => $orgB->id, // Malicious forged tenant id
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
            'tanggal_aktif' => now()->toDateString(),
        ]);
        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('cooperative_members', ['email' => 'forged@example.com']);

        // 2. Normal creation assigns authenticated actor's organization
        $validResponse = $this->actingAs($adminA)->post(route('cooperative.members.store'), [
            'name' => 'Legit Member',
            'nama_anggota' => 'Legit Member',
            'email' => 'legit@example.com',
            'phone' => '081234567891',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
            'tanggal_aktif' => now()->toDateString(),
        ]);
        $validResponse->assertRedirect();
        $createdMember = CooperativeMember::query()->where('email', 'legit@example.com')->firstOrFail();
        $this->assertSame($orgA->id, $createdMember->organization_id);

        // 3. Attempting to move member to Org B on update is prohibited
        $updateResponse = $this->actingAs($adminA)->put(route('cooperative.members.update', $createdMember), [
            'name' => 'Updated Member Name',
            'nama_anggota' => 'Updated Member Name',
            'email' => 'legit@example.com',
            'phone' => '081234567891',
            'organization_id' => $orgB->id, // Attempting to hijack organization
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);
        $updateResponse->assertSessionHasErrors('organization_id');
        $this->assertSame($orgA->id, $createdMember->fresh()->organization_id);
    }

    /**
     * Section 24 — Contract & Registry Integrity:
     * Automated test ensuring all registered paths are structurally valid
     * against real model schemas and relations, and all global permissions are defined.
     */
    public function test_registry_contract_integrity_for_all_registered_models(): void
    {
        $registeredPaths = $this->scopeService->registeredPaths();
        $this->assertNotEmpty($registeredPaths);

        foreach ($registeredPaths as $modelClass => $path) {
            $this->assertTrue(class_exists($modelClass), "Registered model class [{$modelClass}] does not exist.");

            /** @var Model $instance */
            $instance = new $modelClass;
            $this->assertInstanceOf(Model::class, $instance);

            // pathFor structurally validates the path
            $resolvedPath = $this->scopeService->pathFor($instance);
            $this->assertSame($path, $resolvedPath, "Path for [{$modelClass}] mismatch.");
        }
    }

    /**
     * Section 22 — Contract Precedence & Model-Level Global Permission:
     */
    public function test_model_contract_takes_precedence_over_registry_and_supports_custom_global_permission(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $customModel = new class extends Employee implements OrganizationScopedModel
        {
            protected $table = 'employees';

            public function organizationScopePath(): string
            {
                return 'organization_id';
            }

            public function organizationGlobalPermission(): ?string
            {
                return 'custom_global_permission';
            }
        };

        $this->assertSame('organization_id', $this->scopeService->pathFor($customModel));
        $this->assertSame('custom_global_permission', $this->scopeService->globalPermissionFor($customModel));

        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $visibility = $this->scopeService->visibilityFor($user, $this->scopeService->globalPermissionFor($customModel));
        $this->assertFalse($visibility->global);
        $this->assertSame($orgA->id, $visibility->organizationId);
    }

    /**
     * Section 22 — Validating Organization Identifiers:
     */
    public function test_assert_organization_identifier_validates_existence_and_rejects_empty_or_missing(): void
    {
        $org = Organization::factory()->create();

        $resolved = $this->scopeService->assertOrganizationIdentifier($org->id);
        $this->assertSame((string) $org->id, $resolved);

        $this->expectException(AuthorizationException::class);
        $this->scopeService->assertOrganizationIdentifier('non-existent-uuid-1234');
    }
}
