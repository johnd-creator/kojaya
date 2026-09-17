<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Sso;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleSsoMemberMatchingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Role::firstOrCreate(['name' => 'Anggota']);
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        Role::firstOrCreate(['name' => 'Pengurus Koperasi']);

        config()->set('services.google.sso_enabled', true);
        config()->set('services.google.client_id', 'web-client.apps.googleusercontent.com');
        config()->set('services.google.allow_new_member_registration', false);

        $this->organization = Organization::factory()->create();
    }

    /**
     * 01. existing Google provider_id binding logs same existing User in
     */
    public function test_01_existing_google_provider_id_binding_logs_existing_user_in(): void
    {
        $user = User::factory()->unverified()->create([
            'organization_id' => $this->organization->id,
            'email' => 'existing@example.com',
        ]);
        $user->assignRole('Anggota');

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-uid-01',
            'provider_email' => 'existing@example.com',
        ]);

        $this->mockSocialite(googleId: 'google-uid-01', email: 'existing@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * 01b. existing Google provider_id binding logs in even if Google reports email_verified = false (R1-01)
     */
    public function test_01b_existing_google_provider_id_binding_logs_in_even_if_email_unverified(): void
    {
        $user = User::factory()->unverified()->create([
            'organization_id' => $this->organization->id,
            'email' => 'existing-unverified@example.com',
        ]);
        $user->assignRole('Anggota');

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-uid-01b',
            'provider_email' => 'existing-unverified@example.com',
        ]);

        $this->mockSocialite(googleId: 'google-uid-01b', email: 'existing-unverified@example.com', verified: false);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * 02. existing provider binding + Google email changed: same User remains identity, no rematch, canonical member email unchanged
     */
    public function test_02_existing_provider_binding_ignores_email_change_and_preserves_canonical_member(): void
    {
        $user = User::factory()->unverified()->create([
            'organization_id' => $this->organization->id,
            'email' => 'original@example.com',
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'email' => 'original@example.com',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-uid-02',
            'provider_email' => 'original@example.com',
        ]);

        // Google returns a new email for the same provider_id
        $this->mockSocialite(googleId: 'google-uid-02', email: 'new-google-email@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('original@example.com', $member->fresh()->email);
        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * 03. first-time verified Google email + exactly one eligible canonical member + member.user_id null:
     * creates exactly one User, links member.user_id, creates exactly one SocialAccount
     */
    public function test_03_first_time_verified_google_email_creates_user_links_member_and_social_account(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'nama_anggota' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(
            googleId: 'google-uid-03',
            email: 'budi.santoso@example.com',
            verified: true,
            name: 'Google Display Name Should Be Ignored'
        );

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect(route('member.onboarding'));

        $this->assertAuthenticated();

        $createdUser = User::query()->where('email', 'budi.santoso@example.com')->firstOrFail();
        $this->assertSame($createdUser->id, $member->fresh()->user_id);
        $this->assertSame('google', $member->fresh()->sso_provider);
        $this->assertNotNull($member->fresh()->last_sso_login_at);

        $social = SocialAccount::query()
            ->where('provider', 'google')
            ->where('provider_id', 'google-uid-03')
            ->firstOrFail();
        $this->assertSame($createdUser->id, $social->user_id);
    }

    /**
     * 04. matching is LOWER(TRIM(email))
     */
    public function test_04_matching_is_case_insensitive_and_whitespace_trimmed(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'siti.aminah@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(
            googleId: 'google-uid-04',
            email: '  SiTi.AmiNah@Example.COM  ',
            verified: true
        );

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.onboarding'));

        $this->assertAuthenticated();
        $this->assertNotNull($member->fresh()->user_id);
    }

    /**
     * 05. new User name comes from canonical member, not Google display name
     */
    public function test_05_new_user_name_comes_from_canonical_member_not_google_display_name(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'nama_anggota' => 'Nama KTP Anggota',
            'email' => 'ktp@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(
            googleId: 'google-uid-05',
            email: 'ktp@example.com',
            verified: true,
            name: 'Cool Guy 99'
        );

        $this->get(route('auth.google.callback'))->assertRedirect();

        $createdUser = User::query()->where('email', 'ktp@example.com')->firstOrFail();
        $this->assertSame('Nama KTP Anggota', $createdUser->name);
    }

    /**
     * 06. new User organization_id comes from member, not request/OAuth input
     */
    public function test_06_new_user_organization_id_comes_from_canonical_member(): void
    {
        $otherOrg = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $otherOrg->id,
            'user_id' => null,
            'email' => 'org.member@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-06', email: 'org.member@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $createdUser = User::query()->where('email', 'org.member@example.com')->firstOrFail();
        $this->assertSame($otherOrg->id, $createdUser->organization_id);
    }

    /**
     * 07. new User gets Anggota role
     */
    public function test_07_new_user_gets_anggota_role(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'role.test@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-07', email: 'role.test@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $createdUser = User::query()->where('email', 'role.test@example.com')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('Anggota'));
    }

    /**
     * 08. no admin/cooperative administrative role granted
     */
    public function test_08_no_administrative_role_granted_to_new_user(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'noadmin@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-08', email: 'noadmin@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $createdUser = User::query()->where('email', 'noadmin@example.com')->firstOrFail();
        $this->assertFalse($createdUser->hasRole('Admin Koperasi'));
        $this->assertFalse($createdUser->hasRole('Pengurus Koperasi'));
        $this->assertSame(['Anggota'], $createdUser->getRoleNames()->values()->all());
    }

    /**
     * 09. no default/shared/plaintext password (random 64 char)
     */
    public function test_09_new_user_receives_unusable_high_entropy_hashed_password(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'password.test@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-09', email: 'password.test@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $createdUser = User::query()->where('email', 'password.test@example.com')->firstOrFail();
        $this->assertNotEmpty($createdUser->password);
        $this->assertFalse(Hash::check('password', (string) $createdUser->password));
        $this->assertFalse(Hash::check('secret', (string) $createdUser->password));
        $this->assertFalse(Hash::check('12345678', (string) $createdUser->password));
        $this->assertFalse(Hash::check('', (string) $createdUser->password));
    }

    /**
     * 10. trustworthy Google verified-email condition required
     */
    public function test_10_trustworthy_google_verified_email_condition_required(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'verified.required@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-10', email: 'verified.required@example.com', verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'verified.required@example.com']);
    }

    /**
     * 11. unverified Google email -> no User, no SocialAccount, no member linkage
     */
    public function test_11_unverified_google_email_creates_no_records_and_links_no_member(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'unverified.member@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-11', email: 'unverified.member@example.com', verified: false);

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'unverified.member@example.com']);
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'google-uid-11']);
    }

    /**
     * 12. no canonical member email match -> controlled failure, no CooperativeMember created, no TMP member
     */
    public function test_12_no_canonical_member_match_fails_closed_without_creating_member_or_user(): void
    {
        $this->mockSocialite(googleId: 'google-uid-12', email: 'unknown.stranger@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertSame(0, CooperativeMember::query()->where('email', 'unknown.stranger@example.com')->count());
        $this->assertSame(0, User::query()->where('email', 'unknown.stranger@example.com')->count());
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'like', 'TMP%')->count());
    }

    /**
     * 13. ambiguous duplicate member match -> fail closed
     */
    public function test_13_ambiguous_duplicate_member_match_fails_closed(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'duplicate@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'duplicate@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-13', email: 'duplicate@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'duplicate@example.com']);
    }

    /**
     * 14. unrelated existing users.email collision -> fail closed, no silent email auto-link
     */
    public function test_14_unrelated_existing_user_email_collision_fails_closed(): void
    {
        User::factory()->create([
            'email' => 'collision@example.com',
        ]);

        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'collision@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-14', email: 'collision@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'google-uid-14']);
    }

    /**
     * 15. provider_id already belongs to another User -> fail closed
     */
    public function test_15_provider_id_already_belonging_to_another_user_fails_closed(): void
    {
        $existingUser = User::factory()->create(['email' => 'first@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'shared-google-id',
            'provider_email' => 'first@example.com',
        ]);

        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'second@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        // Incoming attempt tries to claim the already used provider_id
        $this->mockSocialite(googleId: 'shared-google-id', email: 'second@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        // Must log in the first existing user, NOT link the second member
        $this->assertAuthenticatedAs($existingUser);
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    /**
     * 16. member.user_id conflict -> fail closed
     */
    public function test_16_member_user_id_conflict_fails_closed(): void
    {
        $otherUser = User::factory()->create(['email' => 'owner@example.com']);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $otherUser->id,
            'email' => 'owner@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        // Guest attempts first-time match for member that already has user_id set without social account
        $this->mockSocialite(googleId: 'google-uid-16', email: 'owner@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'google-uid-16']);
    }

    /**
     * 17. target User already has conflicting Google provider -> fail closed
     */
    public function test_17_target_user_already_having_google_provider_fails_closed(): void
    {
        $user = User::factory()->create(['email' => 'hasgoogle@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'original-google-id',
            'provider_email' => 'hasgoogle@example.com',
        ]);

        // Trying to authenticate under a different google provider_id with same email
        $this->mockSocialite(googleId: 'new-google-id', email: 'hasgoogle@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'new-google-id']);
    }

    /**
     * 18. PENDING/PENDING member linkage: statuses remain exactly PENDING/PENDING
     */
    public function test_18_pending_pending_member_linkage_preserves_statuses(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'pending.pending@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-18', email: 'pending.pending@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.onboarding'));

        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
    }

    /**
     * 19. PENDING/PENDING_VALIDATION member linkage: statuses unchanged
     */
    public function test_19_pending_validation_member_linkage_preserves_statuses(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'pending.review@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $this->mockSocialite(googleId: 'google-uid-19', email: 'pending.review@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.onboarding'));

        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
    }

    /**
     * 20. ACTIVE/ACTIVE member linkage: statuses unchanged
     */
    public function test_20_active_active_member_linkage_preserves_statuses(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'active.active@example.com',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->mockSocialite(googleId: 'google-uid-20', email: 'active.active@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.dashboard'));

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
    }

    /**
     * 21. INACTIVE/REVISION: automatic new binding blocked
     */
    public function test_21_inactive_revision_member_linkage_blocked(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'revision@example.com',
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
        ]);

        $this->mockSocialite(googleId: 'google-uid-21', email: 'revision@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
    }

    /**
     * 22. INACTIVE/REJECTED: automatic new binding blocked
     */
    public function test_22_inactive_rejected_member_linkage_blocked(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'rejected@example.com',
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $this->mockSocialite(googleId: 'google-uid-22', email: 'rejected@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
    }

    /**
     * 23. other terminal/unknown inactive state -> fail closed
     */
    public function test_23_terminal_resigned_state_fails_closed(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'resigned@example.com',
            'status' => CooperativeMember::VALIDATION_RESIGNED,
            'validation_status' => CooperativeMember::VALIDATION_RESIGNED,
        ]);

        $this->mockSocialite(googleId: 'google-uid-23', email: 'resigned@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
    }

    /**
     * 24. ONB-03 approval/verification service NOT invoked
     */
    public function test_24_admin_verification_service_not_invoked_during_sso_matching(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'no.onb03@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'admin_validated_at' => null,
            'admin_validated_by' => null,
        ]);

        $this->mockSocialite(googleId: 'google-uid-24', email: 'no.onb03@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $freshMember = $member->fresh();
        $this->assertNull($freshMember->admin_validated_at);
        $this->assertNull($freshMember->admin_validated_by);
    }

    /**
     * 25. member.active protection still applies to pending member
     */
    public function test_25_member_active_route_protection_still_applies_to_pending_member(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'active.protection@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-25', email: 'active.protection@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.onboarding'));

        // Attempting to access active-only member area should redirect to onboarding
        $response = $this->get(route('member.savings'));
        $response->assertRedirect(route('member.onboarding'));
    }

    /**
     * 26. SocialAccount first-link creation is atomic
     */
    public function test_26_social_account_first_link_creation_is_atomic(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'atomic@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-26', email: 'atomic@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $user = User::query()->where('email', 'atomic@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-uid-26',
        ]);
    }

    /**
     * 27. SocialAccount creation failure -> User creation rolled back, member.user_id rolled back
     */
    public function test_27_social_account_creation_failure_rolls_back_user_and_member_link(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'rollback.test@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        SocialAccount::creating(function (SocialAccount $social) {
            if ($social->provider_id === 'simulated-failure-uid-27') {
                throw new \RuntimeException('Simulated SocialAccount creation failure');
            }
        });

        $this->mockSocialite(googleId: 'simulated-failure-uid-27', email: 'rollback.test@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'));
        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'rollback.test@example.com']);
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'simulated-failure-uid-27']);
    }

    /**
     * 28. mandatory audit failure -> full first-link rollback
     */
    public function test_28_mandatory_audit_failure_rolls_back_entire_linking_transaction(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'audit.fail@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        // Mock AuditLogService to throw during log()
        $auditMock = Mockery::mock(AuditLogService::class);
        $auditMock->shouldReceive('log')
            ->andThrow(new Exception('Simulated audit persistence failure'));
        $this->app->instance(AuditLogService::class, $auditMock);

        $this->mockSocialite(googleId: 'google-uid-28', email: 'audit.fail@example.com', verified: true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'audit.fail@example.com']);
        $this->assertDatabaseMissing('social_accounts', ['provider_id' => 'google-uid-28']);
    }

    /**
     * 29. first-link audit event created on success
     */
    public function test_29_first_link_audit_event_created_on_success(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'audit.success@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-29', email: 'audit.success@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $user = User::query()->where('email', 'audit.success@example.com')->firstOrFail();

        $audit = AuditLog::query()
            ->where('action', 'member.google_sso_linked')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('auth.sso', $audit->module);
        $this->assertSame($member->id, $audit->new_values['member_id'] ?? null);
        $this->assertSame($user->id, $audit->new_values['user_id'] ?? null);
        $this->assertSame(hash('sha256', 'google-uid-29'), $audit->new_values['provider_id_hash'] ?? null);
    }

    /**
     * 30. audit metadata contains no NIK/OAuth token/full profile
     */
    public function test_30_audit_metadata_contains_no_nik_or_oauth_token(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'safe.audit@example.com',
            'identity_number' => '3201234567890001',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-30', email: 'safe.audit@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $audit = AuditLog::query()->where('action', 'member.google_sso_linked')->latest('id')->first();
        $this->assertNotNull($audit);

        $auditJson = json_encode($audit->toArray());
        $this->assertStringNotContainsString('3201234567890001', (string) $auditJson);
        $this->assertStringNotContainsString('access_token', (string) $auditJson);
        $this->assertStringNotContainsString('refresh_token', (string) $auditJson);
    }

    /**
     * 31. subsequent provider-id login does not create duplicate User
     */
    public function test_31_subsequent_login_does_not_create_duplicate_user(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'subsequent@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-31', email: 'subsequent@example.com', verified: true);

        // First login
        $this->get(route('auth.google.callback'))->assertRedirect();
        $this->assertSame(1, User::query()->where('email', 'subsequent@example.com')->count());

        // Log out
        auth()->logout();

        // Subsequent login
        $this->get(route('auth.google.callback'))->assertRedirect();
        $this->assertSame(1, User::query()->where('email', 'subsequent@example.com')->count());
    }

    /**
     * 32. subsequent provider-id login does not create duplicate SocialAccount
     */
    public function test_32_subsequent_login_does_not_create_duplicate_social_account(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'subsequent.social@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-32', email: 'subsequent.social@example.com', verified: true);

        // First login
        $this->get(route('auth.google.callback'))->assertRedirect();
        // Subsequent login
        auth()->logout();
        $this->get(route('auth.google.callback'))->assertRedirect();

        $this->assertSame(1, SocialAccount::query()->where('provider_id', 'google-uid-32')->count());
    }

    /**
     * 33. subsequent login does not repeat first-link mutation
     */
    public function test_33_subsequent_login_does_not_emit_duplicate_first_link_audit(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'no.repeat@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-33', email: 'no.repeat@example.com', verified: true);

        // First login
        $this->get(route('auth.google.callback'))->assertRedirect();
        auth()->logout();

        // Subsequent login
        $this->get(route('auth.google.callback'))->assertRedirect();

        $linkAudits = AuditLog::query()->where('action', 'member.google_sso_linked')->count();
        $this->assertSame(1, $linkAudits);
    }

    /**
     * 34. authenticated /auth/google/link cannot steal provider identity from another User
     */
    public function test_34_authenticated_link_cannot_steal_provider_from_another_user(): void
    {
        $victim = User::factory()->create(['email' => 'victim@example.com']);
        SocialAccount::factory()->create([
            'user_id' => $victim->id,
            'provider' => 'google',
            'provider_id' => 'stolen-google-id',
            'provider_email' => 'victim@example.com',
        ]);

        $attacker = User::factory()->create(['email' => 'attacker@example.com']);
        $this->mockSocialite(googleId: 'stolen-google-id', email: 'attacker@example.com', verified: true);

        $this->actingAs($attacker)
            ->withSession(['google_sso_intent' => 'link'])
            ->get(route('auth.google.callback'))
            ->assertForbidden();

        $this->assertSame($victim->id, SocialAccount::query()->where('provider_id', 'stolen-google-id')->value('user_id'));
    }

    /**
     * 35. no OAuth access token newly persisted
     */
    public function test_35_no_oauth_access_token_persisted(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'no.token@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-35', email: 'no.token@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $social = SocialAccount::query()->where('provider_id', 'google-uid-35')->firstOrFail();
        $this->assertNull($social->access_token);
    }

    /**
     * 36. no refresh token newly persisted
     */
    public function test_36_no_refresh_token_persisted(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'no.refresh@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-36', email: 'no.refresh@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $social = SocialAccount::query()->where('provider_id', 'google-uid-36')->firstOrFail();
        $this->assertNull($social->refresh_token);
    }

    /**
     * 37. no raw NIK in response/log/audit
     */
    public function test_37_no_raw_nik_in_response_or_audit(): void
    {
        $nik = '3171012345670002';
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'nik.privacy@example.com',
            'identity_number' => $nik,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-37', email: 'nik.privacy@example.com', verified: true);

        $response = $this->get(route('auth.google.callback'));
        $this->assertStringNotContainsString($nik, $response->getContent() ?: '');

        $audit = AuditLog::query()->where('action', 'member.google_sso_linked')->latest('id')->first();
        $this->assertStringNotContainsString($nik, json_encode($audit?->toArray() ?? []));
    }

    /**
     * 38. Google callback cannot create TMP member
     */
    public function test_38_google_callback_cannot_create_tmp_member(): void
    {
        $this->mockSocialite(googleId: 'google-uid-38', email: 'stranger@example.com', verified: true);

        $this->get(route('auth.google.callback'));

        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'like', 'TMP%')->count());
        $this->assertSame(0, CooperativeMember::query()->where('no_anggota', 'like', 'TMP%')->count());
    }

    /**
     * 39. existing staff/admin provider-id login still works
     */
    public function test_39_existing_staff_admin_provider_id_login_still_works(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'staff@example.com',
            'organization_id' => $this->organization->id,
        ]);
        $staffUser->assignRole('Admin Koperasi');

        SocialAccount::factory()->create([
            'user_id' => $staffUser->id,
            'provider' => 'google',
            'provider_id' => 'staff-google-uid-39',
            'provider_email' => 'staff@example.com',
        ]);

        // Staff does NOT have a CooperativeMember record
        $this->assertNull($staffUser->cooperativeMember);

        $this->mockSocialite(googleId: 'staff-google-uid-39', email: 'staff@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect();

        $this->assertAuthenticatedAs($staffUser);
    }

    /**
     * 40. no ONB-08 activation behavior introduced
     */
    public function test_40_no_onb_08_activation_behavior_introduced(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'no.activation@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->mockSocialite(googleId: 'google-uid-40', email: 'no.activation@example.com', verified: true);

        $this->get(route('auth.google.callback'))->assertRedirect(route('member.onboarding'));

        // Member status must NOT become ACTIVE automatically
        $this->assertNotSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->status);
    }

    /**
     * 41. match result DTO returns correct result code ('login_existing' vs 'login_linked')
     */
    public function test_41_match_result_dto_returns_correct_result_codes(): void
    {
        $matchingService = app(\App\Services\Auth\Sso\MemberGoogleSsoMatchingService::class);

        // Case A: First-time match
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
            'email' => 'dto.first@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $firstTimeUser = $this->fakeSocialiteUser('dto-first-uid', 'dto.first@example.com', true);
        $resultFirst = $matchingService->resolve($firstTimeUser);

        $this->assertTrue($resultFirst->success);
        $this->assertSame('login_linked', $resultFirst->resultCode);
        $this->assertSame('login_linked', $resultFirst->toArray()['result']);

        // Case B: Existing login
        $existingUser = $this->fakeSocialiteUser('dto-first-uid', 'dto.first@example.com', false);
        $resultExisting = $matchingService->resolve($existingUser);

        $this->assertTrue($resultExisting->success);
        $this->assertSame('login_existing', $resultExisting->resultCode);
        $this->assertSame('login_existing', $resultExisting->toArray()['result']);
    }

    private function mockSocialite(string $googleId, string $email, bool $verified, ?string $name = null): void
    {
        $abstract = Mockery::mock(Provider::class);
        $abstract->shouldReceive('user')->andReturn($this->fakeSocialiteUser($googleId, $email, $verified, $name));
        $abstract->shouldReceive('redirect')->andReturn(redirect('/fake-google-oauth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($abstract);
    }

    private function fakeSocialiteUser(string $id, string $email, bool $verified, ?string $name = null): \Laravel\Socialite\Two\User
    {
        $user = new \Laravel\Socialite\Two\User;
        $user->id = $id;
        $user->nickname = null;
        $user->name = $name ?? ('Tester '.$id);
        $user->email = $email;
        $user->avatar = null;
        $user->user = [
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'name' => $name ?? ('Tester '.$id),
        ];
        $user->token = Str::random(40);
        $user->refreshToken = null;
        $user->expiresIn = 3600;
        $user->attributes['token_type'] = 'Bearer';

        return $user;
    }

    protected function tearDown(): void
    {
        SocialAccount::flushEventListeners();
        Mockery::close();
        parent::tearDown();
    }
}
