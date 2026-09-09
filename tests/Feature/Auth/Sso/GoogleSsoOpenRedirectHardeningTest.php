<?php

namespace Tests\Feature\Auth\Sso;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleSsoOpenRedirectHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Role::firstOrCreate(['name' => 'Anggota']);
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        Permission::firstOrCreate(['name' => 'view_cooperative_member']);

        config()->set('services.google.sso_enabled', true);
        config()->set('services.google.client_id', 'web-client.apps.googleusercontent.com');
        config()->set('services.google.allow_new_member_registration', true);
    }

    public function test_link_endpoint_does_not_store_external_previous_url(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->mockSocialite(googleId: 'link-101', email: 'link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'https://evil.example/phishing')
            ->get(route('auth.google.link'));

        $response->assertRedirect('/fake-google-oauth');
        $this->assertFalse(
            session()->has('google_sso_return_to'),
            'Unsafe external previous URL must not be stored in session.'
        );
    }

    public function test_link_endpoint_rejects_protocol_relative_previous_url(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->mockSocialite(googleId: 'link-102', email: 'link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withHeader('Referer', '//evil.example/phishing')
            ->get(route('auth.google.link'));

        $response->assertRedirect('/fake-google-oauth');
        $this->assertFalse(session()->has('google_sso_return_to'));
    }

    public function test_link_endpoint_rejects_lookalike_hostname_previous_url(): void
    {
        config()->set('app.url', 'https://app.kojaya.id');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->mockSocialite(googleId: 'link-103', email: 'link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'https://app.kojaya.id.evil.example/phishing')
            ->get(route('auth.google.link'));

        $response->assertRedirect('/fake-google-oauth');
        $this->assertFalse(session()->has('google_sso_return_to'));
    }

    public function test_link_endpoint_rejects_userinfo_hostname_confusion_previous_url(): void
    {
        config()->set('app.url', 'https://app.kojaya.id');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->mockSocialite(googleId: 'link-104', email: 'link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'https://app.kojaya.id@evil.example/phishing')
            ->get(route('auth.google.link'));

        $response->assertRedirect('/fake-google-oauth');
        $this->assertFalse(session()->has('google_sso_return_to'));
    }

    public function test_link_endpoint_stores_valid_same_origin_previous_url_as_relative_path(): void
    {
        config()->set('app.url', 'https://app.kojaya.id');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->mockSocialite(googleId: 'link-105', email: 'link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'https://app.kojaya.id/member/profile?tab=security')
            ->get(route('auth.google.link'));

        $response->assertRedirect('/fake-google-oauth');
        $this->assertSame(
            '/member/profile?tab=security',
            session('google_sso_return_to'),
            'Same-origin URL must be normalized to relative path before session storage.'
        );
    }

    public function test_link_endpoint_fails_closed_to_internal_route_when_disabled(): void
    {
        config()->set('services.google.sso_enabled', false);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withHeader('Referer', 'https://evil.example/phishing')
            ->get(route('auth.google.link'));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
    }

    public function test_callback_account_linking_redirects_to_valid_internal_return_destination(): void
    {
        $user = User::factory()->create(['email' => 'member-link@example.com']);
        $this->mockSocialite(googleId: 'google-link-201', email: 'member-link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_intent' => 'link',
                'google_sso_return_to' => '/member/profile?tab=security',
            ])
            ->get(route('auth.google.callback'));

        $response->assertRedirect('/member/profile?tab=security');
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
        $this->assertFalse(session()->has('google_sso_return_to'));
        $this->assertFalse(session()->has('google_sso_intent'));
    }

    public function test_callback_account_linking_blocks_tampered_external_session_destination(): void
    {
        $user = User::factory()->create(['email' => 'member-link@example.com']);
        $this->mockSocialite(googleId: 'google-link-202', email: 'member-link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_intent' => 'link',
                'google_sso_return_to' => 'https://evil.example/phishing',
            ])
            ->get(route('auth.google.callback'));

        // Must fail closed to server-controlled destination (dashboard)
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
        $this->assertFalse(session()->has('google_sso_return_to'));
    }

    public function test_callback_account_linking_blocks_protocol_relative_session_destination(): void
    {
        $user = User::factory()->create(['email' => 'member-link@example.com']);
        $this->mockSocialite(googleId: 'google-link-203', email: 'member-link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_intent' => 'link',
                'google_sso_return_to' => '//evil.example/phishing',
            ])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
    }

    public function test_callback_account_linking_blocks_backslash_session_destination(): void
    {
        $user = User::factory()->create(['email' => 'member-link@example.com']);
        $this->mockSocialite(googleId: 'google-link-204', email: 'member-link@example.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_intent' => 'link',
                'google_sso_return_to' => '/\\evil.example',
            ])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
    }

    public function test_callback_hosted_domain_denial_blocks_external_session_return_destination(): void
    {
        config()->set('services.google.hosted_domains', ['kojaya.co.id']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->mockSocialite(googleId: 'domain-blocked', email: 'other@gmail.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_return_to' => 'https://evil.example/phishing',
            ])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasErrors('sso');

        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
        $this->assertFalse(session()->has('google_sso_return_to'));
        $this->assertFalse(session()->has('google_sso_intent'));
    }

    public function test_callback_hosted_domain_denial_allows_safe_internal_session_return_destination(): void
    {
        config()->set('services.google.hosted_domains', ['kojaya.co.id']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->mockSocialite(googleId: 'domain-blocked-2', email: 'other@gmail.com', verified: true);

        $response = $this->actingAs($user)
            ->withSession([
                'google_sso_return_to' => '/member/profile',
            ])
            ->get(route('auth.google.callback'));

        $response->assertRedirect('/member/profile')
            ->assertSessionHasErrors('sso');

        $this->assertFalse(session()->has('google_sso_return_to'));
    }

    public function test_ordinary_google_login_blocks_poisoned_intended_url(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);
        $this->mockSocialite(googleId: 'login-301', email: 'returning@example.com', verified: true);

        $response = $this->withSession([
            'url.intended' => 'https://evil.example/phishing',
        ])->get(route('auth.google.callback'));

        // Redirect must fall back to server-controlled destination and NOT redirect to evil.example
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_ordinary_google_login_allows_valid_local_intended_url(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);
        $this->mockSocialite(googleId: 'login-302', email: 'returning@example.com', verified: true);

        $response = $this->withSession([
            'url.intended' => '/member/profile?tab=account',
        ])->get(route('auth.google.callback'));

        $response->assertRedirect('/member/profile?tab=account');
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_ordinary_google_login_routes_pending_member_to_onboarding(): void
    {
        $pendingUser = User::factory()->create(['email' => 'pending-member@example.com']);
        CooperativeMember::factory()->create([
            'user_id' => $pendingUser->id,
            'email' => 'pending-member@example.com',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $this->mockSocialite(googleId: 'status-1', email: 'pending-member@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('member.onboarding', absolute: false));
    }

    public function test_ordinary_google_login_routes_active_member_to_member_dashboard(): void
    {
        $activeUser = User::factory()->create(['email' => 'active-member@example.com']);
        CooperativeMember::factory()->active()->create([
            'user_id' => $activeUser->id,
            'email' => 'active-member@example.com',
        ]);
        $this->mockSocialite(googleId: 'status-2', email: 'active-member@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('member.dashboard', absolute: false));
    }

    public function test_ordinary_google_login_routes_cooperative_admin_to_cooperative_members(): void
    {
        $adminUser = User::factory()->create(['email' => 'admin-coop@example.com']);
        $adminUser->givePermissionTo('view_cooperative_member');
        $this->mockSocialite(googleId: 'status-3', email: 'admin-coop@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('cooperative.members.index', absolute: false));
    }

    public function test_ordinary_google_login_routes_default_user_to_dashboard(): void
    {
        $defaultUser = User::factory()->create(['email' => 'default-user@example.com']);
        $this->mockSocialite(googleId: 'status-4', email: 'default-user@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_stale_session_state_is_cleared_on_callback_failures(): void
    {
        // Unverified email
        $this->mockSocialite(googleId: 'unverified-sso', email: 'unverified@example.com', verified: false);

        $response = $this->withSession([
            'google_sso_intent' => 'link',
            'google_sso_return_to' => 'https://evil.example',
        ])->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('google_sso_intent'));
        $this->assertFalse(session()->has('google_sso_return_to'));
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
