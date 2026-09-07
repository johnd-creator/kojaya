<?php

namespace Tests\Feature\Security;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\PosTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\Cooperative\MemberPointService;
use App\Services\Cooperative\PointService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PointsAdminOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private PointService $pointService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->pointService = app(PointService::class);
    }

    /**
     * Test 01: Admin list is scoped to actor's organization (cross-tenant invisible).
     */
    public function test_01_admin_list_is_scoped_to_actors_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        $memberA1 = $this->createMember($orgA, 100);
        $memberA2 = $this->createMember($orgA, 200);
        $memberB = $this->createMember($orgB, 500);

        $this->actingAs($staffA)
            ->get('/cooperative/points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 2)
                ->has('members.data', 2)
                ->where('members.data.0.id', fn ($id) => in_array($id, [$memberA1->id, $memberA2->id], true))
                ->where('members.data.1.id', fn ($id) => in_array($id, [$memberA1->id, $memberA2->id], true))
                ->where('members.data.0.id', fn ($id) => $id !== $memberB->id)
            );
    }

    /**
     * Test 02: Admin list filtering by status preserves tenant isolation.
     */
    public function test_02_admin_list_filtering_by_status_preserves_tenant_isolation(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        $activeA = $this->createMember($orgA, 150);
        $inactiveA = $this->createMember($orgA, 50);
        $inactiveA->update(['status' => 'INACTIVE']);

        $inactiveB = $this->createMember($orgB, 300);
        $inactiveB->update(['status' => 'INACTIVE']);

        $this->actingAs($staffA)
            ->get('/cooperative/points?status=INACTIVE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 1)
                ->where('members.data.0.id', $inactiveA->id)
            );
    }

    /**
     * Test 03: Admin list search by name/member_no is scoped to actor's organization.
     */
    public function test_03_admin_list_search_is_scoped_to_actors_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        $memberA = $this->createMember($orgA, 100);
        $memberA->update(['name' => 'Budi Santoso OrgA', 'member_no' => 'MBR-A-001']);

        $memberB = $this->createMember($orgB, 500);
        $memberB->update(['name' => 'Budi Santoso OrgB', 'member_no' => 'MBR-B-001']);

        // Search name matching both, should only find Org A
        $this->actingAs($staffA)
            ->get('/cooperative/points?search=Budi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 1)
                ->where('members.data.0.id', $memberA->id)
            );

        // Search specific to Org B returns empty for Staff A
        $this->actingAs($staffA)
            ->get('/cooperative/points?search=MBR-B-001')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 0)
            );
    }

    /**
     * Test 04: Pagination count and totals never leak across organizations.
     */
    public function test_04_pagination_count_and_totals_never_leak_across_organizations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        // Create 3 members in orgA, 15 members in orgB
        for ($i = 0; $i < 3; $i++) {
            $this->createMember($orgA, 10);
        }
        for ($i = 0; $i < 15; $i++) {
            $this->createMember($orgB, 10);
        }

        $this->actingAs($staffA)
            ->get('/cooperative/points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 3)
                ->where('members.last_page', 1)
            );
    }

    /**
     * Test 05: Aggregates / stats (active_members, total_balance) are strictly scoped.
     */
    public function test_05_aggregates_and_stats_are_strictly_scoped_to_actors_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        $memberA1 = $this->createMember($orgA, 300);
        $memberA2 = $this->createMember($orgA, 200);
        $memberB = $this->createMember($orgB, 10000);

        $this->actingAs($staffA)
            ->get('/cooperative/points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('stats.active_members', 2)
                ->where('stats.total_balance', 500)
            );
    }

    /**
     * Test 06: Direct IDOR / point adjustment for foreign member returns 404 and fails closed.
     */
    public function test_06_direct_idor_point_adjustment_for_foreign_member_returns_404_and_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberB = $this->createMember($orgB, 500);

        $this->actingAs($staffA)
            ->post("/cooperative/points/{$memberB->id}/adjust", [
                'points' => 50,
                'description' => 'Malicious IDOR adjustment',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('point_transactions', [
            'cooperative_member_id' => $memberB->id,
            'description' => 'Malicious IDOR adjustment',
        ]);
    }

    /**
     * Test 07: Direct service call to PointService::balanceSummary for null-org member throws AuthorizationException.
     */
    public function test_07_direct_service_call_to_balance_summary_for_null_org_member_throws(): void
    {
        $orphanMember = $this->createMember(null, 100);

        $this->expectException(AuthorizationException::class);
        $this->pointService->balanceSummary($orphanMember);
    }

    /**
     * Test 08: Direct service call to PointService::historyQuery validates organization ownership.
     */
    public function test_08_direct_service_call_to_history_query_validates_organization_ownership(): void
    {
        $orphanMember = $this->createMember(null, 100);

        $this->expectException(AuthorizationException::class);
        $this->pointService->historyQuery($orphanMember);
    }

    /**
     * Test 09: Direct service call to PointService::syncPosPoints ignores foreign POS transactions.
     */
    public function test_09_direct_service_call_to_sync_pos_points_ignores_foreign_pos_transactions(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        $foreignTx = $this->createPosTransaction($orgB, $memberA, 100000);

        $foreignPosPoint = PosMemberPoint::query()->create([
            'pos_transaction_id' => $foreignTx->id,
            'cooperative_member_id' => $memberA->id,
            'year' => now()->year,
            'profit_amount' => 100000,
            'points' => 100,
            'posted_at' => now()->toDateString(),
        ]);

        $this->pointService->syncPosPoints($memberA);

        // Foreign POS point should NOT be converted into a PointTransaction for memberA
        $this->assertDatabaseMissing('point_transactions', [
            'cooperative_member_id' => $memberA->id,
            'source_type' => PosMemberPoint::class,
            'source_id' => (string) $foreignPosPoint->id,
        ]);
    }

    /**
     * Test 10: Point transaction creation via PointService::recordTransaction validates organization ownership.
     */
    public function test_10_point_transaction_creation_validates_organization_ownership(): void
    {
        $orphanMember = $this->createMember(null, 0);

        $this->expectException(AuthorizationException::class);
        $this->pointService->recordTransaction(
            member: $orphanMember,
            transactionType: 'EARNED',
            points: 100,
            description: 'Test point',
            postedAt: now()
        );
    }

    /**
     * Test 11: Point adjustment endpoint succeeds for same-org member.
     */
    public function test_11_point_adjustment_endpoint_succeeds_for_same_org_member(): void
    {
        [$orgA] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberA = $this->createMember($orgA, 200);

        $this->actingAs($staffA)
            ->from('/cooperative/points')
            ->post("/cooperative/points/{$memberA->id}/adjust", [
                'points' => 50,
                'description' => 'Bonus loyalitas tahunan',
            ])
            ->assertRedirect('/cooperative/points')
            ->assertSessionHas('success', 'Poin anggota berhasil disesuaikan.');

        $this->assertDatabaseHas('point_transactions', [
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'ADJUSTMENT',
            'points' => 50,
            'balance_before' => 200,
            'balance_after' => 250,
            'description' => 'Bonus loyalitas tahunan',
        ]);
    }

    /**
     * Test 12: Point adjustment endpoint rejects adjustment for foreign-org member.
     */
    public function test_12_point_adjustment_endpoint_rejects_adjustment_for_foreign_member(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberB = $this->createMember($orgB, 300);

        $this->actingAs($staffA)
            ->post("/cooperative/points/{$memberB->id}/adjust", [
                'points' => -50,
                'description' => 'Cross-org reduction attempt',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('point_transactions', [
            'cooperative_member_id' => $memberB->id,
            'description' => 'Cross-org reduction attempt',
        ]);
    }

    /**
     * Test 13: Point adjustment cannot reduce balance below zero.
     */
    public function test_13_point_adjustment_cannot_reduce_balance_below_zero(): void
    {
        [$orgA] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberA = $this->createMember($orgA, 100);

        $this->actingAs($staffA)
            ->from('/cooperative/points')
            ->post("/cooperative/points/{$memberA->id}/adjust", [
                'points' => -150,
                'description' => 'Excessive deduction',
            ])
            ->assertSessionHasErrors(['points']);

        $summary = $this->pointService->balanceSummary($memberA);
        $this->assertSame(100, $summary['total_points']);
    }

    /**
     * Test 14: Point adjustment creates PointTransaction with correct tenant attribution and audit trail metadata.
     */
    public function test_14_point_adjustment_creates_correct_tenant_attribution_and_metadata(): void
    {
        [$orgA] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberA = $this->createMember($orgA, 50);

        $this->actingAs($staffA)
            ->post("/cooperative/points/{$memberA->id}/adjust", [
                'points' => 25,
                'description' => 'Audit check adjustment',
            ])
            ->assertRedirect();

        $transaction = PointTransaction::query()
            ->where('cooperative_member_id', $memberA->id)
            ->where('transaction_type', 'ADJUSTMENT')
            ->firstOrFail();

        $this->assertSame(25, (int) $transaction->points);
        $this->assertSame((string) $staffA->id, (string) $transaction->source_id);
        $this->assertSame(User::class, $transaction->source_type);
        $this->assertNotNull($transaction->metadata);
        $this->assertSame((string) $staffA->id, (string) $transaction->metadata['adjusted_by']);
        $this->assertSame((string) $orgA->id, (string) $transaction->metadata['organization_id']);
    }

    /**
     * Test 15: Point adjustment DB transaction locking atomic execution.
     */
    public function test_15_point_adjustment_db_transaction_locking(): void
    {
        [$orgA] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberA = $this->createMember($orgA, 100);

        $tx = $this->pointService->adjust(
            actor: $staffA,
            member: $memberA,
            points: 50,
            description: 'Direct adjustment test'
        );

        $this->assertInstanceOf(PointTransaction::class, $tx);
        $this->assertSame(150, (int) $tx->balance_after);
    }

    /**
     * Test 16: Global actor with view_cooperative_all can view list across organizations or target specific organization.
     */
    public function test_16_global_actor_with_view_cooperative_all_can_scope_by_target_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaff = $this->createGlobalStaff($orgA);

        $memberA = $this->createMember($orgA, 100);
        $memberB = $this->createMember($orgB, 200);

        // Targeted to Org B
        $this->actingAs($globalStaff)
            ->get("/cooperative/points?organization_id={$orgB->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 1)
                ->where('members.data.0.id', $memberB->id)
                ->where('stats.total_balance', 200)
            );

        // Targeted to Org A
        $this->actingAs($globalStaff)
            ->get("/cooperative/points?organization_id={$orgA->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Points/Index')
                ->where('members.total', 1)
                ->where('members.data.0.id', $memberA->id)
                ->where('stats.total_balance', 100)
            );
    }

    /**
     * Test 17: Global actor with view_cooperative_all can adjust member point with explicit target organization.
     */
    public function test_17_global_actor_can_adjust_with_explicit_target_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaff = $this->createGlobalStaff($orgA);
        $memberB = $this->createMember($orgB, 300);

        $this->actingAs($globalStaff)
            ->post("/cooperative/points/{$memberB->id}/adjust", [
                'points' => 50,
                'description' => 'Global adjustment on Org B',
                'organization_id' => $orgB->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('point_transactions', [
            'cooperative_member_id' => $memberB->id,
            'transaction_type' => 'ADJUSTMENT',
            'points' => 50,
            'balance_after' => 350,
        ]);
    }

    /**
     * Test 18: Global actor missing target organization on adjustment fails closed.
     */
    public function test_18_global_actor_missing_target_organization_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaffWithoutOrg = $this->createGlobalStaff(null);
        $memberB = $this->createMember($orgB, 300);

        $this->actingAs($globalStaffWithoutOrg)
            ->from('/cooperative/points')
            ->post("/cooperative/points/{$memberB->id}/adjust", [
                'points' => 50,
                'description' => 'Adjustment without target org',
            ])
            ->assertRedirect('/cooperative/points')
            ->assertSessionHasErrors(['organization_id']);

        $this->assertDatabaseMissing('point_transactions', [
            'cooperative_member_id' => $memberB->id,
            'description' => 'Adjustment without target org',
        ]);
    }

    /**
     * Test 19: Unit actor attempting to specify foreign target organization fails closed (403).
     */
    public function test_19_unit_actor_attempting_to_specify_foreign_target_org_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $memberA = $this->createMember($orgA, 100);

        // Passing foreign orgB in index query
        $this->actingAs($staffA)
            ->get("/cooperative/points?organization_id={$orgB->id}")
            ->assertForbidden();

        // Passing foreign orgB in adjust payload
        $this->actingAs($staffA)
            ->post("/cooperative/points/{$memberA->id}/adjust", [
                'points' => 20,
                'description' => 'Forged target org',
                'organization_id' => $orgB->id,
            ])
            ->assertForbidden();
    }

    /**
     * Test 20: Actor with manage_cooperative_points without view_cooperative_all is restricted to own organization.
     */
    public function test_20_actor_without_view_cooperative_all_restricted_to_own_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);

        $memberB = $this->createMember($orgB, 100);
        $txB = PointTransaction::query()->create([
            'cooperative_member_id' => $memberB->id,
            'transaction_type' => 'EARNED',
            'points' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'posted_at' => now()->toDateString(),
            'description' => 'Org B point',
        ]);

        $this->assertFalse($staffA->can('view', $txB));
        $this->assertFalse($staffA->can('update', $txB));
    }

    /**
     * Test 21: Actor without manage_cooperative_points cannot access points admin or adjust.
     */
    public function test_21_actor_without_manage_cooperative_points_cannot_access_or_adjust(): void
    {
        [$orgA] = $this->createOrganizations();
        $regularUser = User::factory()->create(['organization_id' => $orgA->id]);
        $memberA = $this->createMember($orgA, 100);

        $this->actingAs($regularUser)
            ->get('/cooperative/points')
            ->assertForbidden();

        $this->actingAs($regularUser)
            ->post("/cooperative/points/{$memberA->id}/adjust", [
                'points' => 10,
                'description' => 'Unauthorized adjustment',
            ])
            ->assertForbidden();
    }

    /**
     * Test 22: Legacy unassigned member (null organization_id) cannot be targeted in points admin.
     */
    public function test_22_legacy_unassigned_member_cannot_be_targeted(): void
    {
        [$orgA] = $this->createOrganizations();
        $staffA = $this->createPointsStaff($orgA);
        $orphanMember = $this->createMember(null, 100);

        $this->actingAs($staffA)
            ->post("/cooperative/points/{$orphanMember->id}/adjust", [
                'points' => 20,
                'description' => 'Target orphan member',
            ])
            ->assertNotFound();
    }

    /**
     * Test 23: Member portal points view cannot view foreign point history.
     */
    public function test_23_member_portal_points_view_cannot_view_foreign_points(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, 100);
        $memberB = $this->createMember($orgB, 500);

        $userA = $memberA->user;

        $this->actingAs($userA)
            ->get('/member/points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Points')
                ->where('summary.total_points', 100)
                ->where('history.total', 1)
            );
    }

    /**
     * Test 24: Member portal points view fails closed if member has null organization_id.
     */
    public function test_24_member_portal_points_view_fails_closed_if_member_has_null_org(): void
    {
        $orphanMember = $this->createMember(null, 50);
        $orphanUser = $orphanMember->user;

        $this->actingAs($orphanUser)
            ->get('/member/points')
            ->assertForbidden();
    }

    /**
     * Test 25: API v1 point endpoints fail closed if member has null organization_id.
     */
    public function test_25_api_v1_point_endpoints_fail_closed_if_member_has_null_org(): void
    {
        $orphanMember = $this->createMember(null, 50);
        $orphanUser = $orphanMember->user;

        $this->actingAs($orphanUser)
            ->getJson('/api/v1/points/balance')
            ->assertForbidden();

        $this->actingAs($orphanUser)
            ->getJson('/api/v1/points/history')
            ->assertForbidden();
    }

    /**
     * Test 26: MemberPointService::postFromTransaction validates organization match.
     */
    public function test_26_member_point_service_validates_organization_match(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        $foreignTx = $this->createPosTransaction($orgB, $memberA, 50000);

        $service = app(MemberPointService::class);

        $this->expectException(AuthorizationException::class);
        $service->postFromTransaction($foreignTx);
    }

    /**
     * Test 27: PointService::redeem validates member and locked reward belong to same organization.
     */
    public function test_27_point_service_redeem_validates_same_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, 1000);
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->pointService->redeem($memberA, $rewardB, 1);
    }

    /**
     * Test 28: PointService::updateRedemptionStatus validates member and reward integrity.
     */
    public function test_28_point_service_update_redemption_status_validates_integrity(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, 500);
        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);

        $redemption = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);

        // Tamper redemption to point to member from Org B
        $memberB = $this->createMember($orgB, 500);
        $redemption->update(['cooperative_member_id' => $memberB->id]);

        $this->expectException(AuthorizationException::class);
        $this->pointService->updateRedemptionStatus($redemption, 'SHIPPED');
    }

    /**
     * Helper to create two organizations.
     *
     * @return array{0: Organization, 1: Organization}
     */
    private function createOrganizations(): array
    {
        return [
            Organization::factory()->create(),
            Organization::factory()->create(),
        ];
    }

    /**
     * Helper to create staff with manage_cooperative_points.
     */
    private function createPointsStaff(Organization $organization): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo(PermissionEnum::COOPERATIVE_POINTS_MANAGE->value);

        return $staff;
    }

    /**
     * Helper to create global staff with view_cooperative_all and manage_cooperative_points.
     */
    private function createGlobalStaff(?Organization $organization): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization?->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo([
            PermissionEnum::COOPERATIVE_POINTS_MANAGE->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        ]);

        return $staff;
    }

    /**
     * Helper to create a member with initial points.
     */
    private function createMember(?Organization $organization = null, int $points = 0): CooperativeMember
    {
        $orgId = $organization?->id;

        if ($orgId === null) {
            Schema::table('cooperative_members', function (Blueprint $table): void {
                $table->uuid('organization_id')->nullable()->change();
            });
        }

        $user = User::factory()->create([
            'organization_id' => $orgId,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $orgId,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        if ($points > 0) {
            PointTransaction::query()->create([
                'cooperative_member_id' => $member->id,
                'transaction_type' => 'EARNED',
                'points' => $points,
                'balance_before' => 0,
                'balance_after' => $points,
                'posted_at' => now()->toDateString(),
                'description' => 'Initial test balance',
            ]);
        }

        return $member;
    }

    /**
     * Helper to create a POS transaction.
     */
    private function createPosTransaction(Organization $org, CooperativeMember $member, float $grossProfit = 50000): PosTransaction
    {
        return PosTransaction::query()->create([
            'transaction_no' => 'POS-TRX-'.Str::random(8),
            'organization_id' => $org->id,
            'cooperative_member_id' => $member->id,
            'cashier_id' => $member->user_id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'gross_profit' => $grossProfit,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
    }

    /**
     * Helper to create reward staff with manage_cooperative_rewards and manage_cooperative_redemption.
     */
    private function createRewardStaff(Organization $organization): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo([
            PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
            PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value,
        ]);

        return $staff;
    }

    /**
     * Helper to create global reward staff with view_cooperative_all and reward management permissions.
     */
    private function createGlobalRewardStaff(?Organization $organization = null): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization?->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
            PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value,
            PermissionEnum::COOPERATIVE_POINTS_MANAGE->value,
        ]);

        return $staff;
    }

    /**
     * Test 29: R2-01, R2-02, R2-03: Reward create explicit tenant targeting and fail-closed rules.
     */
    public function test_29_reward_create_explicit_tenant_targeting(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalStaffA = $this->createGlobalRewardStaff($orgA);
        $unitStaffA = $this->createRewardStaff($orgA);

        // R2-01: Global actor with home Org A omits organization_id -> validation failure (422), no reward created.
        $this->actingAs($globalStaffA)
            ->post('/cooperative/rewards', [
                'name' => 'Reward Omitted Org',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertDatabaseMissing('rewards', ['name' => 'Reward Omitted Org']);

        // R2-02: Global actor with home Org A specifies explicit target Org B -> Reward created in Org B.
        $this->actingAs($globalStaffA)
            ->post('/cooperative/rewards', [
                'organization_id' => $orgB->id,
                'name' => 'Reward Target Org B',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rewards', [
            'name' => 'Reward Target Org B',
            'organization_id' => $orgB->id,
        ]);

        // R2-03: Unit actor in Org A attempts explicit Org B -> 403 Forbidden / fail closed, no reward created.
        $this->actingAs($unitStaffA)
            ->post('/cooperative/rewards', [
                'organization_id' => $orgB->id,
                'name' => 'Reward Illicit Target',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('rewards', ['name' => 'Reward Illicit Target']);
    }

    /**
     * Test 30: R2-04, R2-05, R2-06: Reward update explicit tenant targeting and match enforcement.
     */
    public function test_30_reward_update_explicit_tenant_targeting(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalStaffA = $this->createGlobalRewardStaff($orgA);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Original Reward B',
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        // R2-04: Global actor updates Reward Org B with no explicit tenant -> fail closed (422), unchanged.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'name' => 'Updated Without Org',
                'category' => 'BARANG',
                'points_required' => 150,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('Original Reward B', $rewardB->fresh()->name);

        // R2-05: Global actor updates Reward Org B with mismatched target Org A -> fail closed (403), unchanged.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $orgA->id,
                'name' => 'Updated Mismatched Org',
                'category' => 'BARANG',
                'points_required' => 150,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertSame('Original Reward B', $rewardB->fresh()->name);

        // R2-06: Global actor updates Reward Org B with matching target Org B -> success, updated, remains Org B.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $orgB->id,
                'name' => 'Updated Correct Org',
                'category' => 'BARANG',
                'points_required' => 150,
                'stock' => 15,
                'is_active' => true,
            ])
            ->assertRedirect();

        $freshReward = $rewardB->fresh();
        $this->assertSame('Updated Correct Org', $freshReward->name);
        $this->assertSame(150, $freshReward->points_required);
        $this->assertSame(15, $freshReward->stock);
        $this->assertSame($orgB->id, $freshReward->organization_id);
    }

    /**
     * Test 31: R2-07, R2-08, R2-09: Reward delete explicit tenant targeting and match enforcement.
     */
    public function test_31_reward_delete_explicit_tenant_targeting(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalStaffA = $this->createGlobalRewardStaff($orgA);

        $rewardB1 = Reward::factory()->create(['organization_id' => $orgB->id, 'name' => 'Reward B1']);
        $rewardB2 = Reward::factory()->create(['organization_id' => $orgB->id, 'name' => 'Reward B2']);
        $rewardB3 = Reward::factory()->create(['organization_id' => $orgB->id, 'name' => 'Reward B3']);

        // R2-07: Global actor deletes Reward Org B without explicit target -> fail closed (422), still exists.
        $this->actingAs($globalStaffA)
            ->delete("/cooperative/rewards/{$rewardB1->id}")
            ->assertSessionHasErrors(['organization_id']);

        $this->assertModelExists($rewardB1);

        // R2-08: Global actor deletes Reward Org B with mismatched target Org A -> fail closed (403), still exists.
        $this->actingAs($globalStaffA)
            ->delete("/cooperative/rewards/{$rewardB2->id}", [
                'organization_id' => $orgA->id,
            ])
            ->assertForbidden();

        $this->assertModelExists($rewardB2);

        // R2-09: Global actor deletes Reward Org B with correct explicit target Org B -> success, deleted.
        $this->actingAs($globalStaffA)
            ->delete("/cooperative/rewards/{$rewardB3->id}", [
                'organization_id' => $orgB->id,
            ])
            ->assertRedirect();

        $this->assertModelMissing($rewardB3);
    }

    /**
     * Test 32: R2-10, R2-11, R2-12, R2-13: Redemption status mutation explicit tenant targeting.
     */
    public function test_32_redemption_status_mutation_explicit_tenant_targeting(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalStaffA = $this->createGlobalRewardStaff($orgA);
        $unitStaffA = $this->createRewardStaff($orgA);

        $memberB = $this->createMember($orgB, 500);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        // R2-10: Unit actor Org A tries to mutate Redemption Org B -> 404 (IDOR protection), status unchanged.
        $this->actingAs($unitStaffA)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'status' => 'PROCESSING',
            ])
            ->assertNotFound();

        $this->assertSame('PENDING', $redemptionB->fresh()->status);

        // R2-11: Global actor with home Org A mutates Redemption Org B without organization_id -> fail closed (422), status unchanged.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'status' => 'PROCESSING',
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('PENDING', $redemptionB->fresh()->status);

        // R2-12: Global actor mutates Redemption Org B with mismatched target Org A -> fail closed (403), status unchanged.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'organization_id' => $orgA->id,
                'status' => 'PROCESSING',
            ])
            ->assertForbidden();

        $this->assertSame('PENDING', $redemptionB->fresh()->status);

        // R2-13: Global actor mutates Redemption Org B with correct target Org B -> success, status updated.
        $this->actingAs($globalStaffA)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'organization_id' => $orgB->id,
                'status' => 'SHIPPED',
            ])
            ->assertRedirect();

        $this->assertSame('SHIPPED', $redemptionB->fresh()->status);
    }

    /**
     * Test 33: Section 8: Home and session active_organization_id cannot silently determine tenant target.
     */
    public function test_33_home_and_session_fallback_cannot_silently_determine_tenant_target(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        // Deliberately establish misleading fallback context: home org = Org A, session active_organization_id = Org A
        $globalStaffA = $this->createGlobalRewardStaff($orgA);

        $memberB = $this->createMember($orgB, 500);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id, 'name' => 'Fallback Test Reward']);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $sessionContext = ['active_organization_id' => $orgA->id];

        // 1. Reward Create: missing explicit target is rejected (never falls back to home/session Org A)
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->post('/cooperative/rewards', [
                'name' => 'Session Fallback Reward',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertDatabaseMissing('rewards', ['name' => 'Session Fallback Reward']);

        // Explicit Org B succeeds against Org B despite misleading session/home Org A
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->post('/cooperative/rewards', [
                'organization_id' => $orgB->id,
                'name' => 'Session Explicit Org B Reward',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rewards', [
            'name' => 'Session Explicit Org B Reward',
            'organization_id' => $orgB->id,
        ]);

        // 2. Reward Update: missing explicit target is rejected
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'name' => 'Session Update Attempt',
                'category' => 'BARANG',
                'points_required' => 120,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('Fallback Test Reward', $rewardB->fresh()->name);

        // Explicit Org B succeeds against Org B
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $orgB->id,
                'name' => 'Session Updated Reward B',
                'category' => 'BARANG',
                'points_required' => 120,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('Session Updated Reward B', $rewardB->fresh()->name);

        // 3. Redemption Status: missing explicit target is rejected
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'status' => 'PROCESSING',
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('PENDING', $redemptionB->fresh()->status);

        // Explicit Org B succeeds against Org B
        $this->actingAs($globalStaffA)
            ->withSession($sessionContext)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'organization_id' => $orgB->id,
                'status' => 'PROCESSING',
            ])
            ->assertRedirect();

        $this->assertSame('PROCESSING', $redemptionB->fresh()->status);
    }

    /**
     * Test 34: Section 9: Invalid target UUID and nonexistent organization fail closed.
     */
    public function test_34_invalid_target_organization_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaff = $this->createGlobalRewardStaff($orgA);

        $memberB = $this->createMember($orgB, 500);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $invalidUuid = 'invalid-not-a-uuid';
        $nonexistentUuid = '00000000-0000-0000-0000-000000000000';

        // Reward create with invalid UUID and nonexistent UUID
        $this->actingAs($globalStaff)
            ->post('/cooperative/rewards', [
                'organization_id' => $invalidUuid,
                'name' => 'Bad UUID Reward',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->actingAs($globalStaff)
            ->post('/cooperative/rewards', [
                'organization_id' => $nonexistentUuid,
                'name' => 'Ghost Org Reward',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        // Reward update with invalid UUID and nonexistent UUID
        $this->actingAs($globalStaff)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $invalidUuid,
                'name' => 'Bad UUID Update',
                'category' => 'BARANG',
                'points_required' => 120,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->actingAs($globalStaff)
            ->put("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $nonexistentUuid,
                'name' => 'Ghost Org Update',
                'category' => 'BARANG',
                'points_required' => 120,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        // Reward delete with invalid UUID and nonexistent UUID
        $this->actingAs($globalStaff)
            ->delete("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $invalidUuid,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->actingAs($globalStaff)
            ->delete("/cooperative/rewards/{$rewardB->id}", [
                'organization_id' => $nonexistentUuid,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertModelExists($rewardB);

        // Redemption status update with invalid UUID and nonexistent UUID
        $this->actingAs($globalStaff)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'organization_id' => $invalidUuid,
                'status' => 'PROCESSING',
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->actingAs($globalStaff)
            ->put("/cooperative/redemptions/{$redemptionB->id}/status", [
                'organization_id' => $nonexistentUuid,
                'status' => 'PROCESSING',
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('PENDING', $redemptionB->fresh()->status);
    }

    /**
     * Test 35: Section 5: PointService domain-level authority prevents tenant mismatch on direct call.
     */
    public function test_35_point_service_domain_level_authority_prevents_tenant_mismatch(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaffA = $this->createGlobalRewardStaff($orgA);

        $memberB = $this->createMember($orgB, 500);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        // 1. Direct service call with global actor omitting targetOrgId throws ValidationException
        $threwValidation = false;
        try {
            $this->pointService->updateRedemptionStatus(
                redemption: $redemptionB,
                status: 'SHIPPED',
                targetOrgId: null,
                actor: $globalStaffA,
            );
        } catch (ValidationException $e) {
            $threwValidation = true;
            $this->assertArrayHasKey('organization_id', $e->errors());
        }
        $this->assertTrue($threwValidation, 'Expected ValidationException when global actor omits targetOrgId on direct service call.');
        $this->assertSame('PENDING', $redemptionB->fresh()->status);

        // 2. Direct service call with global actor and mismatched targetOrgId throws AuthorizationException
        $this->expectException(AuthorizationException::class);
        $this->pointService->updateRedemptionStatus(
            redemption: $redemptionB,
            status: 'SHIPPED',
            targetOrgId: $orgA->id,
            actor: $globalStaffA,
        );
    }

    /**
     * Test 36: Direct service call with matching targetOrgId succeeds.
     */
    public function test_36_point_service_direct_call_with_matching_target_succeeds(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalStaffA = $this->createGlobalRewardStaff($orgA);

        $memberB = $this->createMember($orgB, 500);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $updated = $this->pointService->updateRedemptionStatus(
            redemption: $redemptionB,
            status: 'SHIPPED',
            targetOrgId: $orgB->id,
            actor: $globalStaffA,
        );

        $this->assertSame('SHIPPED', $updated->status);
        $this->assertSame('SHIPPED', $redemptionB->fresh()->status);
    }
}
