<?php

namespace Tests\Feature\Member;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\MemberAccessRevocationService;
use App\Services\Cooperative\MemberStatusTransitionService;
use App\Services\Cooperative\MemberValidationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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
            'status' => CooperativeMember::VALIDATION_PENDING,
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
            'status' => CooperativeMember::VALIDATION_PENDING,
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

        app(CooperativeMemberService::class)->resign($member, $admin);

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

    public function test_mandatory_audit_failure_rolls_back_member_token_revocation(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);
        $tokenId = $user->tokens()->firstOrFail()->id;
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->once()
            ->andThrow(new \RuntimeException('simulated mandatory audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(MemberAccessRevocationService::class)->revokeFor($member, 'deactivated', $admin);
            $this->fail('Expected mandatory audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated mandatory audit failure', $exception->getMessage());
        }

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.access.revoked']);
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

    public function test_mandatory_revocation_audit_failure_during_lifecycle_rolls_back_everything(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);
        $tokenId = $user->tokens()->firstOrFail()->id;
        $user->createToken('ess-mobile', ['ess:read']);
        $user->createToken('technician-mobile', ['work-orders:read']);

        $originalStatus = $member->status;
        $originalValidationStatus = $member->validation_status;

        $audit = Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.access.revoked')
            ->andThrow(new \RuntimeException('simulated mandatory revocation audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin);
            $this->fail('Expected mandatory revocation audit failure to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated mandatory revocation audit failure', $exception->getMessage());
        }

        $member->refresh();
        $this->assertSame($originalStatus, $member->status, 'Member status must roll back on revocation audit failure.');
        $this->assertSame($originalValidationStatus, $member->validation_status, 'Validation status must roll back.');
        $this->assertTrue($user->fresh()->hasRole('Anggota'), 'Anggota role must not be removed on rollback.');
        $this->assertSame(3, $user->fresh()->tokens()->count());
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.status.transitioned']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.access.revoked']);
    }

    public function test_delete_access_audit_failure_rolls_back_member_and_persisted_lifecycle_audit(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);
        $tokenId = $user->tokens()->firstOrFail()->id;
        $originalStatus = $member->status;
        $originalValidationStatus = $member->validation_status;

        // Narrow fake: only the mandatory member.access.revoked audit fails.
        // The member.access.deleted audit and all other audits run through the
        // real implementation, so the rollback proof exercises the real token
        // deletion and revocation paths, not a mocked service boundary.
        $audit = Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.access.revoked')
            ->andThrow(new \RuntimeException('simulated mandatory revocation audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(MemberStatusTransitionService::class)->deleteAccess($member->refresh(), $admin, 'security review');
            $this->fail('Expected mandatory revocation audit failure to propagate during delete access.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated mandatory revocation audit failure', $exception->getMessage());
        }

        $member->refresh();
        $this->assertSame($originalStatus, $member->status, 'Member status must be unchanged by delete access.');
        $this->assertSame($originalValidationStatus, $member->validation_status, 'Validation status must be unchanged.');
        $this->assertTrue($user->fresh()->hasRole('Anggota'), 'Anggota role must be restored on rollback.');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.access.deleted']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.access.revoked']);
    }

    public function test_lifecycle_happy_path_revokes_only_member_tokens_preserving_ess_and_technician(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        $user->createToken('ess-mobile', ['ess:read', 'ess:write']);
        $user->createToken('technician-mobile', ['work-orders:read', 'work-orders:write']);

        $this->assertSame(3, $user->tokens()->count());

        $result = app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin);

        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $result->validation_status);
        $this->assertSame(2, $user->tokens()->count(), 'Only member token should be revoked; ESS and technician preserved.');
        $this->assertTrue($user->tokens()->where('name', 'ess-mobile')->exists());
        $this->assertTrue($user->tokens()->where('name', 'technician-mobile')->exists());
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'mobile-test']);
    }

    public function test_lifecycle_and_revocation_audits_share_one_audit_context_even_without_request_correlation_header(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        // Drive the operation through the HTTP deactivate endpoint so the
        // AuditContext is built from the request, with no X-Correlation-ID.
        $this->actingAs($admin)
            ->withHeaders([])
            ->post(route('cooperative.members.deactivate', $member), [
                'reason' => 'Lifecycle correlation proof',
            ])
            ->assertSessionHas('success');

        $transitioned = \App\Models\AuditLog::query()
            ->where('action', 'member.status.transitioned')
            ->where('subject_id', $member->id)
            ->sole();
        $revoked = \App\Models\AuditLog::query()
            ->where('action', 'member.access.revoked')
            ->where('subject_id', $member->id)
            ->sole();

        $this->assertNotEmpty($transitioned->correlation_id, 'Lifecycle audit must carry a correlation ID.');
        $this->assertSame($transitioned->correlation_id, $revoked->correlation_id, 'Lifecycle and revocation audits must share one correlation ID for a single operation.');
        $this->assertSame((string) $admin->id, (string) $transitioned->user_id);
        $this->assertSame((string) $transitioned->user_id, (string) $revoked->user_id, 'Actor identity must be identical across lifecycle audits.');
        $this->assertSame((string) $admin->organization_id, (string) $transitioned->organization_id);
        $this->assertSame((string) $transitioned->organization_id, (string) $revoked->organization_id, 'Organization context must be identical across lifecycle audits.');
        $this->assertSame($transitioned->actor_roles, $revoked->actor_roles, 'Actor roles must be identical across lifecycle audits.');
    }

    public function test_lifecycle_and_revocation_audits_share_one_audit_context_for_domain_service_calls(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin, 'Domain context proof');

        $transitioned = \App\Models\AuditLog::query()
            ->where('action', 'member.status.transitioned')
            ->where('subject_id', $member->id)
            ->sole();
        $revoked = \App\Models\AuditLog::query()
            ->where('action', 'member.access.revoked')
            ->where('subject_id', $member->id)
            ->sole();

        $this->assertSame($transitioned->correlation_id, $revoked->correlation_id, 'Domain-driven lifecycle and revocation audits must share one correlation ID.');
        $this->assertSame((string) $transitioned->user_id, (string) $revoked->user_id);
        $this->assertSame((string) $transitioned->organization_id, (string) $revoked->organization_id);
    }

    public function test_delete_access_and_revocation_audits_share_one_audit_context(): void
    {
        [$user, $member] = $this->activeMemberWithToken();
        $admin = $this->adminUser($member->organization_id);

        app(MemberStatusTransitionService::class)->deleteAccess($member->refresh(), $admin, 'delete access correlation proof');

        $deleted = \App\Models\AuditLog::query()
            ->where('action', 'member.access.deleted')
            ->where('subject_id', $member->id)
            ->sole();
        $revoked = \App\Models\AuditLog::query()
            ->where('action', 'member.access.revoked')
            ->where('subject_id', $member->id)
            ->sole();

        $this->assertSame($deleted->correlation_id, $revoked->correlation_id, 'deleteAccess lifecycle and revocation audits must share one correlation ID.');
        $this->assertSame((string) $deleted->user_id, (string) $revoked->user_id);
        $this->assertSame((string) $deleted->organization_id, (string) $revoked->organization_id);
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

        $user->createToken('mobile-test', ['profile:read', 'member:read', 'member:write']);

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
