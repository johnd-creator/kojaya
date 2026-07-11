<?php

namespace Tests\Feature\Member;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberActiveStatusGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_member_can_access_financial_endpoints(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/savings/summary')->assertOk();
        $this->getJson('/api/v1/member/dues/invoices')->assertOk();
        $this->getJson('/api/v1/member/loans')->assertOk();
        $this->getJson('/api/v1/member/bills')->assertOk();
    }

    public function test_pending_member_is_blocked_from_financial_endpoints(): void
    {
        [$user] = $this->nonActiveMember('pending');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
        $this->getJson('/api/v1/member/dues/invoices')->assertForbidden();
        $this->postJson('/api/v1/member/savings/withdraw', [])->assertForbidden();
    }

    public function test_revision_member_is_blocked_from_financial_endpoints(): void
    {
        [$user] = $this->nonActiveMember('revision');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    public function test_rejected_member_is_blocked_from_financial_endpoints(): void
    {
        [$user] = $this->nonActiveMember('rejected');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    public function test_inactive_member_is_blocked_from_financial_endpoints(): void
    {
        [$user] = $this->nonActiveMember('inactive');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    public function test_resigned_member_is_blocked_from_financial_endpoints(): void
    {
        [$user] = $this->nonActiveMember('resigned');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    public function test_financial_endpoint_returns_member_not_active_error_code(): void
    {
        [$user] = $this->nonActiveMember('pending');

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/savings/summary')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'MEMBER_NOT_ACTIVE');
    }

    public function test_non_active_member_can_access_onboarding_safe_endpoints(): void
    {
        [$user] = $this->nonActiveMember('pending');

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/dashboard')->assertOk();
        $this->getJson('/api/v1/member/onboarding/status')->assertOk();
        $this->getJson('/api/v1/member/status-journey')->assertOk();
        $this->getJson('/api/v1/member/profile')->assertOk();
        $this->getJson('/api/v1/member/resignation')->assertOk();
        $this->getJson('/api/v1/member/notifications')->assertOk();
    }

    public function test_non_active_member_can_update_profile_for_onboarding(): void
    {
        [$user, $member] = $this->nonActiveMember('revision');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->putJson('/api/v1/member/profile', [
            'name' => 'Revised Name',
            'email' => $user->email,
            'phone' => '08123456789',
            'address' => $member->address ?? 'New Address',
        ])->assertOk();
    }

    public function test_non_active_member_cannot_submit_resignation(): void
    {
        [$user] = $this->nonActiveMember('inactive');

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Pindah kota',
            'effective_date' => now()->addMonth()->toDateString(),
        ])->assertForbidden();
    }

    public function test_user_without_cooperative_member_gets_403_on_member_endpoints(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function nonActiveMember(string $state): array
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $base = [
            'user_id' => $user->id,
            'organization_id' => Organization::factory()->create()->id,
        ];

        $member = match ($state) {
            'pending' => CooperativeMember::factory()->pending()->create($base),
            'revision' => CooperativeMember::factory()->pending()->create([
                ...$base,
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
            ]),
            'rejected' => CooperativeMember::factory()->pending()->create([
                ...$base,
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
            ]),
            'inactive' => CooperativeMember::factory()->active()->create([
                ...$base,
                'status' => CooperativeMember::VALIDATION_INACTIVE,
            ]),
            'resigned' => CooperativeMember::factory()->active()->create([
                ...$base,
                'status' => CooperativeMember::VALIDATION_RESIGNED,
            ]),
        };

        return [$user, $member];
    }
}
