<?php

namespace Tests\Feature;

use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\CooperativePersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class CooperativePersonaSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $expectedEmails = [
        'seed.system.admin@kojaya.test',
        'seed.pengurus@kojaya.test',
        'seed.manajer@kojaya.test',
        'seed.admin.kop@kojaya.test',
        'seed.kasir@kojaya.test',
        'seed.member.waiting@kojaya.test',
        'seed.member.review@kojaya.test',
        'seed.member.revision@kojaya.test',
        'seed.member.rejected@kojaya.test',
        'seed.member.active@kojaya.test',
        'seed.member.google@kojaya.test',
        'seed.member.no-google@kojaya.test',
    ];

    /**
     * Scenario A: Exactly 12 canonical user personas exist (seed.*@kojaya.test).
     */
    public function test_scenario_a_exactly_twelve_canonical_user_personas_exist(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $users = User::query()->orderBy('id')->get();
        $this->assertCount(12, $users, 'Exactly 12 canonical user personas must be seeded.');

        $emails = $users->pluck('email')->all();
        sort($emails);
        $expected = $this->expectedEmails;
        sort($expected);

        $this->assertSame($expected, $emails);

        foreach ($users as $user) {
            $this->assertStringStartsWith('seed.', $user->email);
            $this->assertStringEndsWith('@kojaya.test', $user->email);
            $this->assertNotNull($user->email_verified_at);
        }
    }

    /**
     * Scenario B: Role mapping for P01-P05 staff and P06-P10, P12-P13 members.
     */
    public function test_scenario_b_role_mapping_for_all_personas(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $roleExpectations = [
            'seed.system.admin@kojaya.test' => 'System Admin',
            'seed.pengurus@kojaya.test' => 'Pengurus Koperasi',
            'seed.manajer@kojaya.test' => 'Manajer Koperasi',
            'seed.admin.kop@kojaya.test' => 'Admin Koperasi',
            'seed.kasir@kojaya.test' => 'Kasir Koperasi',
            'seed.member.waiting@kojaya.test' => 'Anggota',
            'seed.member.review@kojaya.test' => 'Anggota',
            'seed.member.revision@kojaya.test' => 'Anggota',
            'seed.member.rejected@kojaya.test' => 'Anggota',
            'seed.member.active@kojaya.test' => 'Anggota',
            'seed.member.google@kojaya.test' => 'Anggota',
            'seed.member.no-google@kojaya.test' => 'Anggota',
        ];

        foreach ($roleExpectations as $email => $expectedRole) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue($user->hasRole($expectedRole), "User {$email} must have role {$expectedRole}.");
            $this->assertCount(1, $user->roles, "User {$email} must have exactly 1 role assigned.");
        }
    }

    /**
     * Scenario C: All 12 personas belong strictly to KOP-001 (Cooperative Legal Entity).
     */
    public function test_scenario_c_all_twelve_personas_belong_to_kop_001(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();

        $users = User::all();
        $this->assertCount(12, $users);

        foreach ($users as $user) {
            $this->assertSame($kop->id, $user->organization_id, "User {$user->email} must belong to KOP-001.");
        }

        $members = CooperativeMember::all();
        $this->assertCount(7, $members);

        foreach ($members as $member) {
            $this->assertSame($kop->id, $member->organization_id, "Member {$member->member_no} must belong to KOP-001.");
        }
    }

    /**
     * Scenario D: Exactly 7 members exist (only for P06-P10, P12, P13; zero for P01-P05).
     */
    public function test_scenario_d_exactly_seven_members_exist_only_for_member_personas(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $this->assertSame(7, CooperativeMember::query()->count());

        $staffEmails = [
            'seed.system.admin@kojaya.test',
            'seed.pengurus@kojaya.test',
            'seed.manajer@kojaya.test',
            'seed.admin.kop@kojaya.test',
            'seed.kasir@kojaya.test',
        ];

        foreach ($staffEmails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertNull($user->cooperativeMember, "Staff user {$email} must not have a CooperativeMember record.");
            $this->assertDatabaseMissing('cooperative_members', ['user_id' => $user->id]);
        }

        $memberEmails = [
            'seed.member.waiting@kojaya.test',
            'seed.member.review@kojaya.test',
            'seed.member.revision@kojaya.test',
            'seed.member.rejected@kojaya.test',
            'seed.member.active@kojaya.test',
            'seed.member.google@kojaya.test',
            'seed.member.no-google@kojaya.test',
        ];

        foreach ($memberEmails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertNotNull($user->cooperativeMember, "Member user {$email} must have a CooperativeMember record.");
        }
    }

    /**
     * Scenario E: Derived lifecycle experience mapping matches canonical states.
     */
    public function test_scenario_e_lifecycle_mapping_matches_canonical_states(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $lifecycleMap = [
            'DEV-KOP-006' => MemberLifecycleExperience::WaitingVerification,
            'DEV-KOP-007' => MemberLifecycleExperience::UnderReview,
            'DEV-KOP-008' => MemberLifecycleExperience::RevisionRequired,
            'DEV-KOP-009' => MemberLifecycleExperience::Rejected,
            'DEV-KOP-010' => MemberLifecycleExperience::Active,
            'DEV-KOP-012' => MemberLifecycleExperience::Active,
            'DEV-KOP-013' => MemberLifecycleExperience::Active,
        ];

        foreach ($lifecycleMap as $memberNo => $expectedExperience) {
            $member = CooperativeMember::query()->where('member_no', $memberNo)->firstOrFail();
            $resolved = MemberLifecycleExperience::fromMember($member);
            $this->assertSame(
                $expectedExperience,
                $resolved,
                "Member {$memberNo} lifecycle should resolve to {$expectedExperience->value}, got {$resolved->value}",
            );
        }
    }

    /**
     * Scenario F: Reserved personas (P11, P14, P15) and namespaces are absent.
     */
    public function test_scenario_f_reserved_personas_and_namespaces_are_absent(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        // P11 BLOCKED_UNKNOWN check
        $this->assertDatabaseMissing('cooperative_members', ['member_no' => 'DEV-KOP-011']);
        $this->assertDatabaseMissing('cooperative_members', ['no_anggota' => 'DEV-KOP-011']);
        $this->assertDatabaseMissing('users', ['email' => 'seed.member.blocked@kojaya.test']);

        // P14 and P15 reserved checks
        $this->assertSame(0, User::query()->where('email', 'like', '%014%')->count());
        $this->assertSame(0, User::query()->where('email', 'like', '%015%')->count());

        // No DEV-KBU-* member namespace
        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'like', 'DEV-KBU-%')->count());
        $this->assertSame(0, CooperativeMember::query()->where('no_anggota', 'like', 'DEV-KBU-%')->count());
    }

    /**
     * Scenario G: KBU-001 (PT Anak Usaha) has strictly 0 CooperativeMembers.
     */
    public function test_scenario_g_kbu_001_has_zero_cooperative_members(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $this->assertSame(
            0,
            CooperativeMember::query()->where('organization_id', $kbu->id)->count(),
            'KBU-001 subsidiary company must have strictly 0 CooperativeMembers.',
        );
    }

    /**
     * Scenario H: ISO-999 has strictly 0 default personas and members.
     */
    public function test_scenario_h_iso_999_has_zero_default_users_and_members(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $iso = Organization::query()->where('code', 'ISO-999')->first();
        if ($iso) {
            $this->assertSame(0, User::query()->where('organization_id', $iso->id)->count());
            $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());
        } else {
            $this->assertNull($iso, 'ISO-999 is an edge test fixture and must not be seeded by default.');
        }
    }

    /**
     * Scenario I: P12 Google SocialAccount deterministic contract.
     */
    public function test_scenario_i_p12_google_social_account_contract(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $user12 = User::query()->where('email', 'seed.member.google@kojaya.test')->firstOrFail();
        $member12 = CooperativeMember::query()->where('user_id', $user12->id)->firstOrFail();

        $socialAccounts = $user12->socialAccounts;
        $this->assertCount(1, $socialAccounts, 'P12 must have exactly 1 SocialAccount.');

        $account = $socialAccounts->first();
        $this->assertSame('google', $account->provider);
        $this->assertSame('google-seed-sub-012', $account->provider_id);
        $this->assertSame('seed.member.google@kojaya.test', $account->provider_email);
        $this->assertNotNull($account->linked_at, 'linked_at must be set to deterministic timestamp.');
        $this->assertNull($account->last_login_at, 'last_login_at must remain null.');
        $this->assertNull($account->access_token, 'access_token must remain null.');
        $this->assertNull($account->refresh_token, 'refresh_token must remain null.');

        $this->assertSame('google', $member12->sso_provider);
        $this->assertNull($member12->last_sso_login_at, 'member.last_sso_login_at must remain null.');
    }

    /**
     * Scenario J: P13 has strictly zero SocialAccounts.
     */
    public function test_scenario_j_p13_has_zero_social_accounts(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $user13 = User::query()->where('email', 'seed.member.no-google@kojaya.test')->firstOrFail();
        $member13 = CooperativeMember::query()->where('user_id', $user13->id)->firstOrFail();

        $this->assertCount(0, $user13->socialAccounts, 'P13 must have 0 SocialAccounts.');
        $this->assertNull($member13->sso_provider);
        $this->assertNull($member13->last_sso_login_at);
    }

    /**
     * Scenario K: Password 'password' works via Hash::check for personas across roles.
     */
    public function test_scenario_k_password_hash_check_succeeds_for_personas(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $checkEmails = [
            'seed.system.admin@kojaya.test',
            'seed.admin.kop@kojaya.test',
            'seed.member.active@kojaya.test',
            'seed.member.google@kojaya.test',
        ];

        foreach ($checkEmails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue(
                Hash::check('password', $user->password),
                "Password check failed for persona {$email}",
            );
        }
    }

    /**
     * Scenario L: Seeder is fully idempotent across multiple runs.
     */
    public function test_scenario_l_seeder_is_idempotent(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $firstRunCounts = [
            'users' => User::query()->count(),
            'members' => CooperativeMember::query()->count(),
            'social_accounts' => SocialAccount::query()->count(),
        ];

        $this->seed(CooperativePersonaSeeder::class);

        $secondRunCounts = [
            'users' => User::query()->count(),
            'members' => CooperativeMember::query()->count(),
            'social_accounts' => SocialAccount::query()->count(),
        ];

        $this->assertSame($firstRunCounts, $secondRunCounts, 'Rerunning CooperativePersonaSeeder must be idempotent.');
    }

    /**
     * Scenario M: Soft-deleted member is restored without duplicates.
     */
    public function test_scenario_m_soft_deleted_member_is_restored_without_duplicates(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $member10 = CooperativeMember::query()->where('member_no', 'DEV-KOP-010')->firstOrFail();
        $member10->delete();

        $this->assertSoftDeleted('cooperative_members', ['id' => $member10->id]);
        $this->assertSame(6, CooperativeMember::query()->count());

        $this->seed(CooperativePersonaSeeder::class);

        $this->assertSame(7, CooperativeMember::query()->count());
        $this->assertSame(7, CooperativeMember::withTrashed()->count());

        $member10->refresh();
        $this->assertFalse($member10->trashed(), 'Soft-deleted member must be restored.');
    }

    /**
     * Scenario N: Fail-closed guard refuses execution in non-whitelisted environments.
     */
    public function test_scenario_n_fail_closed_guard_in_unauthorized_environments(): void
    {
        foreach (['production', 'staging', 'qa', 'development'] as $environment) {
            config(['app.env' => $environment]);

            $thrown = false;
            try {
                (new CooperativePersonaSeeder)->run();
            } catch (LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('is only available in local, testing, or playwright environments', $exception->getMessage());
            }

            $this->assertTrue($thrown, "Expected CooperativePersonaSeeder to throw LogicException in {$environment}.");
            $this->assertSame(0, User::query()->count(), "Zero users must be created when seeder fails in {$environment}.");
            $this->assertSame(0, CooperativeMember::query()->count(), "Zero members must be created when seeder fails in {$environment}.");
            $this->assertSame(0, SocialAccount::query()->count(), "Zero social accounts must be created when seeder fails in {$environment}.");
        }
    }

    /**
     * Web login verification for Admin Koperasi (P04) and Active Member (P10).
     */
    public function test_web_fortify_login_for_admin_koperasi_and_active_member(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        // 1. Staff: Admin Koperasi (P04)
        $responseAdmin = $this->post('/login', [
            'email' => 'seed.admin.kop@kojaya.test',
            'password' => 'password',
        ]);
        $responseAdmin->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::query()->where('email', 'seed.admin.kop@kojaya.test')->firstOrFail());

        $this->post('/logout');
        $this->assertGuest();

        // 2. Member: Active Member (P10)
        $responseMember = $this->post('/login', [
            'email' => 'seed.member.active@kojaya.test',
            'password' => 'password',
        ]);
        $responseMember->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail());
    }

    /**
     * Mobile API login verification for Active Member (P10).
     */
    public function test_mobile_api_login_for_active_member(): void
    {
        $this->seed(CooperativePersonaSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'seed.member.active@kojaya.test',
            'password' => 'password',
            'device_name' => 'Pixel 8 Pro Test Device',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'cooperative_member_id',
            ],
            'member_status',
            'validation_status',
            'lifecycle_experience',
        ]);

        $user10 = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $this->assertSame('seed.member.active@kojaya.test', $response->json('user.email'));
        $this->assertSame('ACTIVE', $response->json('member_status'));
        $this->assertSame('ACTIVE', $response->json('validation_status'));
        $this->assertSame('ACTIVE', $response->json('lifecycle_experience'));
        $this->assertSame($user10->cooperativeMember->id, $response->json('user.cooperative_member_id'));
    }
}
