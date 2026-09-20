<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\CooperativeMemberLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeMemberLifecycleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario A: Direct execution bootstraps SEED-03 dependency automatically.
     */
    public function test_scenario_a_direct_bootstrap_establishes_seed_03_dependency(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $this->assertSame(12, User::query()->count(), 'Exactly 12 canonical user personas must exist.');
        $this->assertSame(7, CooperativeMember::query()->count(), 'Exactly 7 canonical member personas must exist.');

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $this->assertSame(7, CooperativeMember::query()->where('organization_id', $kop->id)->count());
    }

    /**
     * Scenario B: Exact lifecycle matrix resolves canonical lifecycle experiences.
     */
    public function test_scenario_b_exact_lifecycle_matrix_resolves_all_canonical_experiences(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $expectedMatrix = [
            'DEV-KOP-006' => [
                'experience' => MemberLifecycleExperience::WaitingVerification,
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
                'review_state' => 'pending',
            ],
            'DEV-KOP-007' => [
                'experience' => MemberLifecycleExperience::UnderReview,
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'review_state' => 'review',
            ],
            'DEV-KOP-008' => [
                'experience' => MemberLifecycleExperience::RevisionRequired,
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
                'review_state' => 'revision',
            ],
            'DEV-KOP-009' => [
                'experience' => MemberLifecycleExperience::Rejected,
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
                'review_state' => 'rejected',
            ],
            'DEV-KOP-010' => [
                'experience' => MemberLifecycleExperience::Active,
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'review_state' => 'approved',
            ],
            'DEV-KOP-012' => [
                'experience' => MemberLifecycleExperience::Active,
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'review_state' => 'approved',
            ],
            'DEV-KOP-013' => [
                'experience' => MemberLifecycleExperience::Active,
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                'review_state' => 'approved',
            ],
        ];

        foreach ($expectedMatrix as $memberNo => $expected) {
            $member = CooperativeMember::query()->where('member_no', $memberNo)->firstOrFail();
            $resolved = MemberLifecycleExperience::fromMember($member);

            $this->assertSame($expected['experience'], $resolved, "Member {$memberNo} experience mismatch.");
            $this->assertSame($expected['status'], $member->status, "Member {$memberNo} status mismatch.");
            $this->assertSame($expected['validation_status'], $member->validation_status, "Member {$memberNo} validation_status mismatch.");
            $this->assertSame($expected['review_state'], $resolved->reviewState(), "Member {$memberNo} review_state mismatch.");
        }
    }

    /**
     * Scenario C: Review metadata correctly persists admin review, pengurus approval, revisions, and rejections.
     */
    public function test_scenario_c_review_metadata_matches_canonical_workflow(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $adminKop = User::query()->where('email', 'seed.admin.kop@kojaya.test')->firstOrFail();
        $pengurus = User::query()->where('email', 'seed.pengurus@kojaya.test')->firstOrFail();

        // 1. P06: Waiting verification (no admin or final approval)
        $m06 = CooperativeMember::query()->where('member_no', 'DEV-KOP-006')->firstOrFail();
        $this->assertNull($m06->admin_validated_at);
        $this->assertNull($m06->admin_validated_by);
        $this->assertNull($m06->admin_validation_notes);
        $this->assertNull($m06->validated_at);
        $this->assertNull($m06->validated_by);
        $this->assertNull($m06->validation_notes);
        $this->assertNull($m06->tanggal_aktif);

        // 2. P07: Under review (Admin verification present, final Pengurus approval absent)
        $m07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $this->assertNotNull($m07->admin_validated_at);
        $this->assertSame($adminKop->id, $m07->admin_validated_by);
        $this->assertNotNull($m07->admin_validation_notes);
        $this->assertStringContainsString('Admin Koperasi', $m07->admin_validation_notes);
        $this->assertNull($m07->validated_at);
        $this->assertNull($m07->validated_by);
        $this->assertNull($m07->validation_notes);
        $this->assertNull($m07->tanggal_aktif);

        // 3. P08: Revision required (Admin verification preserved, revision note exists, no active date)
        $m08 = CooperativeMember::query()->where('member_no', 'DEV-KOP-008')->firstOrFail();
        $this->assertNotNull($m08->admin_validated_at);
        $this->assertSame($adminKop->id, $m08->admin_validated_by);
        $this->assertNotNull($m08->admin_validation_notes);
        $this->assertNotNull($m08->validated_at);
        $this->assertSame($adminKop->id, $m08->validated_by);
        $this->assertTrue($m08->validated_at->gt($m08->admin_validated_at));
        $this->assertNotNull($m08->validation_notes);
        $this->assertStringContainsString('Perbarui', $m08->validation_notes);
        $this->assertNull($m08->tanggal_aktif);

        // 4. P09: Rejected (Admin verification preserved, rejection note exists, maker-checker preserved, no active date)
        $m09 = CooperativeMember::query()->where('member_no', 'DEV-KOP-009')->firstOrFail();
        $this->assertNotNull($m09->admin_validated_at);
        $this->assertSame($adminKop->id, $m09->admin_validated_by);
        $this->assertNotNull($m09->admin_validation_notes);
        $this->assertNotNull($m09->validated_at);
        $this->assertSame($pengurus->id, $m09->validated_by);
        $this->assertTrue($m09->validated_at->gt($m09->admin_validated_at));
        $this->assertNotSame($m09->admin_validated_by, $m09->validated_by);
        $this->assertNotNull($m09->validation_notes);
        $this->assertStringContainsString('tidak memenuhi', $m09->validation_notes);
        $this->assertNull($m09->tanggal_aktif);

        // 5. P10, P12, P13: Active (Admin verification + Pengurus final approval present)
        foreach (['DEV-KOP-010', 'DEV-KOP-012', 'DEV-KOP-013'] as $activeNo) {
            $m = CooperativeMember::query()->where('member_no', $activeNo)->firstOrFail();
            $this->assertSame('2026-06-01', $m->tanggal_aktif?->toDateString());
            $this->assertNotNull($m->admin_validated_at);
            $this->assertSame($adminKop->id, $m->admin_validated_by);
            $this->assertNotNull($m->validated_at);
            $this->assertSame($pengurus->id, $m->validated_by);
            $this->assertNotNull($m->validation_notes);
        }
    }

    /**
     * Scenario D: Lifecycle UI behavior on GET member.onboarding.
     */
    public function test_scenario_d_lifecycle_ui_behavior_on_onboarding_route(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        // P06: Waiting verification
        $u06 = User::query()->where('email', 'seed.member.waiting@kojaya.test')->firstOrFail();
        $this->actingAs($u06)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', 'WAITING_VERIFICATION')
                ->where('review_state', 'pending')
                ->where('can_edit_safe_profile', true)
            );

        // P07: Under review
        $u07 = User::query()->where('email', 'seed.member.review@kojaya.test')->firstOrFail();
        $this->actingAs($u07)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', 'UNDER_REVIEW')
                ->where('review_state', 'review')
                ->where('can_edit_safe_profile', false)
            );

        // P08: Revision required
        $u08 = User::query()->where('email', 'seed.member.revision@kojaya.test')->firstOrFail();
        $this->actingAs($u08)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', 'REVISION_REQUIRED')
                ->where('review_state', 'revision')
                ->where('can_edit_safe_profile', true)
            );

        // P09: Rejected
        $u09 = User::query()->where('email', 'seed.member.rejected@kojaya.test')->firstOrFail();
        $this->actingAs($u09)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', 'REJECTED')
                ->where('review_state', 'rejected')
                ->where('can_edit_safe_profile', false)
            );

        // P10: Active -> redirects to member.dashboard
        $u10 = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $this->actingAs($u10)
            ->get(route('member.onboarding'))
            ->assertRedirect(route('member.dashboard'));
    }

    /**
     * Scenario E: Safe profile edit matrix via POST member.onboarding.submit.
     */
    public function test_scenario_e_safe_profile_edit_matrix_enforces_lifecycle_immutability(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        // 1. P06: WAITING_VERIFICATION -> Allowed safe profile edit
        $u06 = User::query()->where('email', 'seed.member.waiting@kojaya.test')->firstOrFail();
        $m06 = $u06->cooperativeMember;
        $this->actingAs($u06)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Seed Member Waiting Updated',
                'phone' => '081299990006',
            ])
            ->assertRedirect(route('member.onboarding'));

        $m06->refresh();
        $this->assertSame('Seed Member Waiting Updated', $m06->name);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $m06->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $m06->validation_status);

        // 2. P07: UNDER_REVIEW -> Read-only, POST forbidden (403)
        $u07 = User::query()->where('email', 'seed.member.review@kojaya.test')->firstOrFail();
        $this->actingAs($u07)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Attempted Under Review Mutation',
                'phone' => '081299990007',
            ])
            ->assertForbidden();

        // 3. P08: REVISION_REQUIRED -> Allowed safe profile edit, does NOT self-promote lifecycle status
        $u08 = User::query()->where('email', 'seed.member.revision@kojaya.test')->firstOrFail();
        $m08 = $u08->cooperativeMember;
        $this->actingAs($u08)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Seed Member Revision Resubmitted',
                'phone' => '081299990008',
            ])
            ->assertRedirect(route('member.onboarding'));

        $m08->refresh();
        $this->assertSame('Seed Member Revision Resubmitted', $m08->name);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $m08->status);
        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $m08->validation_status);

        // 4. P09: REJECTED -> Read-only, POST forbidden (403)
        $u09 = User::query()->where('email', 'seed.member.rejected@kojaya.test')->firstOrFail();
        $this->actingAs($u09)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Attempted Rejected Mutation',
                'phone' => '081299990009',
            ])
            ->assertForbidden();

        // 5. P10: ACTIVE -> POST forbidden (403: 'Anggota aktif tidak dapat mengajukan onboarding.')
        $u10 = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $this->actingAs($u10)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Active Member Mutation Attempt',
            ])
            ->assertForbidden();
    }

    /**
     * Scenario F: Financial gate matrix denies non-active members and permits active members.
     */
    public function test_scenario_f_financial_gate_matrix_denies_non_active_and_permits_active(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $nonActiveEmails = [
            'seed.member.waiting@kojaya.test',
            'seed.member.review@kojaya.test',
            'seed.member.revision@kojaya.test',
            'seed.member.rejected@kojaya.test',
        ];

        foreach ($nonActiveEmails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();

            // Web gate: redirect to member.onboarding with warning
            $this->actingAs($user)
                ->get(route('member.store-account'))
                ->assertRedirect(route('member.onboarding'))
                ->assertSessionHas('warning');

            // API gate: 403 Forbidden with MemberNotActive error code
            Sanctum::actingAs($user, ['member:write']);
            $this->postJson('/api/payments/charge', [])
                ->assertStatus(403)
                ->assertJson(['error_code' => ApiErrorCode::MemberNotActive->value]);
        }

        // P10 ACTIVE: permitted to access active web route
        $u10 = User::query()->where('email', 'seed.member.active@kojaya.test')->firstOrFail();
        $this->actingAs($u10)
            ->get(route('member.store-account'))
            ->assertOk();
    }

    /**
     * Scenario G: API login metadata returns lifecycle experience and onboarding next step.
     */
    public function test_scenario_g_api_login_returns_correct_lifecycle_experience_metadata(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $cases = [
            'seed.member.waiting@kojaya.test' => [
                'lifecycle_experience' => 'WAITING_VERIFICATION',
                'onboarding_next_step' => 'waiting_admin_acceptance',
                'member_status' => 'PENDING',
                'validation_status' => 'PENDING',
            ],
            'seed.member.review@kojaya.test' => [
                'lifecycle_experience' => 'UNDER_REVIEW',
                'onboarding_next_step' => 'waiting_final_approval',
                'member_status' => 'PENDING',
                'validation_status' => 'PENDING_VALIDATION',
            ],
            'seed.member.revision@kojaya.test' => [
                'lifecycle_experience' => 'REVISION_REQUIRED',
                'onboarding_next_step' => 'revision_required',
                'member_status' => 'INACTIVE',
                'validation_status' => 'REVISION',
            ],
            'seed.member.rejected@kojaya.test' => [
                'lifecycle_experience' => 'REJECTED',
                'onboarding_next_step' => 'rejected',
                'member_status' => 'INACTIVE',
                'validation_status' => 'REJECTED',
            ],
            'seed.member.active@kojaya.test' => [
                'lifecycle_experience' => 'ACTIVE',
                'onboarding_next_step' => 'dashboard',
                'member_status' => 'ACTIVE',
                'validation_status' => 'ACTIVE',
            ],
        ];

        foreach ($cases as $email => $expected) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $email,
                'password' => 'password',
                'app' => 'member',
            ]);

            $response->assertOk();
            $response->assertJson([
                'lifecycle_experience' => $expected['lifecycle_experience'],
                'onboarding_next_step' => $expected['onboarding_next_step'],
                'member_status' => $expected['member_status'],
                'validation_status' => $expected['validation_status'],
            ]);
        }
    }

    /**
     * Scenario H: blockedUnknown() factory state resolves to BlockedUnknown.
     */
    public function test_scenario_h_blocked_unknown_factory_state_resolves_correctly(): void
    {
        $member = CooperativeMember::factory()->blockedUnknown()->make();

        $experience = MemberLifecycleExperience::fromMember($member);
        $this->assertSame(MemberLifecycleExperience::BlockedUnknown, $experience);
        $this->assertSame('blocked', $experience->reviewState());
        $this->assertTrue($experience->isBlocked());
        $this->assertFalse($experience->isActive());
        $this->assertFalse($experience->isNonActiveLifecycle());
    }

    /**
     * Scenario I: Blocked factory state fails closed on web and mobile API.
     */
    public function test_scenario_i_blocked_factory_state_fails_closed(): void
    {
        Role::firstOrCreate(['name' => 'Anggota']);

        $user = User::factory()->create(['password' => Hash::make('secret123')]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->blockedUnknown()->create(['user_id' => $user->id]);

        // 1. Web entry -> 403 Forbidden
        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertForbidden();

        // 2. Mobile API login -> 403 Forbidden with zero tokens issued
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);

        $response->assertForbidden();
        $response->assertJsonMissing(['token', 'token_type']);
        $response->assertJson([
            'success' => false,
            'error_code' => ApiErrorCode::MemberNotActive->value,
            'data' => [
                'lifecycle_experience' => MemberLifecycleExperience::BlockedUnknown->value,
            ],
        ]);

        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $user->id)->count());
    }

    /**
     * Scenario J: P11 remains absent from normal lifecycle seeder baseline.
     */
    public function test_scenario_j_p11_remains_absent_from_lifecycle_seeder(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $this->assertDatabaseMissing('cooperative_members', ['member_no' => 'DEV-KOP-011']);
        $this->assertDatabaseMissing('users', ['email' => 'seed.member.blocked@kojaya.test']);

        $members = CooperativeMember::all();
        foreach ($members as $member) {
            $this->assertNotSame(
                MemberLifecycleExperience::BlockedUnknown,
                MemberLifecycleExperience::fromMember($member),
                'Baseline seeder must never produce a BLOCKED_UNKNOWN member.',
            );
        }
    }

    /**
     * Scenario K: Seeder is idempotent across multiple runs.
     */
    public function test_scenario_k_seeder_is_idempotent(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $firstRun = [
            'users' => User::query()->count(),
            'members' => CooperativeMember::query()->count(),
            'social' => SocialAccount::query()->count(),
        ];

        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $secondRun = [
            'users' => User::query()->count(),
            'members' => CooperativeMember::query()->count(),
            'social' => SocialAccount::query()->count(),
        ];

        $this->assertSame($firstRun, $secondRun, 'Rerunning CooperativeMemberLifecycleSeeder must be idempotent.');
    }

    /**
     * Scenario L: Re-running seeder repairs altered seed-owned lifecycle records.
     */
    public function test_scenario_l_rerun_repairs_altered_seed_owned_lifecycle_records(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        // Tamper with P07
        $m07 = CooperativeMember::query()->where('member_no', 'DEV-KOP-007')->firstOrFail();
        $m07->forceFill([
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
            'admin_validated_by' => null,
            'admin_validated_at' => null,
        ])->save();

        $this->assertSame(MemberLifecycleExperience::RevisionRequired, MemberLifecycleExperience::fromMember($m07->fresh()));

        // Re-run seeder to repair
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $m07->refresh();
        $this->assertSame(MemberLifecycleExperience::UnderReview, MemberLifecycleExperience::fromMember($m07));
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $m07->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $m07->validation_status);
        $this->assertNotNull($m07->admin_validated_by);
    }

    /**
     * Scenario M: Fail-closed guard refuses execution in unauthorized environments.
     */
    public function test_scenario_m_fail_closed_guard_in_unauthorized_environments(): void
    {
        foreach (['production', 'staging', 'qa', 'development'] as $environment) {
            config(['app.env' => $environment]);

            $thrown = false;
            try {
                (new CooperativeMemberLifecycleSeeder)->run();
            } catch (LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('is only available in local, testing, or playwright environments', $exception->getMessage());
            }

            $this->assertTrue($thrown, "Expected CooperativeMemberLifecycleSeeder to throw LogicException in {$environment}.");
            $this->assertSame(0, User::query()->count());
            $this->assertSame(0, CooperativeMember::query()->count());
        }
    }

    /**
     * Scenario N: Zero financial fixture side effects created by SEED-04.
     */
    public function test_scenario_n_zero_financial_fixture_side_effects(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $this->assertSame(0, MemberStoreAccount::query()->count(), 'SEED-04 must not create MemberStoreAccount.');
        $this->assertSame(0, Loan::query()->count(), 'SEED-04 must not create Loan.');
        $this->assertSame(0, CooperativeDuesInvoice::query()->count(), 'SEED-04 must not create CooperativeDuesInvoice.');
        $this->assertSame(0, CooperativePayment::query()->count(), 'SEED-04 must not create CooperativePayment.');
        $this->assertSame(0, CooperativeLedgerEntry::query()->count(), 'SEED-04 must not create CooperativeLedgerEntry.');
        $this->assertSame(0, PosTransaction::query()->count(), 'SEED-04 must not create PosTransaction.');
        $this->assertSame(0, PersonalAccessToken::query()->count(), 'SEED-04 must not create PersonalAccessToken.');

        // Verify credit fields remain at database defaults
        foreach (CooperativeMember::all() as $member) {
            $this->assertSame(0.0, (float) $member->credit_limit);
            $this->assertSame(30, (int) $member->credit_term_days);
        }
    }

    /**
     * Scenario O: Organization integrity guarantees all lifecycle members belong to KOP-001.
     */
    public function test_scenario_o_organization_integrity(): void
    {
        $this->seed(CooperativeMemberLifecycleSeeder::class);

        $kop = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $kbu = Organization::query()->where('code', 'KBU-001')->firstOrFail();
        $iso = Organization::query()->where('code', 'ISO-999')->firstOrFail();

        $this->assertSame(7, CooperativeMember::query()->where('organization_id', $kop->id)->count());
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $kbu->id)->count());
        $this->assertSame(0, CooperativeMember::query()->where('organization_id', $iso->id)->count());

        $this->assertSame(0, CooperativeMember::query()->where('member_no', 'like', 'DEV-KBU-%')->count());
        $this->assertSame(0, CooperativeMember::query()->where('no_anggota', 'like', 'DEV-KBU-%')->count());
    }
}
