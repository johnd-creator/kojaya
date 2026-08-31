<?php

namespace Tests\Feature\Security;

use App\Enums\ApiErrorCode;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrictApiAbilityEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $adminUser;

    private User $memberUser;

    private CooperativeMember $activeMember;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();

        $this->adminUser = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->adminUser->assignRole('Admin Koperasi');

        $this->memberUser = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->memberUser->assignRole('Anggota');
        $this->activeMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUser->id,
        ]);
    }

    public function test_anonymous_requests_receive_401_unauthorized(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/auth/session')->assertUnauthorized();
        $this->getJson('/api/v1/member/profile')->assertUnauthorized();
        $this->getJson('/api/v1/member/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/member/dues/invoices')->assertUnauthorized();
        $this->getJson('/api/monitoring/health')->assertUnauthorized();
        $this->getJson('/api/v1/pos/products')->assertUnauthorized();
        $this->getJson('/api/ess/dashboard')->assertUnauthorized();
        $this->getJson('/api/technician/work-orders')->assertUnauthorized();
        $this->getJson('/api/v1/members')->assertUnauthorized();
        $this->postJson('/api/token/rotate')->assertUnauthorized();
    }

    public function test_token_required_routes_reject_session_only_authentication(): void
    {
        $this->actingAs($this->memberUser, 'web');

        // Member domain routes
        $this->getJson('/api/v1/member/profile')->assertForbidden();
        $this->getJson('/api/v1/member/dashboard')->assertForbidden();
        $this->getJson('/api/v1/member/dues/invoices')->assertForbidden();

        // POS domain routes
        $this->getJson('/api/v1/pos/products')->assertForbidden();

        // Reports domain routes
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/reports/sales')->assertForbidden();

        // ESS domain routes
        $this->getJson('/api/ess/dashboard')->assertForbidden();

        // Technician domain routes
        $this->getJson('/api/technician/work-orders')->assertForbidden();

        // Push token registration
        $this->postJson('/api/devices/push-token', ['token' => 'dummy', 'platform' => 'android'])->assertForbidden();
    }

    public function test_cooperative_admin_routes_reject_session_only_authentication(): void
    {
        $this->actingAs($this->adminUser, 'web');

        // Cooperative admin domain routes
        $this->getJson('/api/v1/members')->assertForbidden();
        $this->getJson('/api/v1/dues/invoices')->assertForbidden();
        $this->getJson('/api/v1/savings/ledger')->assertForbidden();
        $this->getJson('/api/v1/loans')->assertForbidden();
        $this->getJson('/api/v1/notifications')->assertForbidden();
    }

    public function test_token_required_routes_succeed_with_correct_scoped_pat(): void
    {
        // Member scoped token
        $memberToken = $this->memberUser->createToken('member-app', ['member:read', 'member:write']);
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken->plainTextToken);

        $this->getJson('/api/v1/member/profile')->assertOk();
        $this->getJson('/api/v1/member/dashboard')->assertOk();
        $this->getJson('/api/v1/member/dues/invoices')->assertOk();

        // Reports scoped token
        $reportToken = $this->adminUser->createToken('reports-app', ['reports:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($reportToken->plainTextToken);

        $this->getJson('/api/monitoring/health')->assertOk();

        // POS scoped token
        $posToken = $this->adminUser->createToken('pos-app', ['pos:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($posToken->plainTextToken);

        $this->getJson('/api/v1/pos/products')->assertOk();

        // Cooperative scoped token
        $coopToken = $this->adminUser->createToken('coop-app', ['cooperative.member.read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($coopToken->plainTextToken);

        $this->getJson('/api/v1/members')->assertOk();
    }

    public function test_token_required_routes_reject_wrong_ability_pat(): void
    {
        // Member token accessing POS and Reports
        $memberToken = $this->memberUser->createToken('member-app', ['member:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken->plainTextToken);

        $this->getJson('/api/v1/pos/products')->assertForbidden();
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/members')->assertForbidden();

        // POS token accessing Member domain
        $posToken = $this->adminUser->createToken('pos-app', ['pos:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($posToken->plainTextToken);

        $this->getJson('/api/v1/member/profile')->assertForbidden();
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/members')->assertForbidden();

        // Cooperative dues token accessing cooperative members
        $duesToken = $this->adminUser->createToken('dues-app', ['cooperative.dues.read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($duesToken->plainTextToken);

        $this->getJson('/api/v1/members')->assertForbidden();
    }

    public function test_wildcard_pat_is_rejected_from_all_scoped_abilities(): void
    {
        $wildcardToken = $this->memberUser->createToken('wildcard', ['*']);
        $this->app['auth']->forgetGuards();
        $this->withToken($wildcardToken->plainTextToken);

        $this->getJson('/api/v1/member/profile')->assertForbidden();
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/members')->assertForbidden();
        $this->getJson('/api/user')->assertForbidden();
        $this->getJson('/api/auth/session')->assertForbidden();
    }

    public function test_session_safe_identity_endpoints_support_web_session(): void
    {
        $this->actingAs($this->memberUser, 'web');

        // GET /api/user with web session
        $response = $this->getJson('/api/user');
        $response->assertOk()
            ->assertJsonPath('id', $this->memberUser->id)
            ->assertJsonPath('email', $this->memberUser->email);

        // GET /api/auth/session with web session
        $sessionResponse = $this->getJson('/api/auth/session');
        $sessionResponse->assertOk()
            ->assertJsonPath('user.id', $this->memberUser->id)
            ->assertJsonPath('token.name', null)
            ->assertJsonPath('token.abilities', null);
    }

    public function test_session_safe_identity_endpoints_support_valid_bearer_pat(): void
    {
        $profileToken = $this->memberUser->createToken('mobile-device', ['profile:read']);
        $profileToken->accessToken->forceFill([
            'token_app' => 'member',
            'token_version' => 'v1',
        ])->save();

        $this->app['auth']->forgetGuards();
        $this->withToken($profileToken->plainTextToken);

        $response = $this->getJson('/api/user');
        $response->assertOk()
            ->assertJsonPath('id', $this->memberUser->id);

        $sessionResponse = $this->getJson('/api/auth/session');
        $sessionResponse->assertOk()
            ->assertJsonPath('user.id', $this->memberUser->id)
            ->assertJsonPath('token.name', 'mobile-device')
            ->assertJsonPath('token.abilities', ['profile:read'])
            ->assertJsonPath('token.token_app', 'member')
            ->assertJsonPath('token.token_version', 'v1');
    }

    public function test_session_safe_identity_endpoints_reject_bearer_token_missing_profile_read(): void
    {
        $restrictedToken = $this->memberUser->createToken('restricted', ['reports:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($restrictedToken->plainTextToken);

        $this->getJson('/api/user')->assertForbidden();
        $this->getJson('/api/auth/session')->assertForbidden();
    }

    public function test_token_rotation_rejects_session_only_and_rotates_valid_pat(): void
    {
        // 1. Session-only rotation attempt must be rejected (400 Bad Request)
        $this->actingAs($this->memberUser, 'web');
        $this->postJson('/api/token/rotate', ['app' => 'member'])
            ->assertStatus(400);

        // 2. Valid PAT rotation succeeds
        $pat = $this->memberUser->createToken('device-old', ['profile:read', 'member:read', 'member:write']);
        $pat->accessToken->forceFill([
            'token_app' => 'member',
            'token_version' => 'v1',
            'device_id' => 'device-xyz',
        ])->save();

        $this->app['auth']->forgetGuards();
        $this->withToken($pat->plainTextToken);

        $rotateResponse = $this->postJson('/api/token/rotate', [
            'app' => 'member',
            'device_name' => 'device-refreshed',
        ]);

        $rotateResponse->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('token_app', 'member')
            ->assertJsonPath('token_version', 'v1')
            ->assertJsonStructure(['token', 'abilities', 'expires_at']);

        // Old token deleted
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $pat->accessToken->id]);

        // New token functions on member endpoints
        $newPlainText = $rotateResponse->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($newPlainText);

        $this->getJson('/api/v1/member/profile')->assertOk();
    }

    public function test_member_active_gate_remains_enforced_for_inactive_member_tokens(): void
    {
        // Member with PENDING status
        $pendingUser = User::factory()->create(['organization_id' => $this->organization->id]);
        $pendingUser->assignRole('Anggota');
        $pendingMember = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $pendingUser->id,
        ]);

        $token = $pendingUser->createToken('pending-device', ['profile:read', 'member:read', 'member:write']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken);

        // Onboarding routes are allowed for pending member
        $this->getJson('/api/v1/member/profile')->assertOk();
        $this->getJson('/api/v1/member/dashboard')->assertOk();

        // Active-only transactional routes are rejected by member.api.active
        $this->getJson('/api/v1/member/dues/invoices')
            ->assertForbidden()
            ->assertJsonPath('error_code', ApiErrorCode::MemberNotActive->value);

        $this->getJson('/api/v1/member/savings/ledger')
            ->assertForbidden()
            ->assertJsonPath('error_code', ApiErrorCode::MemberNotActive->value);
    }
}
