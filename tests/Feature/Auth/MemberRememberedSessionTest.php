<?php

namespace Tests\Feature\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberAuthSession;
use App\Models\MemberSessionRenewal;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\MemberSessionService;
use App\Services\Cooperative\MemberAccessRevocationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/** Only SQLite-memory fixture accounts. No QA services, credentials or financial writes. */
class MemberRememberedSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed(RolePermissionSeeder::class);
        config(['member_sessions.enabled' => true]);
        $this->travelTo(now()->startOfSecond());
    }

    private function member(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        return $user;
    }

    /** @return array<string, mixed> */
    private function login(User $user, bool $remember = true): array
    {
        app('auth')->forgetGuards();
        $response = $this->flushHeaders()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password', 'app' => 'member', 'device_name' => 'DEMO Emulator', 'device_id' => 'DEMO-only-device', 'session_protocol' => 2, 'remember_device' => $remember]);
        $response->assertOk()->assertJsonPath('session.protocol', 2);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        return $response->json();
    }

    private function sessionRequest(string $token): \Illuminate\Testing\TestResponse
    {
        app('auth')->forgetGuards();

        return $this->flushHeaders()->withToken($token)->getJson('/api/auth/session');
    }

    public function test_disabled_default_preserves_legacy_login_and_rejects_v2_without_issuing_credentials(): void
    {
        $user = $this->member();
        config(['member_sessions.enabled' => false]);
        $this->getJson('/api/auth/session-capabilities')->assertOk()->assertJsonPath('session_protocol', null);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password', 'app' => 'member'])->assertOk()->assertJsonMissingPath('session');
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password', 'app' => 'member', 'session_protocol' => 2])->assertStatus(503);
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame(0, MemberAuthSession::query()->count());
    }

    public function test_remembered_bundle_uses_proposed_deadlines_and_only_member_abilities(): void
    {
        $bundle = $this->login($this->member());
        $this->assertSame(now()->addMinutes(15)->toISOString(), $bundle['session']['access_expires_at']);
        $this->assertSame(now()->addDays(30)->toISOString(), $bundle['session']['idle_expires_at']);
        $this->assertSame(now()->addDays(90)->toISOString(), $bundle['session']['absolute_expires_at']);
        $this->assertEqualsCanonicalizing(['profile:read', 'member:read', 'member:write'], $bundle['abilities']);
        $this->assertTrue($bundle['session']['remembered']);
        $this->sessionRequest($bundle['token'])->assertOk();
        $this->sessionRequest($bundle['session']['renewal_token'])->assertUnauthorized();
        $this->assertNotContains($bundle['session']['renewal_token'], MemberSessionRenewal::query()->pluck('secret_hash')->all());
    }

    public function test_normal_mode_has_separate_short_idle_and_absolute_deadlines(): void
    {
        $bundle = $this->login($this->member(), false);
        $this->assertFalse($bundle['session']['remembered']);
        $this->assertSame(now()->addMinutes(30)->toISOString(), $bundle['session']['idle_expires_at']);
        $this->assertSame(now()->addHours(12)->toISOString(), $bundle['session']['absolute_expires_at']);
    }

    public function test_access_expires_but_separate_renewal_still_works_without_access_bearer(): void
    {
        $bundle = $this->login($this->member());
        $this->travel(15)->minutes();
        $this->sessionRequest($bundle['token'])->assertUnauthorized();
        app('auth')->forgetGuards();
        $new = $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $bundle['session']['renewal_token']])->assertOk()->json();
        $this->assertNotSame($bundle['token'], $new['token']);
        $this->assertNotSame($bundle['session']['renewal_token'], $new['session']['renewal_token']);
        $this->assertSame($bundle['session']['id'], $new['session']['id']);
        $this->assertSame($bundle['session']['absolute_expires_at'], $new['session']['absolute_expires_at']);
        $this->sessionRequest($new['token'])->assertOk();
    }

    public function test_activity_does_not_extend_access_and_renewal_cannot_extend_absolute_deadline(): void
    {
        $bundle = $this->login($this->member());
        $this->travel(14)->minutes();
        $this->sessionRequest($bundle['token'])->assertOk();
        $this->travel(1)->minutes();
        $this->sessionRequest($bundle['token'])->assertUnauthorized();
        $id = $bundle['session']['id'];
        MemberAuthSession::query()->whereKey($id)->update(['absolute_expires_at' => now()->addMinutes(5)]);
        $new = app(MemberSessionService::class)->renew($bundle['session']['renewal_token']);
        $this->assertSame(now()->addMinutes(5)->toISOString(), $new['session']['idle_expires_at']);
        $this->assertSame(now()->addMinutes(5)->toISOString(), $new['session']['access_expires_at']);
    }

    public function test_idle_and_absolute_boundaries_fail_closed(): void
    {
        foreach (['idle_expires_at', 'absolute_expires_at'] as $field) {
            $bundle = $this->login($this->member());
            MemberAuthSession::query()->whereKey($bundle['session']['id'])->update([$field => now()]);
            $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $bundle['session']['renewal_token']])->assertUnauthorized();
            $this->assertNotNull(MemberAuthSession::query()->find($bundle['session']['id'])->revoked_at);
            $this->sessionRequest($bundle['token'])->assertUnauthorized();
        }
    }

    public function test_reuse_commits_family_revocation_and_invalidates_successor_and_access(): void
    {
        $bundle = $this->login($this->member());
        $new = app(MemberSessionService::class)->renew($bundle['session']['renewal_token']);
        $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $bundle['session']['renewal_token']])->assertUnauthorized();
        $this->assertSame('renewal_reuse', MemberAuthSession::query()->find($bundle['session']['id'])->revocation_reason);
        $this->sessionRequest($new['token'])->assertUnauthorized();
        $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $new['session']['renewal_token']])->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::query()->where('member_session_id', $bundle['session']['id'])->count());
    }

    public function test_bad_secret_does_not_revoke_a_legitimate_family(): void
    {
        $bundle = $this->login($this->member());
        $bad = substr($bundle['session']['renewal_token'], 0, 37).str_repeat('0', 64);
        $this->postJson('/api/auth/session/renew', ['renewal_token' => $bad])->assertUnauthorized();
        $this->assertNull(MemberAuthSession::query()->find($bundle['session']['id'])->revoked_at);
        $this->sessionRequest($bundle['token'])->assertOk();
    }

    public function test_legacy_rotate_cannot_remove_the_family_binding(): void
    {
        $bundle = $this->login($this->member());
        app('auth')->forgetGuards();
        $this->flushHeaders()->withToken($bundle['token'])->postJson('/api/token/rotate')->assertStatus(409);
        $this->assertSame(1, PersonalAccessToken::query()->where('member_session_id', $bundle['session']['id'])->count());
    }

    public function test_device_ownership_and_resource_payload_never_expose_secrets(): void
    {
        $a = $this->login($this->member());
        $b = $this->login($this->member());
        app('auth')->forgetGuards();
        $list = $this->flushHeaders()->withToken($a['token'])->getJson('/api/auth/sessions')->assertOk();
        $this->assertCount(1, $list->json('data'));
        $list->assertJsonPath('data.0.current', true);
        foreach (['renewal_token', 'secret_hash', 'security_digest', $a['session']['renewal_token'], $b['session']['id']] as $secret) {
            $this->assertStringNotContainsString($secret, $list->getContent());
        }
        app('auth')->forgetGuards();
        $this->flushHeaders()->withToken($a['token'])->deleteJson('/api/auth/sessions/'.$b['session']['id'])->assertNotFound();
        $this->sessionRequest($b['token'])->assertOk();
    }

    public function test_revoke_another_owned_device_is_idempotent_and_preserves_current_device(): void
    {
        $user = $this->member();
        $a = $this->login($user);
        $b = $this->login($user);
        foreach ([1, 2] as $_) {
            app('auth')->forgetGuards();
            $this->flushHeaders()->withToken($a['token'])->deleteJson('/api/auth/sessions/'.$b['session']['id'])->assertOk();
        }
        $this->sessionRequest($b['token'])->assertUnauthorized();
        $this->sessionRequest($a['token'])->assertOk();
        $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $b['session']['renewal_token']])->assertUnauthorized();
    }

    public function test_logout_revokes_the_whole_family_and_logout_all_preserves_original_count(): void
    {
        $user = $this->member();
        $a = $this->login($user);
        $b = $this->login($user);
        app('auth')->forgetGuards();
        $this->flushHeaders()->withToken($a['token'])->postJson('/api/auth/logout')->assertOk();
        $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $a['session']['renewal_token']])->assertUnauthorized();
        $legacy = $user->createToken('DEMO legacy', ['profile:read']);
        $this->assertSame(2, app(MemberAccessRevocationService::class)->revokeAccountWide($user, 'account_security', $user));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotNull(MemberAuthSession::query()->find($b['session']['id'])->revoked_at);
    }

    public function test_password_reset_revokes_new_families_without_silently_changing_other_app_policy(): void
    {
        $user = $this->member();
        $bundle = $this->login($user);
        $legacy = $user->createToken('DEMO legacy app', ['ess:read'])->accessToken;
        app(ResetUserPassword::class)->reset($user, ['password' => 'New-Fixture-Password-123!', 'password_confirmation' => 'New-Fixture-Password-123!']);
        $this->assertTrue(Hash::check('New-Fixture-Password-123!', $user->fresh()->password));
        $this->assertNotNull(MemberAuthSession::query()->find($bundle['session']['id'])->revoked_at);
        $this->assertTrue(PersonalAccessToken::query()->whereKey($legacy->id)->exists());
    }

    public function test_bulk_password_tenant_role_and_lifecycle_changes_cannot_restore_old_access_or_renewal(): void
    {
        foreach (['password', 'tenant', 'role', 'lifecycle'] as $change) {
            $user = $this->member();
            $bundle = $this->login($user);
            match ($change) {
                'password' => DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('New-Fixture-Password-123!')]),
                'tenant' => DB::table('users')->where('id', $user->id)->update(['organization_id' => Organization::factory()->create()->id]),
                'role' => DB::table('model_has_roles')->where('model_id', $user->id)->where('model_type', User::class)->delete(),
                'lifecycle' => DB::table('cooperative_members')->where('user_id', $user->id)->update(['status' => 'RESIGNED', 'validation_status' => 'RESIGNED']),
            };
            $this->sessionRequest($bundle['token'])->assertUnauthorized();
            $this->flushHeaders()->postJson('/api/auth/session/renew', ['renewal_token' => $bundle['session']['renewal_token']])->assertUnauthorized();
        }
    }

    public function test_audit_failure_rolls_back_issuance_and_rotation_does_not_produce_partial_grants(): void
    {
        $user = $this->member();
        $this->mock(AuditLogService::class)->shouldReceive('log')->andThrow(new \RuntimeException('DEMO audit unavailable'));
        try {
            app(MemberSessionService::class)->issue($user, \App\Enums\TokenApp::MEMBER, 'DEMO', null, true);
            $this->fail('Expected fixture audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('DEMO audit unavailable', $e->getMessage());
        }
        $this->assertSame(0, MemberAuthSession::query()->count());
        $this->assertSame(0, MemberSessionRenewal::query()->count());
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_existing_global_expiry_is_not_relaxed_and_new_app_profile_cannot_be_escalated(): void
    {
        config(['sanctum.expiration' => 1]);
        $user = $this->member();
        $bundle = $this->login($user);
        $this->assertSame(now()->addMinute()->toISOString(), $bundle['session']['access_expires_at']);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password', 'app' => 'admin', 'session_protocol' => 2, 'remember_device' => true])->assertForbidden();
        $this->assertSame(1, MemberAuthSession::query()->count());
    }

    public function test_renewal_secrets_and_security_digest_are_absent_from_audit_payloads(): void
    {
        $bundle = $this->login($this->member());
        app(MemberSessionService::class)->renew($bundle['session']['renewal_token']);
        $audit = AuditLog::query()->where('module', 'auth.session')->get()->toJson();
        foreach ([$bundle['token'], $bundle['session']['renewal_token'], MemberAuthSession::query()->first()->security_digest] as $secret) {
            $this->assertStringNotContainsString($secret, $audit);
        }
    }

    public function test_admin_revocation_preserves_actor_correlation_and_token_count(): void
    {
        $owner = $this->member();
        $bundle = $this->login($owner);
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $context = \App\Support\AuditContext::forActor($admin, correlationId: '10000000-0000-4000-8000-000000000077');
        $count = app(MemberAccessRevocationService::class)->revokeFor($owner->cooperativeMember, 'deactivated', $admin, $context);
        $this->assertSame(1, $count);
        $event = AuditLog::query()->where('action', 'member.session.revoked')->firstOrFail();
        $this->assertSame($admin->id, $event->user_id);
        $this->assertSame($context->correlationId, $event->correlation_id);
        $this->assertSame($owner->id, $event->new_values['affected_user_id']);
        $this->sessionRequest($bundle['token'])->assertUnauthorized();
    }

    public function test_password_update_revokes_only_new_member_session_families(): void
    {
        $user = $this->member();
        $bundle = $this->login($user);
        $this->actingAs($user)->put(route('user-password.update'), ['current_password' => 'password', 'password' => 'New-Fixture-Password-123!', 'password_confirmation' => 'New-Fixture-Password-123!'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(Hash::check('New-Fixture-Password-123!', $user->fresh()->password));
        $this->assertNotNull(MemberAuthSession::query()->find($bundle['session']['id'])->revoked_at);
    }

    public function test_rotation_audit_failure_rolls_back_consumption_and_successor_then_allows_safe_retry(): void
    {
        $bundle = $this->login($this->member());
        $service = app(MemberSessionService::class);
        $mock = \Mockery::mock(AuditLogService::class);
        $mock->shouldReceive('log')->andThrow(new \RuntimeException('DEMO audit unavailable'));
        $this->app->instance(AuditLogService::class, $mock);
        $this->app->forgetInstance(MemberSessionService::class);
        try {
            app(MemberSessionService::class)->renew($bundle['session']['renewal_token']);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('DEMO audit unavailable', $e->getMessage());
        }
        $this->assertNull(MemberSessionRenewal::query()->firstOrFail()->consumed_at);
        $this->assertSame(1, MemberSessionRenewal::query()->count());
        $this->assertSame(1, PersonalAccessToken::query()->where('member_session_id', $bundle['session']['id'])->count());
        $next = $service->renew($bundle['session']['renewal_token']);
        $this->assertNotSame($bundle['token'], $next['token']);
    }

    public function test_additive_migration_rollback_preserves_legacy_but_never_unbinds_new_credentials(): void
    {
        $user = $this->member();
        $legacy = $user->createToken('legacy-fixture', ['member:read']);
        $bundle = $this->login($user);
        $migration = require database_path('migrations/2026_10_25_035433_create_member_auth_sessions.php');
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('member_auth_sessions'));
        $this->assertTrue(PersonalAccessToken::whereKey($legacy->accessToken->id)->exists());
        $this->assertSame(1, PersonalAccessToken::query()->count());
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('member_auth_sessions'));
        $this->assertSame(0, MemberAuthSession::query()->count());
    }

    public function test_protocol_openapi_preserves_public_renewal_and_owner_scoped_private_device_contract(): void
    {
        $spec = app(\App\Services\OpenApi\OpenApiGenerator::class)->generate();
        $this->assertSame([], $spec['paths']['/api/auth/session-capabilities']['get']['security']);
        $renew = $spec['paths']['/api/auth/session/renew']['post'];
        $this->assertSame([], $renew['security']);
        $this->assertSame(['renewal_token'], $renew['requestBody']['content']['application/json']['schema']['required']);
        $this->assertSame(['member:read'], $spec['paths']['/api/auth/sessions']['get']['x-required-abilities']);
        $delete = $spec['paths']['/api/auth/sessions/{sessionId}']['delete'];
        $this->assertArrayNotHasKey('requestBody', $delete);
        $this->assertSame(['member:write'], $delete['x-required-abilities']);
        $this->assertArrayNotHasKey('renewal_token', $spec['components']['schemas']['MemberDeviceSession']['properties']);
    }
}
