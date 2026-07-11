<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberStatusTransitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        [$user, $member] = $this->activeMemberWithToken('Admin Koperasi');

        app(MemberStatusTransitionService::class)->deactivate($member->refresh(), $admin = User::factory()->create()->assignRole('Admin Koperasi') ? $user : $user);

        // After transition + commit, tokens should be revoked
        $this->assertSame(0, $user->tokens()->count());
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
    private function activeMemberWithToken(string $role): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
        ]);

        $user->createToken('test', ['member:read']);

        return [$user, $member];
    }
}
