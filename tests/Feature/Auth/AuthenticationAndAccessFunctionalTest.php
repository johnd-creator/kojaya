<?php

namespace Tests\Feature\Auth;

use App\Enums\ApiErrorCode;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberAccessRevocationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class AuthenticationAndAccessFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->organization = Organization::factory()->create();
        Cache::flush();
    }

    // =========================================================================
    // AUTH-001: Web Login & Role-Based Redirection
    // =========================================================================

    public function test_auth_001_active_member_redirects_to_member_dashboard(): void
    {
        $user = $this->createActiveMemberUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/member');
    }

    public function test_auth_001_non_active_lifecycle_members_redirect_to_onboarding(): void
    {
        $factories = [
            fn ($u) => CooperativeMember::factory()->pending()->create(['user_id' => $u->id, 'organization_id' => $this->organization->id]),
            fn ($u) => CooperativeMember::factory()->pendingReview()->create(['user_id' => $u->id, 'organization_id' => $this->organization->id]),
            fn ($u) => CooperativeMember::factory()->revision()->create(['user_id' => $u->id, 'organization_id' => $this->organization->id]),
            fn ($u) => CooperativeMember::factory()->rejected()->create(['user_id' => $u->id, 'organization_id' => $this->organization->id]),
        ];

        foreach ($factories as $factoryCallback) {
            $user = User::factory()->create([
                'organization_id' => $this->organization->id,
                'password' => Hash::make('password'),
            ]);
            $user->assignRole('Anggota');
            $factoryCallback($user);

            $response = $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $this->assertAuthenticatedAs($user);
            $response->assertRedirect(route('member.onboarding'));

            $this->post(route('logout'));
        }
    }

    public function test_auth_001_staff_roles_redirect_to_staff_dashboard(): void
    {
        $staffRoles = [
            'System Admin',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
        ];

        foreach ($staffRoles as $role) {
            $staff = User::factory()->create([
                'organization_id' => $this->organization->id,
                'password' => Hash::make('password'),
            ]);
            $staff->assignRole($role);

            $response = $this->post(route('login.store'), [
                'email' => $staff->email,
                'password' => 'password',
            ]);

            $this->assertAuthenticatedAs($staff);
            $response->assertRedirect('/dashboard');

            $this->post(route('logout'));
        }
    }

    public function test_auth_001_intended_url_preserved_for_active_members(): void
    {
        $user = $this->createActiveMemberUser();

        $response = $this->withSession(['url.intended' => '/member/profile'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/member/profile');
    }

    public function test_auth_001_intended_url_cleared_for_non_active_members(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->pending()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withSession(['url.intended' => '/member/profile'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('member.onboarding'));
    }

    // =========================================================================
    // AUTH-002: Web Logout & Session Invalidation
    // =========================================================================

    public function test_auth_002_web_logout_destroys_session_and_regenerates_token(): void
    {
        $user = $this->createActiveMemberUser();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_auth_002_subsequent_request_with_logged_out_session_is_denied(): void
    {
        $user = $this->createActiveMemberUser();

        // Authenticate user
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // Attempting to access protected page redirects to login
        $protectedResponse = $this->get('/member');
        $protectedResponse->assertRedirect(route('login'));
    }

    // =========================================================================
    // AUTH-003: Two-Factor Authentication (2FA) Setup & Challenge
    // =========================================================================

    public function test_auth_003_two_factor_challenge_redirects_guest_and_requires_code(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = $this->createActiveMemberUser();
        $this->enableTwoFactor($user, ['recovery-code-1234']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_auth_003_two_factor_challenge_successful_with_recovery_code_and_consumes_it(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = $this->createActiveMemberUser();
        $this->enableTwoFactor($user, ['single-use-code-1', 'single-use-code-2']);

        // Step 1: Initial login triggers 2FA
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Step 2: Submit valid recovery code
        $challengeResponse = $this->post(route('two-factor.login'), [
            'recovery_code' => 'single-use-code-1',
        ]);

        $this->assertAuthenticatedAs($user);
        $challengeResponse->assertRedirect('/member');

        // Step 3: Verify the used recovery code was replaced/consumed in DB
        $user->refresh();
        $storedCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $this->assertNotContains('single-use-code-1', $storedCodes);
        $this->assertContains('single-use-code-2', $storedCodes);
    }

    public function test_auth_003_two_factor_challenge_rejects_used_or_invalid_recovery_code(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = $this->createActiveMemberUser();
        $this->enableTwoFactor($user, ['valid-code-only']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->post(route('two-factor.login'), [
            'recovery_code' => 'completely-wrong-code',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_auth_003_two_factor_challenge_rate_limited_after_max_attempts(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = $this->createActiveMemberUser();
        $this->enableTwoFactor($user, ['code-1']);

        // Submit 5 failed attempts to trigger rate limiter
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.login'), [
                'recovery_code' => 'wrong-code-'.$i,
            ]);
        }

        $response = $this->post(route('two-factor.login'), [
            'recovery_code' => 'code-1',
        ]);

        $response->assertTooManyRequests();
        $this->assertGuest();
    }

    // =========================================================================
    // AUTH-004: Google SSO Redirection & Callback
    // =========================================================================

    public function test_auth_004_google_sso_redirect_to_oauth_provider(): void
    {
        config()->set('services.google.sso_enabled', true);
        config()->set('services.google.client_id', 'web-client.apps.googleusercontent.com');

        $response = $this->get(route('auth.google.redirect'));
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', (string) $response->headers->get('Location'));
    }

    public function test_auth_004_google_sso_disabled_redirects_to_login(): void
    {
        config()->set('services.google.sso_enabled', false);

        $response = $this->get(route('auth.google.redirect'));
        $response->assertRedirect(route('login'));
    }

    public function test_auth_004_google_sso_tampered_state_fails_closed_with_audit_log(): void
    {
        config()->set('services.google.sso_enabled', true);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('sso');
        $this->assertGuest();

        $audit = AuditLog::query()->where('action', 'sso.google.login_failed')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame('callback_failed', $audit->new_values['reason'] ?? null);
        $this->assertStringContainsString('InvalidStateException', $audit->new_values['exception'] ?? '');
    }

    public function test_auth_004_google_sso_unverified_email_fails_closed(): void
    {
        config()->set('services.google.sso_enabled', true);

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-id-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('unverified@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Unverified User');
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);
        $abstractUser->user = [
            'email_verified' => false,
        ];

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andReturn($abstractUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('sso');
        $this->assertGuest();
    }

    // =========================================================================
    // AUTH-005: Mobile API Token Issuance (Password)
    // =========================================================================

    public function test_auth_005_mobile_login_issues_bearer_token_with_member_abilities(): void
    {
        $user = $this->createActiveMemberUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
            'device_name' => 'Pixel 8 Pro',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token_type',
            'token',
            'abilities',
            'user' => [
                'id',
                'name',
                'email',
                'roles',
                'cooperative_member_id',
            ],
            'member_status',
            'validation_status',
            'lifecycle_experience',
        ]);

        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertContains('profile:read', $response->json('abilities'));
        $this->assertContains('member:read', $response->json('abilities'));
        $this->assertContains('member:write', $response->json('abilities'));
        $this->assertNotContains('cooperative:write', $response->json('abilities'));
        $this->assertSame($user->cooperativeMember->id, $response->json('user.cooperative_member_id'));

        // Verify token can authenticate API requests
        $token = $response->json('token');
        $sessionResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/session');
        $sessionResponse->assertOk();
        $sessionResponse->assertJsonPath('user.id', $user->id);
    }

    public function test_auth_005_mobile_login_with_invalid_credentials_fails_without_token_issuance(): void
    {
        $user = $this->createActiveMemberUser();

        $tokensBefore = PersonalAccessToken::query()->where('tokenable_id', $user->id)->count();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'app' => 'member',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');

        $tokensAfter = PersonalAccessToken::query()->where('tokenable_id', $user->id)->count();
        $this->assertSame(0, $tokensBefore);
        $this->assertSame(0, $tokensAfter);
    }

    public function test_auth_005_mobile_login_rate_limiting(): void
    {
        $user = $this->createActiveMemberUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'app' => 'member',
            ]);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'app' => 'member',
        ]);

        $response->assertTooManyRequests();
    }

    // =========================================================================
    // AUTH-006: Inactive / Blocked Member Access Denial
    // =========================================================================

    public function test_auth_006_blocked_member_web_login_aborts_403(): void
    {
        $blockedUsers = [
            $this->createMemberWithStatus('INACTIVE', 'INACTIVE'),
            $this->createMemberWithStatus('RESIGNED', 'RESIGNED'),
            $this->createMemberWithStatus('EXPELLED', 'EXPELLED'),
        ];

        foreach ($blockedUsers as $user) {
            $response = $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertForbidden();
            $this->assertGuest();
        }
    }

    public function test_auth_006_blocked_member_mobile_login_aborts_403_with_api_error_code(): void
    {
        $blockedUsers = [
            $this->createMemberWithStatus('INACTIVE', 'INACTIVE'),
            $this->createMemberWithStatus('RESIGNED', 'RESIGNED'),
            $this->createMemberWithStatus('EXPELLED', 'EXPELLED'),
        ];

        foreach ($blockedUsers as $user) {
            $tokensBefore = PersonalAccessToken::query()->where('tokenable_id', $user->id)->count();

            $response = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password',
                'app' => 'member',
            ]);

            $response->assertForbidden();
            $response->assertJson([
                'success' => false,
                'message' => 'Status keanggotaan tidak valid.',
                'error_code' => ApiErrorCode::MemberNotActive->value,
            ]);
            $response->assertJsonMissing(['token', 'token_type']);

            $tokensAfter = PersonalAccessToken::query()->where('tokenable_id', $user->id)->count();
            $this->assertSame(0, $tokensBefore);
            $this->assertSame(0, $tokensAfter);
        }
    }

    public function test_auth_006_mid_session_member_deactivation_revokes_tokens(): void
    {
        $user = $this->createActiveMemberUser();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
        ]);
        $token = $loginResponse->json('token');

        // Verify token initially works
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/session')
            ->assertOk();

        // Admin deactivates the member
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');
        app(MemberAccessRevocationService::class)->revokeFor($user->cooperativeMember->refresh(), 'deactivated', $admin);

        // Reset auth guard cached user between test requests
        auth()->forgetGuards();

        // Token must now be revoked and rejected
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/session')
            ->assertUnauthorized();
    }

    public function test_auth_006_blocked_member_session_cannot_access_member_portal(): void
    {
        $user = $this->createMemberWithStatus('INACTIVE', 'INACTIVE');

        // Acting as blocked user attempting to access /member
        $response = $this->actingAs($user)->get('/member');
        $response->assertForbidden();
    }

    // =========================================================================
    // AUTH-007: API Token Revocation (Logout)
    // =========================================================================

    public function test_auth_007_single_token_logout_revokes_only_current_token(): void
    {
        $user = $this->createActiveMemberUser();

        $tokenA = $user->createToken('phone', ['profile:read', 'member:read'])->plainTextToken;
        $tokenB = $user->createToken('tablet', ['profile:read', 'member:read'])->plainTextToken;

        $this->assertCount(2, $user->tokens()->get());

        // Logout using Token A
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/auth/logout');
        $logoutResponse->assertOk()
            ->assertJson(['message' => 'Logged out.']);

        // Reset auth guard cached user between test requests
        auth()->forgetGuards();

        // Token A is revoked
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson('/api/auth/session')
            ->assertUnauthorized();

        auth()->forgetGuards();

        // Token B is still active
        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson('/api/auth/session')
            ->assertOk();

        $this->assertCount(1, $user->tokens()->get());
    }

    public function test_auth_007_logout_all_revokes_all_user_tokens(): void
    {
        $user = $this->createActiveMemberUser();

        $tokenA = $user->createToken('phone', ['profile:read'])->plainTextToken;
        $tokenB = $user->createToken('tablet', ['profile:read'])->plainTextToken;
        $tokenC = $user->createToken('desktop', ['profile:read'])->plainTextToken;

        $this->assertCount(3, $user->tokens()->get());

        // Call logout-all
        $logoutAllResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/auth/logout-all');
        $logoutAllResponse->assertOk()
            ->assertJson(['message' => 'All tokens revoked.']);

        // All tokens are revoked
        auth()->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokenA)->getJson('/api/auth/session')->assertUnauthorized();
        auth()->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokenB)->getJson('/api/auth/session')->assertUnauthorized();
        auth()->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokenC)->getJson('/api/auth/session')->assertUnauthorized();

        $this->assertCount(0, $user->tokens()->get());
    }

    // =========================================================================
    // AUTH-008: Password Confirmation & Reset Link
    // =========================================================================

    public function test_auth_008_password_reset_flow_with_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make('old-password-123'),
        ]);

        // 1. Request password reset link
        $requestResponse = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);
        $requestResponse->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $token = $notification->token;

            // 2. Submit new password with the token
            $resetResponse = $this->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-secure-password-456',
                'password_confirmation' => 'new-secure-password-456',
            ]);

            $resetResponse->assertSessionHasNoErrors();
            $resetResponse->assertRedirect(route('login'));

            return true;
        });

        // 3. Verify old password fails, new password succeeds
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'old-password-123',
        ]);
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'new-secure-password-456',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_auth_008_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make('current-password-123'),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'completely-invalid-reset-token',
            'email' => $user->email,
            'password' => 'new-password-789',
            'password_confirmation' => 'new-password-789',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('current-password-123', $user->fresh()->password));
    }

    public function test_auth_008_password_confirmation_endpoint(): void
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make('secret-password'),
        ]);

        // Wrong password fails confirmation
        $failResponse = $this->actingAs($user)->post(route('password.confirm.store'), [
            'password' => 'wrong-password',
        ]);
        $failResponse->assertSessionHasErrors('password');

        // Correct password succeeds confirmation
        $successResponse = $this->actingAs($user)->post(route('password.confirm.store'), [
            'password' => 'secret-password',
        ]);
        $successResponse->assertSessionHasNoErrors();
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createActiveMemberUser(string $password = 'password'): User
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make($password),
        ]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
        ]);

        return $user;
    }

    private function createMemberWithStatus(string $status, string $validationStatus, string $password = 'password'): User
    {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'password' => Hash::make($password),
        ]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'status' => $status,
            'validation_status' => $validationStatus,
        ]);

        return $user;
    }

    private function enableTwoFactor(User $user, array $recoveryCodes = ['code-1']): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }
}
