<?php

namespace Tests\Feature\Security;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    private function createMember(Organization $organization): CooperativeMember
    {
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Anggota');

        return CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
