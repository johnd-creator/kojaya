<?php

namespace Tests\Feature\Security;

use App\Enums\TokenApp;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenIssuanceService;
use App\Services\Cooperative\MemberAccessRevocationService;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class TokenAppMetadataTest extends TestCase
{
    public function test_new_issuance_persists_explicit_application_metadata(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');

        $token = app(TokenIssuanceService::class)->issue(
            $user,
            TokenApp::ADMIN,
            'Admin device',
            'device-1',
        );

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
            'token_app' => 'admin',
            'token_version' => 'v1',
            'device_id' => 'device-1',
        ]);
        $this->assertNotNull($token->accessToken->fresh()->issued_at);
        $this->assertNotContains('*', $token->accessToken->abilities);
    }

    public function test_member_token_revocation_does_not_revoke_ess_or_admin_tokens(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $memberToken = $user->createToken('member', ['profile:read', 'member:read']);
        $memberToken->accessToken->forceFill(['token_app' => 'member'])->save();
        $essToken = $user->createToken('ess', ['profile:read', 'ess:read']);
        $essToken->accessToken->forceFill(['token_app' => 'ess'])->save();
        $adminToken = $user->createToken('admin', ['profile:read']);
        $adminToken->accessToken->forceFill(['token_app' => 'admin'])->save();

        app(MemberAccessRevocationService::class)->revokeFor($member, 'member lifecycle');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $memberToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $essToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $adminToken->accessToken->id]);
    }

    public function test_account_wide_revoke_is_explicit_and_revokes_every_token(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->createToken('member', ['profile:read']);
        $user->createToken('ess', ['profile:read']);

        app(MemberAccessRevocationService::class)->revokeAccountWide($user, 'account security action');

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_member_revocation_unions_explicit_and_exact_legacy_member_profiles(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $explicit = $user->createToken('explicit-member', ['member:read']);
        $explicit->accessToken->forceFill(['token_app' => 'member'])->save();
        $legacy = $user->createToken('legacy-member', ['profile:read', 'member:read', 'member:write']);
        $ess = $user->createToken('legacy-ess', ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read']);
        $technician = $user->createToken('legacy-technician', ['profile:read', 'work-orders:read', 'work-orders:write', 'work-orders:review']);
        $unsafe = $user->createToken('unsafe', ['*']);

        app(MemberAccessRevocationService::class)->revokeFor($member, 'deactivated');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $explicit->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $legacy->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ess->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $technician->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $unsafe->accessToken->id]);
    }

    public function test_member_revocation_is_independent_of_cutover_phase(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        foreach (['instrument', 'rotate', 'deprecate', 'remove'] as $phase) {
            config()->set('security.ability_cutover_phase', $phase);
            $token = $user->createToken('legacy-'.$phase, ['profile:read', 'member:read', 'member:write']);
            app(MemberAccessRevocationService::class)->revokeFor($member, 'deactivated');

            $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        }
    }
}
