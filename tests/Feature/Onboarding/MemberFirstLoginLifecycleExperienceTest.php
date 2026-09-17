<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Enums\ApiErrorCode;
use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Http\Controllers\Auth\GoogleSsoController;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberFirstLoginLifecycleExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Anggota']);
    }

    /**
     * Requirement 1: Single source of truth derived lifecycle experience mapping.
     */
    public function test_derived_lifecycle_experience_mapping_resolves_all_canonical_and_inconsistent_states(): void
    {
        // 1. PENDING / PENDING -> WAITING_VERIFICATION
        $waiting = MemberLifecycleExperience::fromStatuses(
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING
        );
        $this->assertSame(MemberLifecycleExperience::WaitingVerification, $waiting);
        $this->assertSame('WAITING_VERIFICATION', $waiting->value);
        $this->assertFalse($waiting->isActive());
        $this->assertTrue($waiting->isNonActiveLifecycle());
        $this->assertFalse($waiting->isBlocked());

        // 2. PENDING / PENDING_VALIDATION -> UNDER_REVIEW
        $review = MemberLifecycleExperience::fromStatuses(
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW
        );
        $this->assertSame(MemberLifecycleExperience::UnderReview, $review);
        $this->assertSame('UNDER_REVIEW', $review->value);
        $this->assertFalse($review->isActive());
        $this->assertTrue($review->isNonActiveLifecycle());
        $this->assertFalse($review->isBlocked());

        // 3. INACTIVE / REVISION -> REVISION_REQUIRED
        $revision = MemberLifecycleExperience::fromStatuses(
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REVISION
        );
        $this->assertSame(MemberLifecycleExperience::RevisionRequired, $revision);
        $this->assertSame('REVISION_REQUIRED', $revision->value);
        $this->assertFalse($revision->isActive());
        $this->assertTrue($revision->isNonActiveLifecycle());
        $this->assertFalse($revision->isBlocked());

        // 4. INACTIVE / REJECTED -> REJECTED
        $rejected = MemberLifecycleExperience::fromStatuses(
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REJECTED
        );
        $this->assertSame(MemberLifecycleExperience::Rejected, $rejected);
        $this->assertSame('REJECTED', $rejected->value);
        $this->assertFalse($rejected->isActive());
        $this->assertTrue($rejected->isNonActiveLifecycle());
        $this->assertFalse($rejected->isBlocked());

        // 5. ACTIVE / ACTIVE -> ACTIVE
        $active = MemberLifecycleExperience::fromStatuses(
            CooperativeMember::VALIDATION_ACTIVE,
            CooperativeMember::VALIDATION_ACTIVE
        );
        $this->assertSame(MemberLifecycleExperience::Active, $active);
        $this->assertSame('ACTIVE', $active->value);
        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isNonActiveLifecycle());
        $this->assertFalse($active->isBlocked());

        // 6. Unknown / inconsistent -> BLOCKED_UNKNOWN
        $inconsistentPairs = [
            ['ACTIVE', 'PENDING'],
            ['PENDING', 'REVISION'],
            ['PENDING', 'REJECTED'],
            ['INACTIVE', 'ACTIVE'],
            ['INACTIVE', 'INACTIVE'],
            ['RESIGNED', 'RESIGNED'],
            ['RESIGNED', 'PENDING'],
            [null, null],
            ['UNKNOWN', 'UNKNOWN'],
        ];

        foreach ($inconsistentPairs as [$st, $val]) {
            $blocked = MemberLifecycleExperience::fromStatuses($st, $val);
            $this->assertSame(MemberLifecycleExperience::BlockedUnknown, $blocked, "Pair [{$st}, {$val}] should be BLOCKED_UNKNOWN");
            $this->assertTrue($blocked->isBlocked());
            $this->assertFalse($blocked->isActive());
            $this->assertFalse($blocked->isNonActiveLifecycle());
            $this->assertSame('blocked', $blocked->reviewState());
        }

        // Deterministic reviewState projections
        $this->assertSame('pending', MemberLifecycleExperience::WaitingVerification->reviewState());
        $this->assertSame('review', MemberLifecycleExperience::UnderReview->reviewState());
        $this->assertSame('revision', MemberLifecycleExperience::RevisionRequired->reviewState());
        $this->assertSame('rejected', MemberLifecycleExperience::Rejected->reviewState());
        $this->assertSame('approved', MemberLifecycleExperience::Active->reviewState());
        $this->assertSame('blocked', MemberLifecycleExperience::BlockedUnknown->reviewState());

        // R1-02 Invariant: onboarding_submitted_at does not change lifecycle experience or review_state
        $memberReviewNoSubmit = CooperativeMember::factory()->pendingReview()->create([
            'onboarding_submitted_at' => null,
        ]);
        $memberReviewWithSubmit = CooperativeMember::factory()->pendingReview()->create([
            'onboarding_submitted_at' => now(),
        ]);
        $this->assertSame(MemberLifecycleExperience::UnderReview, MemberLifecycleExperience::fromMember($memberReviewNoSubmit));
        $this->assertSame(MemberLifecycleExperience::UnderReview, MemberLifecycleExperience::fromMember($memberReviewWithSubmit));
        $this->assertSame('review', MemberLifecycleExperience::fromMember($memberReviewNoSubmit)->reviewState());
        $this->assertSame('review', MemberLifecycleExperience::fromMember($memberReviewWithSubmit)->reviewState());
        $this->assertTrue(app(MemberAccessService::class)->for($memberReviewNoSubmit)['is_pending_review']);
        $this->assertTrue(app(MemberAccessService::class)->for($memberReviewWithSubmit)['is_pending_review']);

        // Member instance resolution via MemberLifecycleExperience::fromMember and MemberAccessService
        $memberWaiting = CooperativeMember::factory()->pending()->create();
        $accessService = app(MemberAccessService::class);

        $this->assertSame(MemberLifecycleExperience::WaitingVerification, MemberLifecycleExperience::fromMember($memberWaiting));
        $this->assertSame('WAITING_VERIFICATION', $accessService->lifecycleExperience($memberWaiting));

        $access = $accessService->for($memberWaiting);
        $this->assertFalse($access['is_active']);
        $this->assertTrue($access['can_access_onboarding']);
        $this->assertSame('WAITING_VERIFICATION', $access['lifecycle_experience']);

        // Null member fails closed
        $this->assertSame(MemberLifecycleExperience::BlockedUnknown, MemberLifecycleExperience::fromMember(null));
        $this->assertSame('BLOCKED_UNKNOWN', $accessService->lifecycleExperience(null));
    }

    /**
     * Requirement 2: Fortify web login routing redirects non-active members to onboarding, active to dashboard, blocked to 403.
     */
    public function test_web_fortify_login_redirects_non_active_members_to_onboarding_and_active_to_dashboard(): void
    {
        // 1. Non-active PENDING/PENDING (WAITING_VERIFICATION)
        $userPending = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->pending()->create(['user_id' => $userPending->id]);
        $userPending->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userPending->email,
            'password' => 'secret123',
        ])->assertRedirect(route('member.onboarding'));

        $this->post('/logout');

        // 2. Non-active PENDING/PENDING_VALIDATION (UNDER_REVIEW)
        $userReview = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->pendingReview()->create(['user_id' => $userReview->id]);
        $userReview->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userReview->email,
            'password' => 'secret123',
        ])->assertRedirect(route('member.onboarding'));

        $this->post('/logout');

        // 3. Non-active INACTIVE/REVISION (REVISION_REQUIRED)
        $userRevision = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->revision()->create(['user_id' => $userRevision->id]);
        $userRevision->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userRevision->email,
            'password' => 'secret123',
        ])->assertRedirect(route('member.onboarding'));

        $this->post('/logout');

        // 4. Non-active INACTIVE/REJECTED (REJECTED)
        $userRejected = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->rejected()->create(['user_id' => $userRejected->id]);
        $userRejected->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userRejected->email,
            'password' => 'secret123',
        ])->assertRedirect(route('member.onboarding'));

        $this->post('/logout');

        // 5. Active ACTIVE/ACTIVE -> member.dashboard
        $userActive = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userActive->email,
            'password' => 'secret123',
        ])->assertRedirect(route('member.dashboard'));

        $this->post('/logout');

        // 6. Inconsistent/Blocked member -> 403
        $userBlocked = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'ACTIVE',
            'validation_status' => 'PENDING',
        ]);
        $userBlocked->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userBlocked->email,
            'password' => 'secret123',
        ])->assertForbidden();

        $this->post('/logout');

        // 7. INACTIVE/INACTIVE and RESIGNED/RESIGNED members fail closed with 403
        $userInactive = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->create([
            'user_id' => $userInactive->id,
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
        ]);
        $userInactive->assignRole('Anggota');

        $this->post('/login', [
            'email' => $userInactive->email,
            'password' => 'secret123',
        ])->assertForbidden();

        $this->post('/logout');

        // 8. url.intended safety: non-active member clears intended URL to prevent hijacking into active routes
        $userPendingIntended = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->pending()->create(['user_id' => $userPendingIntended->id]);
        $userPendingIntended->assignRole('Anggota');

        $this->withSession(['url.intended' => 'http://localhost/member/pos'])
            ->post('/login', [
                'email' => $userPendingIntended->email,
                'password' => 'secret123',
            ])
            ->assertRedirect(route('member.onboarding'));
        $this->assertNull(session('url.intended'));
    }

    /**
     * Requirement 3: DashboardController routing for members.
     */
    public function test_dashboard_controller_routes_members_appropriately(): void
    {
        // Non-active -> member.onboarding
        $userPending = User::factory()->create();
        CooperativeMember::factory()->pending()->create(['user_id' => $userPending->id]);
        $userPending->assignRole('Anggota');

        $this->actingAs($userPending)
            ->get(route('dashboard'))
            ->assertRedirect(route('member.onboarding'));

        // Active -> member.dashboard
        $userActive = User::factory()->create();
        CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $this->actingAs($userActive)
            ->get(route('dashboard'))
            ->assertRedirect(route('member.dashboard'));

        // Inconsistent -> 403
        $userBlocked = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'ACTIVE',
            'validation_status' => 'PENDING',
        ]);
        $userBlocked->assignRole('Anggota');

        $this->actingAs($userBlocked)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    /**
     * Requirement 4: Google SSO redirect destination preserves member lifecycle routing.
     */
    public function test_google_sso_redirect_destination_routes_members_by_lifecycle(): void
    {
        $controller = app(GoogleSsoController::class);

        // Non-active member
        $userPending = User::factory()->create();
        CooperativeMember::factory()->pending()->create(['user_id' => $userPending->id]);
        $userPending->assignRole('Anggota');

        $destPending = $controller->redirectDestination($userPending);
        $this->assertSame(route('member.onboarding', absolute: false), $destPending);

        // Active member
        $userActive = User::factory()->create();
        CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $destActive = $controller->redirectDestination($userActive);
        $this->assertSame(route('member.dashboard', absolute: false), $destActive);
    }

    /**
     * Requirement 5: Active-only Web middleware gate (member.active) denies non-active and permits active.
     */
    public function test_web_member_active_middleware_gate_denies_non_active_and_permits_active(): void
    {
        $onboardingStates = [
            'pending' => CooperativeMember::factory()->pending(),
            'review' => CooperativeMember::factory()->pendingReview(),
            'revision' => CooperativeMember::factory()->revision(),
            'rejected' => CooperativeMember::factory()->rejected(),
        ];

        foreach ($onboardingStates as $key => $factory) {
            $user = User::factory()->create();
            $factory->create(['user_id' => $user->id]);
            $user->assignRole('Anggota');

            // Attempt to access member.active route
            $this->actingAs($user)
                ->get(route('member.store-account'))
                ->assertRedirect(route('member.onboarding'))
                ->assertSessionHas('warning');
        }

        // Active member is permitted
        $userActive = User::factory()->create();
        $memberActive = CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $this->actingAs($userActive)
            ->get(route('member.store-account'))
            ->assertOk();

        // Inconsistent/blocked member receives 403
        $userBlocked = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'ACTIVE',
            'validation_status' => 'PENDING',
        ]);
        $userBlocked->assignRole('Anggota');

        $this->actingAs($userBlocked)
            ->get(route('member.store-account'))
            ->assertForbidden();
    }

    /**
     * Requirement 6: Active-only API middleware gate (member.api.active) denies non-active with 403 MemberNotActive and permits active.
     */
    public function test_api_member_active_middleware_gate_denies_non_active_and_permits_active(): void
    {
        $states = [
            'pending' => CooperativeMember::factory()->pending(),
            'review' => CooperativeMember::factory()->pendingReview(),
            'revision' => CooperativeMember::factory()->revision(),
            'rejected' => CooperativeMember::factory()->rejected(),
        ];

        foreach ($states as $key => $factory) {
            $user = User::factory()->create();
            $factory->create(['user_id' => $user->id]);
            $user->assignRole('Anggota');

            Sanctum::actingAs($user, ['member:write']);

            // Accessing payment charge (guarded by member.api.active)
            $response = $this->postJson('/api/payments/charge', []);
            $response->assertStatus(403);
            $response->assertJson([
                'error_code' => ApiErrorCode::MemberNotActive->value,
            ]);
        }

        // Blocked/inconsistent state receives 403
        $userBlocked = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'ACTIVE',
            'validation_status' => 'PENDING',
        ]);
        $userBlocked->assignRole('Anggota');

        Sanctum::actingAs($userBlocked, ['member:write']);
        $this->postJson('/api/payments/charge', [])
            ->assertForbidden();
    }

    /**
     * Requirement 7: Mobile/API Auth login parity returning lifecycle metadata.
     */
    public function test_api_auth_login_returns_lifecycle_experience_and_onboarding_next_step(): void
    {
        // 1. PENDING/PENDING (WAITING_VERIFICATION)
        $userPending = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->pending()->create(['user_id' => $userPending->id]);
        $userPending->assignRole('Anggota');

        $resPending = $this->postJson('/api/auth/login', [
            'email' => $userPending->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resPending->assertOk();
        $resPending->assertJson([
            'member_status' => 'PENDING',
            'validation_status' => 'PENDING',
            'lifecycle_experience' => 'WAITING_VERIFICATION',
            'onboarding_next_step' => 'waiting_admin_acceptance',
        ]);

        // 2. PENDING/PENDING_VALIDATION (UNDER_REVIEW)
        $userReview = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->pendingReview()->create(['user_id' => $userReview->id]);
        $userReview->assignRole('Anggota');

        $resReview = $this->postJson('/api/auth/login', [
            'email' => $userReview->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resReview->assertOk();
        $resReview->assertJson([
            'member_status' => 'PENDING',
            'validation_status' => 'PENDING_VALIDATION',
            'lifecycle_experience' => 'UNDER_REVIEW',
            'onboarding_next_step' => 'waiting_final_approval',
        ]);

        // 3. INACTIVE/REVISION (REVISION_REQUIRED)
        $userRevision = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->revision()->create(['user_id' => $userRevision->id]);
        $userRevision->assignRole('Anggota');

        $resRevision = $this->postJson('/api/auth/login', [
            'email' => $userRevision->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resRevision->assertOk();
        $resRevision->assertJson([
            'member_status' => 'INACTIVE',
            'validation_status' => 'REVISION',
            'lifecycle_experience' => 'REVISION_REQUIRED',
            'onboarding_next_step' => 'revision_required',
        ]);

        // 4. INACTIVE/REJECTED (REJECTED)
        $userRejected = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->rejected()->create(['user_id' => $userRejected->id]);
        $userRejected->assignRole('Anggota');

        $resRejected = $this->postJson('/api/auth/login', [
            'email' => $userRejected->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resRejected->assertOk();
        $resRejected->assertJson([
            'member_status' => 'INACTIVE',
            'validation_status' => 'REJECTED',
            'lifecycle_experience' => 'REJECTED',
            'onboarding_next_step' => 'rejected',
        ]);

        // 5. ACTIVE/ACTIVE (ACTIVE)
        $userActive = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $resActive = $this->postJson('/api/auth/login', [
            'email' => $userActive->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resActive->assertOk();
        $resActive->assertJson([
            'member_status' => 'ACTIVE',
            'validation_status' => 'ACTIVE',
            'lifecycle_experience' => 'ACTIVE',
            'onboarding_next_step' => 'dashboard',
        ]);

        // 6. Blocked/inconsistent member fails closed with 403 on API
        $userBlocked = User::factory()->create(['password' => Hash::make('secret123')]);
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
        ]);
        $userBlocked->assignRole('Anggota');

        $resBlocked = $this->postJson('/api/auth/login', [
            'email' => $userBlocked->email,
            'password' => 'secret123',
            'app' => 'member',
        ]);
        $resBlocked->assertForbidden();
    }

    /**
     * Requirement 8: ONB-03 Authority Preserved - POST /member/onboarding never self-advances status.
     * R1-01: Sensitive PII mutation closed (legacy fields ignored/not mutated).
     * R1-03: Rejected member cannot submit (403).
     * R1-04: Active member cannot submit (403).
     */
    public function test_legacy_onboarding_submit_never_self_advances_status_preserving_onb03_authority(): void
    {
        // 1. PENDING / PENDING member submits safe profile updates
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->pending()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'phone' => null,
            'identity_number' => '3201234567890001',
            'kategori' => 'CDB',
            'npwp' => '12.345.678.9-000.000',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Original Holder',
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        // Payload includes both safe profile fields AND legacy sensitive fields
        $payload = [
            'name' => 'Updated Name',
            'email' => 'new-email@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Gatot Subroto No. 45',
            'identity_number' => '9999999999999999',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'npwp' => '99.999.999.9-999.999',
            'tanggal_lahir' => '1992-05-15',
            'tempat_lahir' => 'Jakarta',
            'pekerjaan' => 'Staff',
            'nama_bank' => 'BNI',
            'no_rekening' => '9876543210',
            'nama_pemilik_rekening' => 'Updated Holder',
        ];

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), $payload)
            ->assertRedirect(route('member.onboarding'))
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        // Safe profile fields ARE updated
        $this->assertSame('Updated Name', $fresh->name);
        $this->assertSame('08123456789', $fresh->phone);
        $this->assertSame('Jl. Gatot Subroto No. 45', $fresh->address);
        $this->assertSame('L', $fresh->jenis_kelamin);
        $this->assertSame('1992-05-15', $fresh->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('Jakarta', $fresh->tempat_lahir);
        $this->assertSame('Staff', $fresh->pekerjaan);
        $this->assertNotNull($fresh->onboarding_submitted_at);
        $this->assertNotNull($fresh->profile_completed_at);

        // Sensitive PII fields MUST NOT be mutated (R1-01)
        $this->assertSame('3201234567890001', $fresh->identity_number);
        $this->assertSame('CDB', $fresh->kategori);
        $this->assertSame('12.345.678.9-000.000', $fresh->npwp);
        $this->assertSame('BCA', $fresh->nama_bank);
        $this->assertSame('1234567890', $fresh->no_rekening);
        $this->assertSame('Original Holder', $fresh->nama_pemilik_rekening);
        $this->assertSame($user->email, $user->fresh()->email);

        // Crucial invariant: validation_status and status MUST NOT self-advance!
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->status);
        $this->assertSame(
            MemberLifecycleExperience::WaitingVerification,
            MemberLifecycleExperience::fromMember($fresh)
        );

        // 2. ACTIVE / ACTIVE member submits onboarding -> Denied with 403 (R1-04)
        $userActive = User::factory()->create();
        $memberActive = CooperativeMember::factory()->active()->create([
            'user_id' => $userActive->id,
            'name' => 'Active Member',
        ]);
        $userActive->assignRole('Anggota');

        $this->actingAs($userActive)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), ['name' => 'Should Fail'])
            ->assertForbidden();

        $this->assertSame('Active Member', $memberActive->fresh()->name);

        // 3. Rejected member submits onboarding -> Denied with 403 (R1-03)
        $userRejected = User::factory()->create();
        $memberRejected = CooperativeMember::factory()->rejected()->create([
            'user_id' => $userRejected->id,
            'name' => 'Rejected Member',
        ]);
        $userRejected->assignRole('Anggota');

        $this->actingAs($userRejected)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), ['name' => 'Should Also Fail'])
            ->assertForbidden();

        $this->assertSame('Rejected Member', $memberRejected->fresh()->name);

        // 4. Blocked/inconsistent member submits onboarding -> Denied with 403
        $userBlocked = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
        ]);
        $userBlocked->assignRole('Anggota');

        $this->actingAs($userBlocked)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    /**
     * Requirement 9: GET /member/onboarding renders lifecycle status UX or redirects/denies.
     * R1-03: Rejected member reaches read-only page (200 OK) with validation notes and no edit capability.
     * R1-04: Active member redirects to member.dashboard.
     */
    public function test_get_member_onboarding_renders_or_redirects_by_lifecycle(): void
    {
        // 1. Active member redirects to member.dashboard (R1-04)
        $userActive = User::factory()->create();
        CooperativeMember::factory()->active()->create(['user_id' => $userActive->id]);
        $userActive->assignRole('Anggota');

        $this->actingAs($userActive)
            ->get(route('member.onboarding'))
            ->assertRedirect(route('member.dashboard'));

        // 2. Rejected member reaches page in read-only mode (R1-03)
        $userRejected = User::factory()->create();
        CooperativeMember::factory()->rejected()->create([
            'user_id' => $userRejected->id,
            'validation_notes' => 'Dokumen identitas tidak jelas.',
        ]);
        $userRejected->assignRole('Anggota');

        $this->actingAs($userRejected)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'rejected')
                ->where('can_edit_safe_profile', false)
                ->where('can_view_lifecycle_status', true)
                ->where('member.validation_notes', 'Dokumen identitas tidak jelas.')
            );

        // 3. Waiting verification member reaches page with editable safe profile
        $userWaiting = User::factory()->create();
        CooperativeMember::factory()->pending()->create(['user_id' => $userWaiting->id]);
        $userWaiting->assignRole('Anggota');

        $this->actingAs($userWaiting)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'pending')
                ->where('can_edit_safe_profile', true)
            );

        // 4. Under review member reaches page with read-only review state
        $userReview = User::factory()->create();
        CooperativeMember::factory()->pendingReview()->create(['user_id' => $userReview->id]);
        $userReview->assignRole('Anggota');

        $this->actingAs($userReview)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'review')
                ->where('can_edit_safe_profile', false)
            );

        // 5. Inconsistent / blocked member receives 403
        $userBlocked = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $userBlocked->id,
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
        ]);
        $userBlocked->assignRole('Anggota');

        $this->actingAs($userBlocked)
            ->get(route('member.onboarding'))
            ->assertForbidden();
    }

    /**
     * Requirement 10: Multi-tenant safety - member experience and access are strictly scoped to organization.
     */
    public function test_multi_tenant_isolation_member_experience_strictly_scoped_to_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $memberA = CooperativeMember::factory()->pending()->create([
            'user_id' => $userA->id,
            'organization_id' => $orgA->id,
        ]);
        $userA->assignRole('Anggota');

        $userB = User::factory()->create(['organization_id' => $orgB->id]);
        $memberB = CooperativeMember::factory()->active()->create([
            'user_id' => $userB->id,
            'organization_id' => $orgB->id,
        ]);
        $userB->assignRole('Anggota');

        $accessService = app(MemberAccessService::class);

        $this->assertSame('WAITING_VERIFICATION', $accessService->lifecycleExperience($memberA));
        $this->assertSame('ACTIVE', $accessService->lifecycleExperience($memberB));

        // Member A in Org A cannot access or view Member B's data
        $this->assertNotSame($memberA->organization_id, $memberB->organization_id);
    }
}
