<?php

namespace Tests\Feature\Auth\Sso;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesTestRsaJwk;
use Tests\TestCase;

class GoogleSsoFlowTest extends TestCase
{
    use CreatesTestRsaJwk;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Role::firstOrCreate(['name' => 'Anggota']);
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        Permission::firstOrCreate(['name' => 'validate_cooperative_member']);

        config()->set('services.google.sso_enabled', true);
        config()->set('services.google.client_id', 'web-client.apps.googleusercontent.com');
        config()->set('services.google.allow_new_member_registration', true);
    }

    public function test_redirect_endpoint_disabled_returns_to_login(): void
    {
        config()->set('services.google.sso_enabled', false);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'));
    }

    public function test_callback_rejects_unverified_email(): void
    {
        $this->mockSocialite(googleId: '123', email: 'unverified@example.com', verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
    }

    public function test_callback_fails_closed_when_email_has_no_canonical_member(): void
    {
        $this->mockSocialite(googleId: '9001', email: 'new-member@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertDatabaseMissing('users', ['email' => 'new-member@example.com']);
        $this->assertDatabaseMissing('cooperative_members', ['email' => 'new-member@example.com']);
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => '9001']);
        $this->assertGuest();
    }

    public function test_callback_logs_in_existing_bound_user(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'returning@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '777',
            'provider_email' => 'returning@example.com',
        ]);
        $this->mockSocialite(googleId: '777', email: 'returning@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '777',
        ]);
    }

    public function test_callback_logs_in_existing_bound_user_even_if_google_email_unverified(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'unverified-returning@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'unverified-777',
            'provider_email' => 'unverified-returning@example.com',
        ]);
        $this->mockSocialite(googleId: 'unverified-777', email: 'unverified-returning@example.com', verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_callback_rejects_unbound_user_when_google_email_unverified(): void
    {
        $member = CooperativeMember::factory()->create([
            'user_id' => null,
            'email' => 'unbound-unverified@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $this->mockSocialite(googleId: 'unbound-unverified-id', email: 'unbound-unverified@example.com', verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'unbound-unverified@example.com']);
    }

    public function test_callback_logs_in_existing_bound_member(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'email' => 'member@example.com',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '4242',
            'provider_email' => 'member@example.com',
        ]);
        $this->mockSocialite(googleId: '4242', email: 'member@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '4242',
        ]);

        $audit = AuditLog::query()->where('action', 'sso.google.login_success')->latest('id')->first();
        $this->assertNotNull($audit);
    }

    public function test_callback_first_time_matches_eligible_canonical_member(): void
    {
        $member = CooperativeMember::factory()->create([
            'user_id' => null,
            'email' => 'unlinked-member@example.com',
            'nama_anggota' => 'Budi Anggota',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $this->mockSocialite(googleId: 'unlinked-member', email: 'unlinked-member@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('member.onboarding'));

        $this->assertAuthenticated();
        $user = User::query()->where('email', 'unlinked-member@example.com')->firstOrFail();
        $this->assertSame('Budi Anggota', $user->name);
        $this->assertTrue($user->hasRole('Anggota'));
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'unlinked-member',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.google_sso_linked',
        ]);
    }

    public function test_callback_blocks_registration_when_disabled(): void
    {
        config()->set('services.google.allow_new_member_registration', false);
        $this->mockSocialite(googleId: 'no-allow', email: 'blocked@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_callback_rejects_email_outside_allowed_hosted_domains(): void
    {
        config()->set('services.google.hosted_domains', ['kojaya.co.id']);
        $this->mockSocialite(googleId: 'domain-denied', email: 'member@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
    }

    public function test_callback_logs_exception_class_when_provider_throws_invalid_state(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();

        $audit = AuditLog::query()->where('action', 'sso.google.login_failed')->latest('id')->first();
        $this->assertNotNull($audit, 'Expected an SSO failure audit log entry.');
        $this->assertSame('callback_failed', $audit->new_values['reason'] ?? null);
        $this->assertStringContainsString('InvalidStateException', $audit->new_values['exception'] ?? '');
        $this->assertSame('(empty message)', $audit->new_values['message'] ?? null);
    }

    public function test_callback_handles_provider_connection_timeout_gracefully(): void
    {
        $psr7Request = new Psr7Request('POST', 'https://oauth2.googleapis.com/token');
        $connectException = new ConnectException('cURL error 28: Operation timed out', $psr7Request);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andThrow($connectException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();

        $audit = AuditLog::query()->where('action', 'sso.google.login_failed')->latest('id')->first();
        $this->assertNotNull($audit, 'Expected an SSO failure audit log entry.');
        $this->assertStringContainsString('ConnectException', $audit->new_values['exception'] ?? '');
        $this->assertStringContainsString('timed out', $audit->new_values['message'] ?? '');
        $this->assertStringContainsString('oauth2.googleapis.com/token', $audit->new_values['guzzle_request'] ?? '');
    }

    public function test_callback_accepts_email_inside_allowed_hosted_domains(): void
    {
        config()->set('services.google.hosted_domains', ['kojaya.co.id']);
        CooperativeMember::factory()->create([
            'user_id' => null,
            'email' => 'member@kojaya.co.id',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $this->mockSocialite(googleId: 'domain-ok', email: 'member@kojaya.co.id', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('member.onboarding'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'domain-ok',
            'provider_email' => 'member@kojaya.co.id',
        ]);
    }

    public function test_authenticated_user_can_link_google_account(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'link@example.com']);
        $this->mockSocialite(googleId: 'link-123', email: 'link@example.com', verified: true);

        $this->actingAs($user)
            ->withSession(['google_sso_intent' => 'link', 'google_sso_return_to' => route('profile.edit', absolute: false)])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.edit', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'link-123',
        ]);
    }

    public function test_authenticated_user_linking_different_email_does_not_verify_user_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'local-user@example.com']);
        $this->mockSocialite(googleId: 'link-diff-456', email: 'different-google@example.com', verified: true);

        $this->actingAs($user)
            ->withSession(['google_sso_intent' => 'link', 'google_sso_return_to' => route('profile.edit', absolute: false)])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.edit', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'link-diff-456',
            'provider_email' => 'different-google@example.com',
        ]);
    }

    public function test_authenticated_user_linking_unverified_matching_email_does_not_verify_user_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'unverified-match@example.com']);
        $this->mockSocialite(googleId: 'link-unverified-789', email: 'unverified-match@example.com', verified: false);

        $this->actingAs($user)
            ->withSession(['google_sso_intent' => 'link', 'google_sso_return_to' => route('profile.edit', absolute: false)])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.edit', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'link-unverified-789',
        ]);
    }

    public function test_authenticated_callback_without_link_intent_does_not_switch_user(): void
    {
        $currentUser = User::factory()->create(['email' => 'current@example.com']);
        User::factory()->create(['email' => 'other@example.com']);
        $this->mockSocialite(googleId: 'other-google', email: 'other@example.com', verified: true);

        $this->actingAs($currentUser)
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($currentUser);
        $this->assertDatabaseMissing('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'other-google',
        ]);
    }

    public function test_authenticated_user_cannot_link_google_account_used_by_another_user(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $user = User::factory()->create(['email' => 'linker@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $owner->id,
            'provider' => 'google',
            'provider_id' => 'already-linked',
            'provider_email' => 'owner@example.com',
        ]);
        $this->mockSocialite(googleId: 'already-linked', email: 'linker@example.com', verified: true);

        $this->actingAs($user)
            ->withSession(['google_sso_intent' => 'link'])
            ->get(route('auth.google.callback'))
            ->assertForbidden();

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $owner->id,
            'provider' => 'google',
            'provider_id' => 'already-linked',
        ]);
    }

    public function test_mobile_google_login_returns_member_token_for_existing_member(): void
    {
        $user = User::factory()->create(['email' => 'member-mobile@example.com']);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'email' => 'member-mobile@example.com',
            'last_sso_login_at' => null,
        ]);
        $social = SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'mobile-google-123',
            'provider_email' => 'member-mobile@example.com',
            'last_login_at' => null,
        ]);

        $lastLoginBefore = $social->fresh()->last_login_at;
        $lastSsoLoginBefore = $member->fresh()->last_sso_login_at;
        $this->assertNull($lastLoginBefore, 'Initial social last_login_at must be null.');
        $this->assertNull($lastSsoLoginBefore, 'Initial member last_sso_login_at must be null.');

        $auditCountBefore = AuditLog::query()
            ->where('action', 'sso.google.login_success')
            ->count();

        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'mobile-google-123',
            'email' => 'member-mobile@example.com',
            'email_verified' => true,
            'name' => 'Member Mobile',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['profile:read', 'member:read', 'member:write'])
            ->assertJsonPath('auth_result', 'login_existing')
            ->assertJsonPath('member_status', 'ACTIVE')
            ->assertJsonPath('validation_status', 'ACTIVE')
            ->assertJsonPath('onboarding_next_step', 'dashboard');

        $this->assertNotNull(
            $social->fresh()->last_login_at,
            'Successful mobile Google authentication must record social last_login_at.'
        );

        $this->assertNotNull(
            $member->fresh()->last_sso_login_at,
            'Successful mobile Google authentication must record member last_sso_login_at.'
        );

        $auditCountAfter = AuditLog::query()
            ->where('action', 'sso.google.login_success')
            ->count();

        $this->assertSame(
            $auditCountBefore + 1,
            $auditCountAfter,
            'Successful mobile Google authentication must record sso.google.login_success exactly once.'
        );
    }

    public function test_mobile_google_login_rejects_blocked_unknown_lifecycle_with_zero_token_issuance(): void
    {
        $user = User::factory()->create(['email' => 'member-blocked@example.com']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'email' => 'member-blocked@example.com',
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
        ]);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'mobile-google-blocked-123',
            'provider_email' => 'member-blocked@example.com',
        ]);
        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'mobile-google-blocked-123',
            'email' => 'member-blocked@example.com',
            'email_verified' => true,
            'name' => 'Blocked Member',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $tokensBefore = \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->count();
        $this->assertSame(0, $tokensBefore);

        $response = $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'MEMBER_NOT_ACTIVE')
            ->assertJsonPath('data.lifecycle_experience', 'BLOCKED_UNKNOWN')
            ->assertJsonPath('data.member_status', 'INACTIVE')
            ->assertJsonPath('data.validation_status', 'INACTIVE');

        $response->assertJsonMissing(['token', 'token_type']);

        $tokensAfter = \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->count();

        $this->assertSame(0, $tokensAfter, 'ZERO personal access tokens may be created for blocked Google mobile login.');
    }

    public function test_mobile_google_login_rejects_wrong_audience(): void
    {
        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'aud' => 'android-client.apps.googleusercontent.com',
            'sub' => 'mobile-google-456',
            'email' => 'member-mobile@example.com',
            'email_verified' => true,
            'name' => 'Member Mobile',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Token Google tidak ditujukan untuk aplikasi Kojaya.');
    }

    public function test_mobile_google_login_handles_google_verification_outage(): void
    {
        Http::fake(function () {
            throw new ConnectionException('tokeninfo timeout');
        });

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => 'header.payload.signature',
            'device_name' => 'Android Member',
        ])->assertStatus(503)
            ->assertJsonPath('message', 'Layanan verifikasi Google sedang tidak dapat dihubungi. Coba lagi beberapa saat.');
    }

    public function test_mobile_google_login_falls_back_to_google_jwks_when_tokeninfo_is_unreachable(): void
    {
        $user = User::factory()->create(['email' => 'jwks-member@example.com']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'email' => 'jwks-member@example.com',
        ]);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'jwks-google-123',
            'provider_email' => 'jwks-member@example.com',
        ]);
        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'jwks-google-123',
            'email' => 'jwks-member@example.com',
            'email_verified' => true,
            'name' => 'JWKS Member',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => function () {
                throw new ConnectionException('tokeninfo timeout');
            },
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['profile:read', 'member:read', 'member:write'])
            ->assertJsonPath('auth_result', 'login_existing')
            ->assertJsonPath('user.email', 'jwks-member@example.com');
    }

    public function test_mobile_google_login_resolves_first_time_match_and_returns_login_linked(): void
    {
        $member = CooperativeMember::factory()->create([
            'user_id' => null,
            'nama_anggota' => 'Mobile First Member',
            'email' => 'mobile-first@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'mobile-first-999',
            'email' => 'mobile-first@example.com',
            'email_verified' => true,
            'name' => 'Mobile First Member',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['profile:read', 'member:read', 'member:write'])
            ->assertJsonPath('auth_result', 'login_linked')
            ->assertJsonPath('member_status', 'PENDING')
            ->assertJsonPath('validation_status', 'PENDING')
            ->assertJsonPath('onboarding_next_step', 'waiting_admin_acceptance');

        $user = User::query()->where('email', 'mobile-first@example.com')->firstOrFail();
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'mobile-first-999',
        ]);
    }

    public function test_mobile_google_login_allows_existing_member_even_if_google_email_unverified(): void
    {
        $user = User::factory()->create(['email' => 'existing-mobile-unverified@example.com']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'email' => 'existing-mobile-unverified@example.com',
        ]);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'mobile-exist-unverified-id',
            'provider_email' => 'existing-mobile-unverified@example.com',
        ]);

        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'mobile-exist-unverified-id',
            'email' => 'existing-mobile-unverified@example.com',
            'email_verified' => false,
            'name' => 'Existing Member',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('auth_result', 'login_existing');
    }

    public function test_mobile_google_login_rejects_unverified_email_when_first_time_matching(): void
    {
        $member = CooperativeMember::factory()->create([
            'user_id' => null,
            'email' => 'mobile-unverified-first@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'mobile-unverified-first-id',
            'email' => 'mobile-unverified-first@example.com',
            'email_verified' => false,
            'name' => 'Unverified First Member',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Email Google tidak valid atau belum terverifikasi.');

        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'mobile-unverified-first@example.com']);
    }

    public function test_web_google_callback_rejects_blocked_unknown_before_session_and_success_side_effects(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked.web@example.com',
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'email' => 'blocked.web@example.com',
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
            'last_sso_login_at' => null,
        ]);

        $social = SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-blocked-web',
            'provider_email' => 'blocked.web@example.com',
            'last_login_at' => null,
        ]);

        $this->mockSocialite(googleId: 'google-blocked-web', email: 'blocked.web@example.com', verified: true);

        $auditCountBefore = AuditLog::query()->where('action', 'sso.google.login_success')->count();

        $this->get(route('auth.google.callback'))
            ->assertStatus(403);

        $this->assertGuest();
        $this->assertNull(
            $social->fresh()->last_login_at,
            'Blocked web Google authentication must not mutate social last_login_at.'
        );
        $this->assertNull(
            $member->fresh()->last_sso_login_at,
            'Blocked web Google authentication must not mutate member last_sso_login_at.'
        );
        $this->assertSame(
            $auditCountBefore,
            AuditLog::query()->where('action', 'sso.google.login_success')->count(),
            'Blocked web Google authentication must not record sso.google.login_success audit.'
        );
    }

    public function test_successful_google_web_login_records_login_side_effects_at_success_boundary(): void
    {
        $user = User::factory()->create([
            'email' => 'active.web@example.com',
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'email' => 'active.web@example.com',
            'status' => 'ACTIVE',
            'validation_status' => 'ACTIVE',
            'last_sso_login_at' => null,
        ]);

        $social = SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-active-web',
            'provider_email' => 'active.web@example.com',
            'last_login_at' => null,
        ]);

        $this->mockSocialite(googleId: 'google-active-web', email: 'active.web@example.com', verified: true);

        $auditCountBefore = AuditLog::query()->where('action', 'sso.google.login_success')->count();

        $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(
            $social->fresh()->last_login_at,
            'Successful web Google authentication must record social last_login_at.'
        );
        $this->assertNotNull(
            $member->fresh()->last_sso_login_at,
            'Successful web Google authentication must record member last_sso_login_at.'
        );
        $this->assertSame(
            $auditCountBefore + 1,
            AuditLog::query()->where('action', 'sso.google.login_success')->count(),
            'Successful web Google authentication must record sso.google.login_success audit exactly once.'
        );
    }

    protected function mockSocialite(string $googleId, string $email, bool $verified): void
    {
        $abstract = Mockery::mock(Provider::class);
        $abstract->shouldReceive('user')->andReturn($this->fakeSocialiteUser($googleId, $email, $verified));
        $abstract->shouldReceive('redirect')->andReturn(redirect('/fake-google-oauth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($abstract);
    }

    protected function fakeSocialiteUser(string $id, string $email, bool $verified): \Laravel\Socialite\Two\User
    {
        $user = new \Laravel\Socialite\Two\User;
        $user->id = $id;
        $user->nickname = null;
        $user->name = 'Tester '.$id;
        $user->email = $email;
        $user->avatar = null;
        $user->user = [
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'name' => 'Tester '.$id,
        ];
        $user->token = Str::random(40);
        $user->refreshToken = null;
        $user->expiresIn = 3600;
        $user->attributes['token_type'] = 'Bearer';

        return $user;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function fakeGoogleIdToken(string $privateKey, array $claims = []): string
    {
        return JWT::encode(array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => 'web-client.apps.googleusercontent.com',
            'sub' => 'google-subject',
            'email' => 'member@example.com',
            'email_verified' => true,
            'name' => 'Google Member',
            'iat' => time(),
            'exp' => time() + 300,
        ], $claims), $privateKey, 'RS256', 'test-kid');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
