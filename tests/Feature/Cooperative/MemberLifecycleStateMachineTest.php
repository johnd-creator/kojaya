<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberStatusTransitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberLifecycleStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // --- Valid transition matrix ---

    public function test_pending_pending_can_be_verified_by_admin(): void
    {
        [$member, $admin] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING, 'Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->verifyByAdmin($member, $admin, 'ok');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $result->validation_status);
    }

    public function test_inactive_revision_can_be_verified_by_admin(): void
    {
        [$member, $admin] = $this->memberWithState('INACTIVE', CooperativeMember::VALIDATION_REVISION, 'Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->verifyByAdmin($member, $admin, 'ok');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $result->validation_status);
    }

    public function test_pending_validation_can_be_approved_final(): void
    {
        [$member, $pengurus] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW, 'Pengurus Koperasi');

        $result = app(MemberStatusTransitionService::class)->approveFinal($member, $pengurus, 'ok');

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $result->validation_status);
        $this->assertSame('ACTIVE', $result->status);
    }

    public function test_pending_validation_can_request_revision(): void
    {
        [$member, $admin] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW, 'Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->requestRevision($member, $admin, 'fix docs');

        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $result->validation_status);
    }

    public function test_pending_validation_can_be_rejected(): void
    {
        [$member, $admin] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW, 'Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->reject($member, $admin, 'no');

        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $result->validation_status);
    }

    public function test_active_can_be_deactivated(): void
    {
        [$member, $admin] = $this->activeMember('Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->deactivate($member, $admin);

        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $result->validation_status);
    }

    public function test_active_can_be_resigned(): void
    {
        [$member, $pengurus] = $this->activeMember('Pengurus Koperasi');

        $result = app(MemberStatusTransitionService::class)->resign($member, $pengurus);

        $this->assertSame(CooperativeMember::VALIDATION_RESIGNED, $result->status);
    }

    public function test_inactive_can_be_reactivated(): void
    {
        [$member, $admin] = $this->memberWithState('INACTIVE', CooperativeMember::VALIDATION_INACTIVE, 'Admin Koperasi');

        $result = app(MemberStatusTransitionService::class)->activate($member, $admin);

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $result->validation_status);
    }

    // --- Invalid transitions ---

    public function test_reject_on_active_member_is_rejected(): void
    {
        [$member, $admin] = $this->activeMember('Admin Koperasi');

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->reject($member, $admin, 'no');
    }

    public function test_approve_final_on_pending_member_is_rejected(): void
    {
        [$member, $pengurus] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING, 'Pengurus Koperasi');

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->approveFinal($member, $pengurus, 'ok');
    }

    public function test_activate_on_resigned_member_is_rejected(): void
    {
        [$member, $admin] = $this->memberWithState('RESIGNED', CooperativeMember::VALIDATION_RESIGNED, 'Admin Koperasi');

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->activate($member, $admin);
    }

    public function test_deactivate_on_pending_member_is_rejected(): void
    {
        [$member, $admin] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING, 'Admin Koperasi');

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->deactivate($member, $admin);
    }

    public function test_verify_on_active_member_is_rejected(): void
    {
        [$member, $admin] = $this->activeMember('Admin Koperasi');

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->verifyByAdmin($member, $admin, 'ok');
    }

    // --- Direct service call doesn't bypass guard ---

    public function test_transition_service_enforces_state_regardless_of_caller(): void
    {
        // Even a system admin calling the service directly gets rejected
        // for invalid transitions.
        $systemAdmin = User::factory()->create();
        $systemAdmin->assignRole('System Admin');

        $member = CooperativeMember::factory()->active()->create();

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->reject($member, $systemAdmin, 'no');
    }

    // --- Actor authorization: Anggota cannot perform admin lifecycle commands ---

    public function test_anggota_cannot_deactivate(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();
        $anggotaActor = User::factory()->create(['organization_id' => $member->organization_id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->deactivate($member, $anggotaActor);
    }

    public function test_anggota_cannot_activate(): void
    {
        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_INACTIVE,
        ]);
        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->activate($member, $anggotaActor);
    }

    public function test_anggota_cannot_approve_final(): void
    {
        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->approveFinal($member, $anggotaActor, 'ok');
    }

    public function test_anggota_cannot_reject(): void
    {
        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->reject($member, $anggotaActor, 'no');
    }

    public function test_anggota_cannot_request_revision(): void
    {
        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->requestRevision($member, $anggotaActor, 'fix');
    }

    public function test_anggota_cannot_delete_access(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();
        $anggotaActor = User::factory()->create(['organization_id' => $member->organization_id]);
        $anggotaActor->assignRole('Anggota');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(MemberStatusTransitionService::class)->deleteAccess($member, $anggotaActor);
    }

    public function test_unauthorized_actor_produces_no_side_effects_on_reject(): void
    {
        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        try {
            app(MemberStatusTransitionService::class)->reject($member, $anggotaActor, 'no');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            // Expected
        }

        $member->refresh();
        $this->assertSame('PENDING', $member->status, 'Status must not change on authz failure.');
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->validation_status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'member.status.transitioned',
            'subject_id' => $member->id,
        ]);
    }

    // --- Generic profile update does not change status ---

    public function test_generic_profile_update_does_not_change_lifecycle_status(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'New Name',
                'name' => 'New Name',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('ACTIVE', $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->validation_status);
    }

    // --- Token revocation after commit ---

    public function test_token_revocation_happens_only_after_commit(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();
        $admin = User::factory()->create(['organization_id' => $member->organization_id]);
        $admin->assignRole('Admin Koperasi');

        $this->assertSame(1, $memberUser->tokens()->count(), 'Member should have one token before transition.');

        app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin);

        $this->assertSame(0, $memberUser->tokens()->count(), 'Tokens must be revoked after committed transition.');
    }

    public function test_token_not_revoked_when_outer_transaction_rolls_back(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();
        $admin = User::factory()->create(['organization_id' => $member->organization_id]);
        $admin->assignRole('Admin Koperasi');

        $this->assertSame(1, $memberUser->tokens()->count());

        try {
            DB::transaction(function () use ($member, $admin): void {
                app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin);

                throw new \RuntimeException('Simulated failure after transition.');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertSame(1, $memberUser->tokens()->count(), 'Tokens must survive when the outer transaction rolls back.');
    }

    public function test_token_revoked_after_commit_when_transition_completes(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();
        $admin = User::factory()->create(['organization_id' => $member->organization_id]);
        $admin->assignRole('Admin Koperasi');

        DB::transaction(function () use ($member, $admin): void {
            app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin);
        });

        $this->assertSame(0, $memberUser->tokens()->count(), 'Tokens must be revoked after transaction commits.');
    }

    public function test_no_side_effects_when_actor_lacks_permission(): void
    {
        [$memberUser, $member] = $this->activeMemberWithToken();

        // User with only Anggota role — no admin permissions
        $anggotaActor = User::factory()->create(['organization_id' => $member->organization_id]);
        $anggotaActor->assignRole('Anggota');

        try {
            app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $anggotaActor);
            $this->fail('Should have thrown AuthorizationException.');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            // Expected
        }

        // No mutation, no role removal, no audit, no token revocation
        $member->refresh();
        $this->assertSame('ACTIVE', $member->status);
        $this->assertSame(1, $memberUser->tokens()->count(), 'Tokens must not be revoked on failed authorization.');
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'member.status.transitioned',
            'subject_id' => $member->id,
        ]);
    }

    // --- Double-submit: second reject on already-rejected member fails cleanly ---

    public function test_double_reject_returns_validation_error(): void
    {
        [$member, $admin] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW, 'Admin Koperasi');

        // First reject succeeds
        app(MemberStatusTransitionService::class)->reject($member->refresh(), $admin, 'no');
        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->refresh()->validation_status);

        // Second reject fails cleanly with ValidationException
        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->reject($member->refresh(), $admin, 'no');
    }

    public function test_double_approve_final_returns_validation_error(): void
    {
        [$member, $pengurus] = $this->memberWithState('PENDING', CooperativeMember::VALIDATION_PENDING_REVIEW, 'Pengurus Koperasi');

        app(MemberStatusTransitionService::class)->approveFinal($member->refresh(), $pengurus, 'ok');
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->refresh()->validation_status);

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->approveFinal($member->refresh(), $pengurus, 'ok');
    }

    // --- deleteAccess method ---

    public function test_delete_access_revokes_tokens_and_role(): void
    {
        [$user, $member] = $this->activeMemberWithToken('Admin Koperasi');
        $admin = User::factory()->create();
        $admin->assignRole('Admin Koperasi');

        $this->assertTrue($user->hasRole('Anggota'));

        app(MemberStatusTransitionService::class)->deleteAccess($member->refresh(), $admin, 'account removed');

        $this->assertSame(0, $user->tokens()->count());
        $this->assertFalse($user->refresh()->hasRole('Anggota'));
    }

    // --- Audit is written on every transition ---

    public function test_every_transition_writes_audit_log(): void
    {
        [$member, $admin] = $this->activeMember('Admin Koperasi');

        app(MemberStatusTransitionService::class)->deactivate($member, $admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.status.transitioned',
            'subject_type' => CooperativeMember::class,
            'subject_id' => $member->id,
        ]);
    }

    /**
     * @return array{0: CooperativeMember, 1: User}
     */
    private function memberWithState(string $status, string $validationStatus, string $role): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole($role);

        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => $status,
            'validation_status' => $validationStatus,
        ]);

        return [$member, $user];
    }

    /**
     * @return array{0: CooperativeMember, 1: User}
     */
    private function activeMember(string $role): array
    {
        return $this->memberWithState('ACTIVE', CooperativeMember::VALIDATION_ACTIVE, $role);
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function activeMemberWithToken(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
        ]);

        $user->createToken('test', ['profile:read', 'member:read', 'member:write']);

        return [$user, $member];
    }
}
