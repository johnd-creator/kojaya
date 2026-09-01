<?php

namespace Tests\Feature\Security;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAbilityCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_case_a_anonymous_requests_are_unauthenticated(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/auth/session')->assertUnauthorized();
        $this->getJson('/api/v1/member/profile')->assertUnauthorized();
        $this->getJson('/api/v1/member/dashboard')->assertUnauthorized();
        $this->getJson('/api/monitoring/health')->assertUnauthorized();
        $this->getJson('/api/v1/members')->assertUnauthorized();
    }

    public function test_case_b_characterize_session_only_authentication_enforced_behavior(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user, 'web');

        // Session-safe identity routes succeed with web session
        $this->getJson('/api/user')->assertOk();
        $this->getJson('/api/auth/session')->assertOk();

        // Token-required application routes reject session-only authentication
        $this->getJson('/api/v1/member/profile')->assertForbidden();
        $this->getJson('/api/v1/member/dashboard')->assertForbidden();
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/members')->assertForbidden();
    }

    public function test_case_c_correct_scoped_personal_access_token_is_authorized(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $profileToken = $user->createToken('profile', ['profile:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($profileToken->plainTextToken);
        $this->getJson('/api/user')->assertOk();

        $memberToken = $user->createToken('member', ['member:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken->plainTextToken);
        $this->getJson('/api/v1/member/profile')->assertOk();

        $reportToken = $user->createToken('reports', ['reports:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($reportToken->plainTextToken);
        $this->getJson('/api/monitoring/health')->assertOk();

        $coopToken = $user->createToken('coop', ['cooperative.member.read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($coopToken->plainTextToken);
        $this->getJson('/api/v1/members')->assertOk();
    }

    public function test_case_d_wrong_ability_token_is_denied(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        // member:read token accessing reports:read endpoint
        $memberToken = $user->createToken('member', ['member:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken->plainTextToken);
        $this->getJson('/api/monitoring/health')->assertForbidden();

        // reports:read token accessing member:read endpoint
        $reportToken = $user->createToken('reports', ['reports:read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($reportToken->plainTextToken);
        $this->getJson('/api/v1/member/profile')->assertForbidden();

        // cooperative.dues.read token accessing cooperative.member.read endpoint
        $duesToken = $user->createToken('dues', ['cooperative.dues.read']);
        $this->app['auth']->forgetGuards();
        $this->withToken($duesToken->plainTextToken);
        $this->getJson('/api/v1/members')->assertForbidden();
    }

    public function test_case_e_token_with_no_required_ability_is_denied(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $emptyToken = $user->createToken('empty', []);
        $this->app['auth']->forgetGuards();
        $this->withToken($emptyToken->plainTextToken);

        $this->getJson('/api/user')->assertForbidden();
        $this->getJson('/api/v1/member/profile')->assertForbidden();
        $this->getJson('/api/monitoring/health')->assertForbidden();
        $this->getJson('/api/v1/members')->assertForbidden();
    }

    public function test_token_rotation_rejects_session_only_and_accepts_valid_pat(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        // Session-only rotation attempt must be rejected (400 Bad Request)
        $this->postJson('/api/token/rotate', ['app' => 'member'])
            ->assertStatus(400);

        // Valid PAT rotation succeeds
        $token = $user->createToken('test-device', ['profile:read', 'member:read', 'member:write']);
        $token->accessToken->forceFill([
            'token_app' => 'member',
            'token_version' => 'v1',
            'device_id' => 'dev-123',
        ])->save();

        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken);

        $response = $this->postJson('/api/token/rotate', [
            'app' => 'member',
            'device_name' => 'new-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'abilities', 'token_app', 'token_version']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_wildcard_pat_behavior_across_cooperative_and_generic_apis(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $wildcard = $user->createToken('wildcard', ['*']);
        $this->withToken($wildcard->plainTextToken);

        // Both EnsureCooperativeAbility and CheckTokenForAnyAbility reject wildcard '*'
        $this->getJson('/api/v1/members')->assertForbidden();
        $this->getJson('/api/v1/member/profile')->assertForbidden();
    }
}
