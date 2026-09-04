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
use App\Services\Cooperative\PointService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RewardRedemptionOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Phase 6 / Rule A / Rule H: Admin redemption list is strictly scoped to the actor's organization.
     * Foreign redemptions and foreign pagination counts are never leaked.
     */
    public function test_admin_list_is_scoped_to_actors_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);

        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionA1 = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);
        $redemptionA2 = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'DELIVERED',
        ]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($staffA)
            ->get('/cooperative/redemptions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Redemptions/Index')
                ->where('redemptions.total', 2)
                ->has('redemptions.data', 2)
                ->where('redemptions.data.0.id', fn ($id) => in_array($id, [$redemptionA1->id, $redemptionA2->id], true))
                ->where('redemptions.data.1.id', fn ($id) => in_array($id, [$redemptionA1->id, $redemptionA2->id], true))
            );
    }

    /**
     * Phase 6: Admin redemption list filtering by ?status=... maintains organization isolation.
     */
    public function test_admin_list_status_filter_preserves_organization_isolation(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);

        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionA1 = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);
        RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'DELIVERED',
        ]);
        RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($staffA)
            ->get('/cooperative/redemptions?status=PENDING')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Redemptions/Index')
                ->where('redemptions.total', 1)
                ->has('redemptions.data', 1)
                ->where('redemptions.data.0.id', $redemptionA1->id)
            );
    }

    /**
     * Phase 3 / Phase 4 / Response Semantics:
     * Staff cannot view foreign redemption detail.
     * Response is 404 Not Found to eliminate cross-tenant existence enumeration.
     */
    public function test_staff_cannot_view_foreign_organization_redemption_detail_returns_404(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberB = $this->createMember($orgB);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        // Foreign UUID produces 404
        $this->actingAs($staffA)
            ->get('/cooperative/redemptions/'.$redemptionB->id)
            ->assertNotFound();

        // Non-existent UUID produces identical 404 (anti-enumeration)
        $this->actingAs($staffA)
            ->get('/cooperative/redemptions/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    /**
     * Phase 5 / Critical Side Effects:
     * Foreign redemption mutation denial returns 404 and leaves foreign state strictly unchanged:
     * - No status change
     * - No notes change
     * - No processed_at change
     * - No reward stock modification
     * - No member points modification
     * - No point transactions created
     * - No REFUNDED transactions created
     * - No notification side effects dispatched
     */
    public function test_staff_cannot_update_foreign_organization_redemption_status_returns_404_and_preserves_foreign_state(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberB = $this->createMember($orgB);

        $initialPoints = 1500;
        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'transaction_type' => 'EARNED',
            'points' => $initialPoints,
            'balance_before' => 0,
            'balance_after' => $initialPoints,
            'description' => 'Saldo awal poin',
        ]);

        $initialStock = 8;
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'stock' => $initialStock,
            'points_required' => 400,
        ]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
            'points_used' => 400,
            'quantity' => 1,
            'notes' => 'Original note',
            'processed_at' => null,
        ]);

        // Capture initial foreign state evidence
        $initialPointTxCount = PointTransaction::query()->count();
        $initialRefundCount = PointTransaction::query()->where('transaction_type', 'REFUNDED')->count();
        $initialNotificationCount = DB::table('notifications')->count();

        // Attempt cross-tenant mutation by Staff A
        $response = $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionB->id.'/status', [
                'status' => 'CANCELLED',
                'notes' => 'Malicious cancellation by Staff A',
            ]);

        $response->assertNotFound();

        // Verify foreign state remains strictly identical
        $freshRedemption = $redemptionB->fresh();
        $this->assertSame('PENDING', $freshRedemption->status);
        $this->assertSame('Original note', $freshRedemption->notes);
        $this->assertNull($freshRedemption->processed_at);

        $this->assertSame($initialStock, $rewardB->fresh()->stock);

        $freshMemberPoints = (int) $memberB->pointTransactions()->latest('posted_at')->latest('created_at')->value('balance_after');
        $this->assertSame($initialPoints, $freshMemberPoints);

        $this->assertSame($initialPointTxCount, PointTransaction::query()->count());
        $this->assertSame($initialRefundCount, PointTransaction::query()->where('transaction_type', 'REFUNDED')->count());
        $this->assertSame($initialNotificationCount, DB::table('notifications')->count());
    }

    /**
     * Route Model Binding Regression:
     * Even when foreign redemption UUID is valid, model exists, and actor has legitimate same-org
     * functional permission, implicit binding cannot hydrate or grant access to foreign entity.
     */
    public function test_route_model_binding_cannot_bypass_organization_scoping_for_valid_foreign_uuid(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberB = $this->createMember($orgB);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        // Attempt detail
        $this->actingAs($staffA)
            ->get('/cooperative/redemptions/'.$redemptionB->id)
            ->assertNotFound();

        // Attempt status transition to PROCESSING
        $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionB->id.'/status', [
                'status' => 'PROCESSING',
            ])
            ->assertNotFound();

        $this->assertSame('PENDING', $redemptionB->fresh()->status);
    }

    /**
     * Same-Org Positive Control:
     * Staff A can view and update redemptions belonging to Organization A.
     */
    public function test_same_organization_staff_can_view_and_update_redemption(): void
    {
        [$orgA] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 1000,
            'balance_before' => 0,
            'balance_after' => 1000,
            'description' => 'Saldo awal',
        ]);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'stock' => 5,
            'points_required' => 300,
        ]);

        $redemptionA = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
            'points_used' => 300,
            'quantity' => 1,
        ]);

        // View detail
        $this->actingAs($staffA)
            ->get('/cooperative/redemptions/'.$redemptionA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Redemptions/Show')
                ->where('redemption.id', $redemptionA->id)
                ->where('redemption.status', 'PENDING')
            );

        // Update status to PROCESSING
        $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionA->id.'/status', [
                'status' => 'PROCESSING',
                'notes' => 'Sedang disiapkan',
            ])
            ->assertRedirect();

        $this->assertSame('PROCESSING', $redemptionA->fresh()->status);

        // Update status to CANCELLED (triggers refund and stock restoration)
        $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionA->id.'/status', [
                'status' => 'CANCELLED',
                'notes' => 'Dibatalkan dan di-refund',
            ])
            ->assertRedirect();

        $this->assertSame('CANCELLED', $redemptionA->fresh()->status);
        $this->assertSame(6, $rewardA->fresh()->stock);

        $this->assertDatabaseHas('point_transactions', [
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'REFUNDED',
            'points' => 300,
            'source_type' => RewardRedemption::class,
            'source_id' => $redemptionA->id,
        ]);
    }

    /**
     * Global Visibility Positive Control:
     * Global staff holding view_cooperative_all AND manage_cooperative_redemption
     * can view and update redemptions across organizations.
     */
    public function test_global_staff_with_view_cooperative_all_can_view_and_update_foreign_redemption(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalStaff = User::factory()->create([
            'organization_id' => $orgA->id,
            'email_verified_at' => now(),
        ]);
        $globalStaff->givePermissionTo([
            PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value,
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        ]);

        $memberB = $this->createMember($orgB);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        // Global staff can view foreign redemption
        $this->actingAs($globalStaff)
            ->get('/cooperative/redemptions/'.$redemptionB->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Redemptions/Show')
                ->where('redemption.id', $redemptionB->id)
            );

        // Global staff can update foreign redemption
        $this->actingAs($globalStaff)
            ->put('/cooperative/redemptions/'.$redemptionB->id.'/status', [
                'status' => 'PROCESSING',
                'notes' => 'Diproses oleh admin pusat',
            ])
            ->assertRedirect();

        $this->assertSame('PROCESSING', $redemptionB->fresh()->status);
    }

    /**
     * Critical Negative Control:
     * Actor with view_cooperative_all = YES, but manage_cooperative_redemption = NO
     * is DENIED mutation. Global visibility does not grant mutation authority.
     */
    public function test_global_actor_without_functional_permission_is_denied_mutation(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $globalReadOnly = User::factory()->create([
            'organization_id' => $orgA->id,
            'email_verified_at' => now(),
        ]);
        $globalReadOnly->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        ]);

        $memberB = $this->createMember($orgB);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($globalReadOnly)
            ->put('/cooperative/redemptions/'.$redemptionB->id.'/status', [
                'status' => 'CANCELLED',
            ])
            ->assertForbidden();

        $this->assertSame('PENDING', $redemptionB->fresh()->status);
    }

    /**
     * Critical Negative Control:
     * Actor with valid functional permission, but NO global visibility, targeting foreign org
     * is DENIED (404 Not Found) with zero side effects.
     */
    public function test_functional_permission_without_global_visibility_denies_foreign_redemption(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberB = $this->createMember($orgB);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionB->id.'/status', [
                'status' => 'SHIPPED',
            ])
            ->assertNotFound();

        $this->assertSame('PENDING', $redemptionB->fresh()->status);
    }

    /**
     * Null Organization:
     * Actor with organization_id = null and no global permission FAILS CLOSED.
     */
    public function test_null_organization_actor_fails_closed(): void
    {
        [$orgA] = $this->createOrganizations();

        $nullOrgUser = User::factory()->create([
            'organization_id' => null,
            'email_verified_at' => now(),
        ]);
        $nullOrgUser->givePermissionTo(PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value);

        $memberA = $this->createMember($orgA);
        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);
        $redemptionA = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);

        // List fails closed
        $this->actingAs($nullOrgUser)
            ->get('/cooperative/redemptions')
            ->assertForbidden();

        // Detail fails closed
        $this->actingAs($nullOrgUser)
            ->get('/cooperative/redemptions/'.$redemptionA->id)
            ->assertForbidden();

        // Mutation fails closed
        $this->actingAs($nullOrgUser)
            ->put('/cooperative/redemptions/'.$redemptionA->id.'/status', [
                'status' => 'PROCESSING',
            ])
            ->assertForbidden();

        $this->assertSame('PENDING', $redemptionA->fresh()->status);
    }

    /**
     * Rule G: Client cannot forge organization_id in UpdateRedemptionStatusRequest.
     */
    public function test_client_cannot_forge_organization_id_in_update_status_request(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $staffA = $this->createStaff($orgA);
        $memberA = $this->createMember($orgA);
        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);

        $redemptionA = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($staffA)
            ->put('/cooperative/redemptions/'.$redemptionA->id.'/status', [
                'status' => 'PROCESSING',
                'organization_id' => $orgB->id,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame('PENDING', $redemptionA->fresh()->status);
    }

    /**
     * Phase 2: Member self-service API is strictly self-scoped.
     * Member A sees own redemptions and cannot view Member B redemptions.
     */
    public function test_member_self_service_api_is_strictly_self_scoped(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        $rewardA = Reward::factory()->create(['organization_id' => $orgA->id]);
        $rewardB = Reward::factory()->create(['organization_id' => $orgB->id]);

        $redemptionA = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'reward_id' => $rewardA->id,
            'status' => 'PENDING',
        ]);
        $redemptionB = RewardRedemption::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'reward_id' => $rewardB->id,
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($memberA->user, ['member:read']);

        $response = $this->getJson('/api/v1/member/reward-redemptions')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $redemptionA->id);
    }

    /**
     * Phase 2: Member self-service redeem strictly binds to the authenticated member.
     */
    public function test_member_self_service_api_redeem_binds_to_authenticated_member(): void
    {
        [$orgA] = $this->createOrganizations();

        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 2000,
            'balance_before' => 0,
            'balance_after' => 2000,
            'description' => 'Saldo awal poin',
        ]);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        Sanctum::actingAs($memberA->user, ['member:write']);

        $response = $this->postJson('/api/v1/rewards/'.$rewardA->id.'/redeem', [
            'quantity' => 1,
            'delivery_address' => 'Jl. Test Anggota',
        ])->assertStatus(201);

        $createdId = $response->json('data.id');
        $redemption = RewardRedemption::query()->findOrFail($createdId);

        $this->assertSame($memberA->id, $redemption->cooperative_member_id);
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
     * Helper to create a staff member with manage_cooperative_redemption (without view_cooperative_all).
     */
    private function createStaff(Organization $organization): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo(PermissionEnum::COOPERATIVE_REDEMPTION_MANAGE->value);

        return $staff;
    }

    /**
     * Helper to create a cooperative member with associated user.
     */
    private function createMember(?Organization $organization = null, int $points = 0): CooperativeMember
    {
        $user = User::factory()->create([
            'organization_id' => $organization?->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization?->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        if ($points > 0) {
            PointTransaction::factory()->create([
                'cooperative_member_id' => $member->id,
                'transaction_type' => 'EARNED',
                'points' => $points,
                'balance_before' => 0,
                'balance_after' => $points,
            ]);
        }

        return $member;
    }

    /**
     * Helper to create a staff member with manage_cooperative_rewards (without view_cooperative_all).
     */
    private function createRewardStaff(Organization $organization): User
    {
        $staff = User::factory()->create([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
        ]);
        $staff->givePermissionTo(PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value);

        return $staff;
    }

    // ==========================================
    // R1 NEW SCENARIOS (PHASE 10: 1 to 20)
    // ==========================================

    /**
     * Scenario 1: Member A API catalog excludes Reward B.
     */
    public function test_member_api_catalog_excludes_foreign_organization_rewards(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Reward A Org A',
            'is_active' => true,
        ]);
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Reward B Org B',
            'is_active' => true,
        ]);

        Sanctum::actingAs($memberA->user, ['member:read']);

        $response = $this->getJson('/api/v1/rewards')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($rewardA->id, $ids);
        $this->assertNotContains($rewardB->id, $ids);
    }

    /**
     * Scenario 2: Member A web catalog excludes Reward B.
     */
    public function test_member_web_catalog_excludes_foreign_organization_rewards(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Reward A Org A',
            'is_active' => true,
        ]);
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Reward B Org B',
            'is_active' => true,
        ]);

        $this->actingAs($memberA->user)
            ->get('/member/rewards')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Rewards')
                ->has('rewards.data', 1)
                ->where('rewards.data.0.id', $rewardA->id)
            );
    }

    /**
     * Scenario 3, 4, 5, 6, 7, 8: Member A API cannot redeem foreign Reward B.
     * Foreign API redemption leaves Reward B stock unchanged, Member A points unchanged,
     * creates no RewardRedemption, creates no REDEEMED transaction, creates no notification/outbox side effect.
     */
    public function test_member_api_cannot_redeem_foreign_reward_and_preserves_all_state(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 2000,
            'balance_before' => 0,
            'balance_after' => 2000,
        ]);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        $initialStockB = $rewardB->stock;
        $initialBalanceA = 2000;
        $initialRedemptionCount = RewardRedemption::query()->count();
        $initialPointTxCount = PointTransaction::query()->count();
        $initialNotificationCount = DB::table('notifications')->count();

        Sanctum::actingAs($memberA->user, ['member:write']);

        $this->postJson('/api/v1/rewards/'.$rewardB->id.'/redeem', [
            'quantity' => 1,
            'delivery_address' => 'Jl. Serang No. 1',
        ])->assertNotFound();

        $this->assertSame($initialStockB, $rewardB->fresh()->stock);
        $this->assertSame($initialBalanceA, (int) $memberA->pointTransactions()->latest('id')->value('balance_after'));
        $this->assertSame($initialRedemptionCount, RewardRedemption::query()->count());
        $this->assertSame($initialPointTxCount, PointTransaction::query()->count());
        $this->assertSame(0, PointTransaction::query()->where('transaction_type', 'REDEEMED')->count());
        $this->assertSame($initialNotificationCount, DB::table('notifications')->count());
    }

    /**
     * Scenario 9: Member A web cannot redeem Reward B.
     */
    public function test_member_web_cannot_redeem_foreign_reward_returns_404(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 2000,
            'balance_before' => 0,
            'balance_after' => 2000,
        ]);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($memberA->user)
            ->post('/member/rewards/'.$rewardB->id.'/redeem', [
                'quantity' => 1,
                'delivery_address' => 'Jl. Serang No. 1',
            ])
            ->assertNotFound();

        $this->assertSame(10, $rewardB->fresh()->stock);
        $this->assertSame(0, RewardRedemption::query()->where('reward_id', $rewardB->id)->count());
    }

    /**
     * Scenario 10: Direct PointService redeem(Member A, Reward B) fails closed with AuthorizationException.
     */
    public function test_direct_point_service_redeem_with_mismatched_organizations_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 2000,
            'balance_before' => 0,
            'balance_after' => 2000,
        ]);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        $pointService = app(PointService::class);

        $initialRedemptionCount = RewardRedemption::query()->count();
        $initialPointTxCount = PointTransaction::query()->count();
        $initialNotificationCount = DB::table('notifications')->count();

        $this->expectException(AuthorizationException::class);

        try {
            $pointService->redeem(
                member: $memberA,
                reward: $rewardB,
                quantity: 1,
                deliveryAddress: 'Jl. Bypass No. 99',
            );
        } finally {
            $this->assertSame(10, $rewardB->fresh()->stock);
            $this->assertSame(2000, (int) $memberA->pointTransactions()->latest('id')->value('balance_after'));
            $this->assertSame($initialRedemptionCount, RewardRedemption::query()->count());
            $this->assertSame($initialPointTxCount, PointTransaction::query()->count());
            $this->assertSame($initialNotificationCount, DB::table('notifications')->count());
        }
    }

    /**
     * Scenario 11, 12, 13: Same-org API and web redemptions succeed, and matching tenant ownership is verified.
     */
    public function test_same_org_api_and_web_redemption_succeed_and_verify_matching_ownership(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 3000,
            'balance_before' => 0,
            'balance_after' => 3000,
        ]);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'points_required' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        // 1. Same-org API redemption succeeds
        Sanctum::actingAs($memberA->user, ['member:write']);
        $apiResponse = $this->postJson('/api/v1/rewards/'.$rewardA->id.'/redeem', [
            'quantity' => 1,
            'delivery_address' => 'Jl. Mawar No. 1',
        ])->assertStatus(201);

        $apiRedemption = RewardRedemption::query()->findOrFail($apiResponse->json('data.id'));
        $this->assertSame($memberA->organization_id, $apiRedemption->member->organization_id);
        $this->assertSame($memberA->organization_id, $apiRedemption->reward->organization_id);
        $this->assertSame(9, $rewardA->fresh()->stock);

        // 2. Same-org Web redemption succeeds
        $this->actingAs($memberA->user)
            ->post('/member/rewards/'.$rewardA->id.'/redeem', [
                'quantity' => 2,
                'delivery_address' => 'Jl. Mawar No. 2',
            ])
            ->assertRedirect();

        $webRedemption = RewardRedemption::query()->latest('id')->firstOrFail();
        $this->assertSame($memberA->organization_id, $webRedemption->member->organization_id);
        $this->assertSame($memberA->organization_id, $webRedemption->reward->organization_id);
        $this->assertSame(7, $rewardA->fresh()->stock);
    }

    /**
     * Scenario 14: Org A staff reward list excludes Reward B.
     */
    public function test_staff_reward_list_excludes_foreign_organization_rewards(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createRewardStaff($orgA);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Reward A Org A',
        ]);
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Reward B Org B',
        ]);

        $this->actingAs($staffA)
            ->get('/cooperative/rewards')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Rewards/Index')
                ->has('rewards.data', 1)
                ->where('rewards.data.0.id', $rewardA->id)
            );
    }

    /**
     * Scenario 15: Org A reward manager cannot update Reward B (returns 404).
     */
    public function test_reward_manager_cannot_update_foreign_reward_returns_404(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createRewardStaff($orgA);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Original Reward B',
            'category' => 'BARANG',
            'points_required' => 400,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($staffA)
            ->put('/cooperative/rewards/'.$rewardB->id, [
                'name' => 'Hacked Reward B',
                'category' => 'BARANG',
                'points_required' => 10,
                'stock' => 999,
                'is_active' => true,
            ])
            ->assertNotFound();

        $this->assertSame('Original Reward B', $rewardB->fresh()->name);
        $this->assertSame(400, $rewardB->fresh()->points_required);
    }

    /**
     * Scenario 16: Org A reward manager cannot delete Reward B (returns 404).
     */
    public function test_reward_manager_cannot_delete_foreign_reward_returns_404(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createRewardStaff($orgA);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Indestructible Reward B',
        ]);

        $this->actingAs($staffA)
            ->delete('/cooperative/rewards/'.$rewardB->id)
            ->assertNotFound();

        $this->assertModelExists($rewardB);
    }

    /**
     * Scenario 17: Unit actor cannot create Reward assigned to foreign organization.
     */
    public function test_unit_actor_cannot_create_reward_for_foreign_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createRewardStaff($orgA);

        $this->actingAs($staffA)
            ->post('/cooperative/rewards', [
                'organization_id' => $orgB->id,
                'name' => 'Illicit Org B Reward',
                'category' => 'BARANG',
                'points_required' => 500,
                'stock' => 20,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame(0, Reward::query()->where('name', 'Illicit Org B Reward')->count());
        $this->assertSame(0, Reward::query()->where('organization_id', $orgB->id)->count());
    }

    /**
     * Scenario 18: Unit actor cannot transfer Reward ownership to foreign organization.
     */
    public function test_unit_actor_cannot_transfer_reward_ownership_to_foreign_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $staffA = $this->createRewardStaff($orgA);

        $rewardA = Reward::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Reward Org A',
            'category' => 'BARANG',
            'points_required' => 200,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($staffA)
            ->put('/cooperative/rewards/'.$rewardA->id, [
                'organization_id' => $orgB->id,
                'name' => 'Transferred Reward',
                'category' => 'BARANG',
                'points_required' => 200,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame($orgA->id, $rewardA->fresh()->organization_id);
    }

    /**
     * Scenario 19: Null-org non-global actor fails closed.
     */
    public function test_null_organization_actor_fails_closed_on_reward_management(): void
    {
        $nullOrgUser = User::factory()->create([
            'organization_id' => null,
            'email_verified_at' => now(),
        ]);
        $nullOrgUser->givePermissionTo(PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value);

        $reward = Reward::factory()->create();

        $this->actingAs($nullOrgUser)->get('/cooperative/rewards')->assertForbidden();
        $this->actingAs($nullOrgUser)->put('/cooperative/rewards/'.$reward->id, [
            'name' => 'Forbidden Update',
            'category' => 'BARANG',
            'points_required' => 100,
            'stock' => 1,
            'is_active' => true,
        ])->assertForbidden();
        $this->actingAs($nullOrgUser)->delete('/cooperative/rewards/'.$reward->id)->assertForbidden();
    }

    /**
     * Scenario 20: Global actor requires BOTH global visibility and functional reward-management permission.
     */
    public function test_global_actor_requires_both_global_visibility_and_functional_reward_management_permission(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        // Actor 1: view_cooperative_all ONLY (no manage_cooperative_rewards) -> DENIED
        $globalReadOnly = User::factory()->create([
            'organization_id' => $orgA->id,
            'email_verified_at' => now(),
        ]);
        $globalReadOnly->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
        ]);

        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Foreign Reward B',
            'category' => 'BARANG',
            'points_required' => 300,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($globalReadOnly)
            ->put('/cooperative/rewards/'.$rewardB->id, [
                'name' => 'Attempted Update',
                'category' => 'BARANG',
                'points_required' => 300,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertSame('Foreign Reward B', $rewardB->fresh()->name);

        // Actor 2: BOTH view_cooperative_all AND manage_cooperative_rewards -> SUCCEEDS
        $globalAdmin = User::factory()->create([
            'organization_id' => $orgA->id,
            'email_verified_at' => now(),
        ]);
        $globalAdmin->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
        ]);

        $this->actingAs($globalAdmin)
            ->put('/cooperative/rewards/'.$rewardB->id, [
                'name' => 'Authorized Global Update',
                'category' => 'BARANG',
                'points_required' => 350,
                'stock' => 8,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('Authorized Global Update', $rewardB->fresh()->name);
        $this->assertSame(350, $rewardB->fresh()->points_required);
    }

    /**
     * R2 Blocker A: globalPermissionFor resolves only from OrganizationScopeService::GLOBAL_PERMISSIONS.
     * Model-level method overrides (e.g. organizationScopeGlobalPermission) must not be recognized.
     */
    public function test_global_permission_for_model_resolves_only_from_centralized_registry(): void
    {
        $scopeService = app(\App\Services\Authorization\OrganizationScopeService::class);

        // 1. Reward model is explicitly mapped in GLOBAL_PERMISSIONS
        $this->assertSame('view_cooperative_all', $scopeService->globalPermissionFor(new Reward));
        $this->assertArrayHasKey(Reward::class, $scopeService->registeredGlobalPermissions());
        $this->assertSame('view_cooperative_all', $scopeService->registeredGlobalPermissions()[Reward::class]);

        // 2. A model defining organizationScopeGlobalPermission() MUST NOT be recognized unless in GLOBAL_PERMISSIONS
        $dummyModel = new class extends \Illuminate\Database\Eloquent\Model implements \App\Contracts\OrganizationScopedModel
        {
            public function organizationScopePath(): string
            {
                return 'organization_id';
            }

            public function organizationScopeGlobalPermission(): ?string
            {
                return 'malicious_global_permission';
            }
        };

        $this->assertNull(
            $scopeService->globalPermissionFor($dummyModel),
            'Model-level organizationScopeGlobalPermission() must not be recognized; permissions must resolve only from centralized registry.'
        );

        // 3. User with malicious_global_permission does NOT receive global visibility for this model
        [$orgA] = $this->createOrganizations();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $visibility = $scopeService->visibilityFor($user, $scopeService->globalPermissionFor($dummyModel));
        $this->assertFalse($visibility->global, 'User without a registered global permission must not receive global visibility.');
    }

    /**
     * R2 Blocker B: Tenant check must precede syncPosPoints().
     * An unsynced POS points source must not have any mutations or notifications executed.
     */
    public function test_unsynced_pos_points_side_effects_prevented_when_foreign_reward_redeemed(): void
    {
        Notification::fake();
        [$orgA, $orgB] = $this->createOrganizations();

        $memberA = $this->createMember($orgA, 0);
        $rewardB = Reward::factory()->create([
            'organization_id' => $orgB->id,
            'points_required' => 50,
            'stock' => 10,
            'is_active' => true,
        ]);

        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-SEC-001',
            'cooperative_member_id' => $memberA->id,
            'cashier_id' => $memberA->user_id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'gross_profit' => 40000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);

        $posPoint = PosMemberPoint::query()->create([
            'cooperative_member_id' => $memberA->id,
            'pos_transaction_id' => $transaction->id,
            'year' => (int) now()->format('Y'),
            'profit_amount' => 100000.00,
            'points' => 0,
            'posted_at' => now()->toDateString(),
        ]);

        $pointService = app(PointService::class);

        $beforePointsCount = PointTransaction::count();
        $beforeEarnedCount = PointTransaction::where('transaction_type', 'EARNED')->count();
        $beforeRedeemedCount = PointTransaction::where('transaction_type', 'REDEEMED')->count();
        $beforeRedemptionCount = RewardRedemption::count();

        $this->assertSame(0, $beforePointsCount);
        $this->assertSame(0, $beforeEarnedCount);
        $this->assertSame(0, $beforeRedeemedCount);
        $this->assertSame(0, $beforeRedemptionCount);
        $this->assertSame(0, (int) $posPoint->points);
        $this->assertSame(10, $rewardB->stock);

        try {
            $pointService->redeem($memberA, $rewardB, 1);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('The reward does not belong to the member organization.', $e->getMessage());
        }

        $this->assertSame(0, (int) $posPoint->fresh()->points, 'POS point record must remain unsynced');
        $this->assertSame(0, PointTransaction::count(), 'No PointTransaction must be created');
        $this->assertSame(0, PointTransaction::where('transaction_type', 'EARNED')->count(), 'No EARNED PointTransaction created');
        $this->assertSame(0, (int) $memberA->pointTransactions()->count(), 'No PointTransaction created for member');
        $this->assertSame(0, RewardRedemption::count(), 'No RewardRedemption created');
        $this->assertSame(10, $rewardB->fresh()->stock, 'Reward stock must remain unchanged');
        Notification::assertNothingSent();
    }

    /**
     * R2 Blocker C: Null-org member cannot browse catalog or redeem rewards via API (fails closed).
     */
    public function test_null_organization_member_cannot_view_or_redeem_rewards_via_api(): void
    {
        Notification::fake();
        [$orgA] = $this->createOrganizations();

        $memberNullOrg = $this->createMember($orgA, 500);
        $memberNullOrg->organization_id = null;
        $userNullOrg = $memberNullOrg->user;
        $userNullOrg->setRelation('cooperativeMember', $memberNullOrg);
        Sanctum::actingAs($userNullOrg, ['member:write']);

        $rewardNullOrg = Reward::factory()->create([
            'organization_id' => null,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        // A. GET /api/v1/rewards fails closed (403)
        $this->getJson('/api/v1/rewards')
            ->assertForbidden();

        // B. POST /api/v1/rewards/{reward}/redeem fails closed (403)
        $this->postJson('/api/v1/rewards/'.$rewardNullOrg->id.'/redeem', ['quantity' => 1])
            ->assertForbidden();

        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, PointTransaction::where('transaction_type', 'REDEEMED')->count());
        $this->assertSame(10, $rewardNullOrg->fresh()->stock);
        $this->assertSame(500, (int) $memberNullOrg->pointTransactions()->latest('id')->value('balance_after'));
        Notification::assertNothingSent();
    }

    /**
     * R2 Blocker C: Normal member cannot redeem a null-organization reward via API (fails closed).
     */
    public function test_normal_member_cannot_redeem_null_organization_reward_via_api(): void
    {
        Notification::fake();
        [$orgA] = $this->createOrganizations();

        $memberA = $this->createMember($orgA, 500);
        Sanctum::actingAs($memberA->user, ['member:write']);

        $rewardNullOrg = Reward::factory()->create([
            'organization_id' => null,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        // C. POST /api/v1/rewards/{reward}/redeem fails closed (404)
        $this->postJson('/api/v1/rewards/'.$rewardNullOrg->id.'/redeem', ['quantity' => 1])
            ->assertNotFound();

        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, PointTransaction::where('transaction_type', 'REDEEMED')->count());
        $this->assertSame(10, $rewardNullOrg->fresh()->stock);
        $this->assertSame(500, (int) $memberA->pointTransactions()->latest('id')->value('balance_after'));
        Notification::assertNothingSent();
    }

    /**
     * R2 Blocker C: Null-org member cannot browse catalog or redeem rewards via Web Portal.
     */
    public function test_null_organization_member_cannot_view_or_redeem_rewards_via_web_portal(): void
    {
        Notification::fake();
        [$orgA] = $this->createOrganizations();

        $memberNullOrg = $this->createMember($orgA, 500);
        $memberNullOrg->organization_id = null;
        $userNullOrg = $memberNullOrg->user;
        $userNullOrg->setRelation('cooperativeMember', $memberNullOrg);

        $rewardNullOrg = Reward::factory()->create([
            'organization_id' => null,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        // GET member reward catalog fails closed (403)
        $this->actingAs($userNullOrg)
            ->get('/member/rewards')
            ->assertForbidden();

        // POST member reward redemption fails closed (403)
        $this->actingAs($userNullOrg)
            ->post('/member/rewards/'.$rewardNullOrg->id.'/redeem', ['quantity' => 1])
            ->assertForbidden();

        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, PointTransaction::where('transaction_type', 'REDEEMED')->count());
        $this->assertSame(10, $rewardNullOrg->fresh()->stock);
        $this->assertSame(500, (int) $memberNullOrg->pointTransactions()->latest('id')->value('balance_after'));
        Notification::assertNothingSent();
    }

    /**
     * R2 Blocker C: Normal member cannot redeem a null-org reward via Web Portal.
     */
    public function test_normal_member_cannot_redeem_null_organization_reward_via_web_portal(): void
    {
        Notification::fake();
        [$orgA] = $this->createOrganizations();

        $memberA = $this->createMember($orgA, 500);

        $rewardNullOrg = Reward::factory()->create([
            'organization_id' => null,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($memberA->user)
            ->post('/member/rewards/'.$rewardNullOrg->id.'/redeem', ['quantity' => 1])
            ->assertNotFound();

        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, PointTransaction::where('transaction_type', 'REDEEMED')->count());
        $this->assertSame(10, $rewardNullOrg->fresh()->stock);
        $this->assertSame(500, (int) $memberA->pointTransactions()->latest('id')->value('balance_after'));
        Notification::assertNothingSent();
    }

    /**
     * R2: PointService must reject Member A / valid Org A + Reward organization_id = null
     * and Member organization_id = null + Reward organization_id = null (fails closed).
     */
    public function test_point_service_rejects_null_organization_member_and_null_organization_reward(): void
    {
        Notification::fake();
        [$orgA] = $this->createOrganizations();

        $memberA = $this->createMember($orgA, 500);
        $memberNullOrg = $this->createMember($orgA, 500);
        $memberNullOrg->organization_id = null;

        $rewardNullOrg = Reward::factory()->create([
            'organization_id' => null,
            'points_required' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $pointService = app(PointService::class);

        // Case 1: Member A / Org A + Reward organization_id = null
        try {
            $pointService->redeem($memberA, $rewardNullOrg, 1);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('The reward does not belong to the member organization.', $e->getMessage());
        }

        // Case 2: Member organization_id = null + Reward organization_id = null
        try {
            $pointService->redeem($memberNullOrg, $rewardNullOrg, 1);
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('The reward does not belong to the member organization.', $e->getMessage());
        }

        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, PointTransaction::where('transaction_type', 'REDEEMED')->count());
        $this->assertSame(10, $rewardNullOrg->fresh()->stock);
        $this->assertSame(500, (int) $memberA->pointTransactions()->latest('id')->value('balance_after'));
        $this->assertSame(500, (int) $memberNullOrg->pointTransactions()->latest('id')->value('balance_after'));
        Notification::assertNothingSent();
    }

    /**
     * R2: Global admin with organization_id = null cannot create orphan reward when omitting target organization_id.
     */
    public function test_global_admin_with_null_org_cannot_create_orphan_reward_without_target_org(): void
    {
        $globalNullOrgUser = User::factory()->create([
            'organization_id' => null,
            'email_verified_at' => now(),
        ]);
        $globalNullOrgUser->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
        ]);

        $beforeCount = Reward::count();

        $this->actingAs($globalNullOrgUser)
            ->post('/cooperative/rewards', [
                'name' => 'Orphan Reward Attempt',
                'category' => 'BARANG',
                'points_required' => 100,
                'stock' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame($beforeCount, Reward::count());
        $this->assertDatabaseMissing('rewards', ['name' => 'Orphan Reward Attempt']);
    }

    /**
     * R2: Global admin with organization_id = null can create reward when explicitly specifying target organization_id.
     */
    public function test_global_admin_with_null_org_can_create_reward_when_specifying_target_org(): void
    {
        [$orgA] = $this->createOrganizations();

        $globalNullOrgUser = User::factory()->create([
            'organization_id' => null,
            'email_verified_at' => now(),
        ]);
        $globalNullOrgUser->givePermissionTo([
            PermissionEnum::COOPERATIVE_VIEW_ALL->value,
            PermissionEnum::COOPERATIVE_REWARDS_MANAGE->value,
        ]);

        $this->actingAs($globalNullOrgUser)
            ->post('/cooperative/rewards', [
                'organization_id' => $orgA->id,
                'name' => 'Targeted Global Reward',
                'category' => 'BARANG',
                'points_required' => 150,
                'stock' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rewards', [
            'name' => 'Targeted Global Reward',
            'organization_id' => $orgA->id,
        ]);
    }
}
