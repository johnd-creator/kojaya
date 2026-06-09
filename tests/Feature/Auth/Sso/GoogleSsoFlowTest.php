<?php

namespace Tests\Feature\Auth\Sso;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleSsoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Anggota']);
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        Permission::firstOrCreate(['name' => 'validate_cooperative_member']);

        config()->set('services.google.sso_enabled', true);
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

    public function test_callback_creates_pending_member_when_email_is_new(): void
    {
        $this->mockSocialite(googleId: '9001', email: 'new-member@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'))
            ->assertRedirect(route('member.onboarding'));

        $user = User::query()->where('email', 'new-member@example.com')->firstOrFail();
        $this->assertNotNull($user->cooperativeMember);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasRole('Anggota'));
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $user->cooperativeMember->validation_status);
        $this->assertNotNull(SocialAccount::query()->where('provider', 'google')->where('provider_id', '9001')->first());

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_to_existing_user(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'returning@example.com']);
        $this->mockSocialite(googleId: '777', email: 'returning@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '777',
        ]);
    }

    public function test_callback_links_to_existing_member(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'email' => 'member@example.com',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
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

    public function test_callback_accepts_email_inside_allowed_hosted_domains(): void
    {
        config()->set('services.google.hosted_domains', ['kojaya.co.id']);
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
