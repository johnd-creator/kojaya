<?php

namespace Tests\Feature\Member;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\MemberAccessRevocationService;
use App\Services\Cooperative\MemberValidationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLifecycleTokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_revision_request_revokes_member_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $this->assertTokenWorks($user);

        // Member must be in pending review to request revision
        $member->forceFill([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ])->save();

        app(MemberValidationService::class)->requestRevision($member->refresh(), $admin, 'Missing documents');

        $this->assertTokenRevoked($user);
    }

    public function test_reject_revokes_member_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $this->assertTokenWorks($user);

        $member->forceFill([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ])->save();

        app(MemberValidationService::class)->reject($member->refresh(), $admin, 'Application rejected');

        $this->assertTokenRevoked($user);
    }

    public function test_deactivate_revokes_member_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $this->assertTokenWorks($user);

        app(MemberAccessRevocationService::class)->revokeFor($member->refresh(), 'deactivated', $admin);

        $this->assertTokenRevoked($user);
    }

    public function test_resign_revokes_member_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $this->assertTokenWorks($user);

        app(CooperativeMemberService::class)->resign($member);

        $this->assertTokenRevoked($user);
    }

    public function test_revocation_creates_audit_log(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        app(MemberAccessRevocationService::class)->revokeFor($member, 'deactivated', $admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.access.revoked',
            'subject_type' => CooperativeMember::class,
            'subject_id' => $member->id,
        ]);
    }

    public function test_revocation_preserves_ess_and_technician_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);
        $user->createToken('ess-mobile', ['ess:read', 'ess:write']);
        $user->createToken('technician-mobile', ['work-orders:read', 'work-orders:write']);

        app(MemberAccessRevocationService::class)->revokeFor($member, 'deactivated', $admin);

        $this->assertSame(2, $user->tokens()->count());
        $this->assertTrue($user->tokens()->where('name', 'ess-mobile')->exists());
        $this->assertTrue($user->tokens()->where('name', 'technician-mobile')->exists());
    }

    public function test_revocation_with_no_tokens_returns_zero(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $admin = $this->adminUser($member->organization_id);

        $count = app(MemberAccessRevocationService::class)->revokeFor($member, 'test', $admin);

        $this->assertSame(0, $count);
    }

    public function test_revocation_with_no_user_returns_zero(): void
    {
        $member = CooperativeMember::factory()->active()->create(['user_id' => null]);

        $count = app(MemberAccessRevocationService::class)->revokeFor($member, 'test');

        $this->assertSame(0, $count);
    }

    public function test_deactivate_endpoint_revokes_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $this->assertTokenWorks($user);

        $this->actingAs($admin)
            ->post(route('cooperative.members.deactivate', $member))
            ->assertSessionHas('success');

        $this->assertTokenRevoked($user);
    }

    public function test_resign_endpoint_revokes_tokens(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $pengurus = User::factory()->create(['organization_id' => $member->organization_id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $this->assertTokenWorks($user);

        $this->actingAs($pengurus)
            ->post(route('cooperative.members.resign', $member))
            ->assertSessionHas('success');

        $this->assertTokenRevoked($user);
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function activeMemberWithToken(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $user->createToken('mobile-test', ['member:read', 'member:write']);

        return [$user, $member];
    }

    private function adminUser(string $organizationId): User
    {
        $admin = User::factory()->create(['organization_id' => $organizationId]);
        $admin->assignRole('Admin Koperasi');

        return $admin;
    }

    private function assertTokenWorks(User $user): void
    {
        $token = $user->tokens()->first();
        $this->assertNotNull($token, 'Member should have an active token before transition.');

        $this->assertSame(1, $user->tokens()->count(), 'Member should have exactly one token.');
    }

    private function assertTokenRevoked(User $user): void
    {
        $this->assertSame(0, $user->tokens()->count(), 'All tokens should have been revoked after lifecycle transition.');
    }
}
