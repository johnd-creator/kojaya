<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Enums\ApiErrorCode;
use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\PreviewProofService;
use Database\Seeders\RolePermissionSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * ONB-09: End-to-End Member Onboarding Verification Suite.
 *
 * Verifies the complete connected lifecycle:
 * Member Data Contract -> Backend Import Validator -> Dry-Run / Preview -> DEV Import ->
 * Google SSO Matching -> First Login / Member Lifecycle -> Admin Verification & Approval Authority ->
 * Active Member Access.
 *
 * Covers Scenarios A through Q and Invariants I-01 through I-22.
 */
class MemberOnboardingEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $adminKoperasi;

    private User $pengurusKoperasi;

    private User $unauthorizedUser;

    private PreviewProofService $proofService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        // Permissions for verification and approval workflows
        Permission::firstOrCreate(['name' => 'verify_cooperative_member']);
        Permission::firstOrCreate(['name' => 'approve_cooperative_member']);

        // Enable DEV member import execution gate
        Config::set('cooperative.member_import_execution_enabled', true);

        // Google SSO test configuration
        Config::set('services.google.sso_enabled', true);
        Config::set('services.google.client_id', 'web-client.apps.googleusercontent.com');
        Config::set('services.google.allow_new_member_registration', true);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-E2E',
            'name' => 'Koperasi E2E Unit',
        ]);

        // Admin Koperasi: has manage, import, and verify permissions
        $this->adminKoperasi = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi E2E',
            'email' => 'admin.e2e@example.com',
        ]);
        $this->adminKoperasi->assignRole('Admin Koperasi');
        $this->adminKoperasi->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
        $this->adminKoperasi->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_IMPORT->value);
        $this->adminKoperasi->givePermissionTo('verify_cooperative_member');

        // Pengurus Koperasi: has final approval permission
        $this->pengurusKoperasi = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Pengurus Koperasi E2E',
            'email' => 'pengurus.e2e@example.com',
        ]);
        $this->pengurusKoperasi->assignRole('Pengurus Koperasi');
        $this->pengurusKoperasi->givePermissionTo('approve_cooperative_member');

        // Regular unauthorized user
        $this->unauthorizedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Unauthorized User',
            'email' => 'unauthorized.e2e@example.com',
        ]);
        $this->unauthorizedUser->assignRole('Anggota');

        $this->proofService = app(PreviewProofService::class);
    }

    /**
     * Scenario A, E, F, G, H, I, L, Q:
     * Complete Connected Onboarding Lifecycle Journey.
     *
     * Valid CSV Import -> Google SSO Matching -> WAITING_VERIFICATION ->
     * Admin Verification -> UNDER_REVIEW -> Pengurus Approval -> ACTIVE.
     *
     * Proves: I-01, I-05, I-06, I-07, I-08, I-09, I-12, I-13, I-16, I-21.
     */
    public function test_e2e_complete_member_onboarding_lifecycle_journey(): void
    {
        $memberEmail = 'budi.e2e@example.com';
        $memberNik = '3201012301900001';
        $memberNo = 'KOP-001';
        $googleSub = 'google-sub-budi-e2e-001';

        // ---------------------------------------------------------------------
        // STEP 1: Admin Dry-Run Preview (Scenario A & C)
        // ---------------------------------------------------------------------
        $row = $this->validRow([
            'member_number' => $memberNo,
            'full_name' => 'Budi Santoso',
            'email' => $memberEmail,
            'phone_number' => '081234567890',
            'identity_number' => $memberNik,
            'gender' => 'L',
            'company_code' => 'IP',
            'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
            'membership_type' => 'AB',
            'join_date' => '2026-06-01',
        ]);

        $upload = $this->createUploadWithProof([$row], (string) $this->organization->id);

        $resPreview = $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $upload['file'],
                'import_date' => '2026-06-01',
                'organization_id' => (string) $this->organization->id,
            ]);

        $returnedProof = null;
        $resPreview->assertOk()
            ->assertInertia(function (Assert $page) use (&$returnedProof) {
                $page->component('Cooperative/Members/ImportPreview')
                    ->where('preview.valid', true)
                    ->where('preview.total_rows', 1)
                    ->where('preview.invalid_rows', 0)
                    ->whereNot('preview_proof', null);

                $returnedProof = $page->toArray()['props']['preview_proof'];
            });

        $this->assertNotEmpty($returnedProof);
        $verifyResult = $this->proofService->verify($returnedProof, $upload['hash'], (string) $this->organization->id, '2026-06-01');
        $this->assertTrue($verifyResult['valid'], 'Preview proof returned from preview must be cryptographically valid');

        // Invariant I-03: Zero persistence during preview
        $this->assertSame(0, CooperativeMember::where('email', $memberEmail)->count());
        $this->assertSame(0, User::where('email', $memberEmail)->count());

        // ---------------------------------------------------------------------
        // STEP 2: DEV Member Import Execution (Scenario A, I-01)
        // ---------------------------------------------------------------------
        $resExecute = $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $upload['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $returnedProof,
                'confirm_import' => true,
                'organization_id' => (string) $this->organization->id,
            ]);

        $resExecute->assertRedirect();

        // Verify CooperativeMember persisted correctly
        $importedMember = CooperativeMember::query()->where('email', $memberEmail)->first();
        $this->assertNotNull($importedMember, 'Imported CooperativeMember must exist');
        $this->assertSame($memberNo, $importedMember->no_anggota);
        $this->assertSame('Budi Santoso', $importedMember->name);
        $this->assertSame('3201012301900001', $importedMember->identity_number);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $importedMember->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $importedMember->validation_status);

        // Invariant I-06: DEV import creates ZERO users or OAuth records
        $this->assertNull($importedMember->user_id, 'Imported member user_id must be null before SSO');
        $this->assertSame(0, User::where('email', $memberEmail)->count());
        $this->assertSame(0, SocialAccount::where('provider_id', $googleSub)->count());

        // ---------------------------------------------------------------------
        // STEP 3: Google SSO Matching (Scenario E, I-05, I-06)
        // ---------------------------------------------------------------------
        auth('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->mockSocialite(googleId: $googleSub, email: $memberEmail, verified: true, name: 'Budi Santoso');

        $resCallback = $this->get(route('auth.google.callback'));
        // Non-active member is redirected directly to onboarding lifecycle screen
        $resCallback->assertRedirect(route('member.onboarding', absolute: false));

        $matchedUser = User::where('email', $memberEmail)->first();
        $this->assertNotNull($matchedUser, 'User must be created upon first Google SSO matching');
        $this->assertTrue($matchedUser->hasRole('Anggota'));

        // Refresh member: linked to matched user
        $importedMember->refresh();
        $this->assertSame($matchedUser->id, $importedMember->user_id, 'CooperativeMember must be linked to matched User');

        // SocialAccount verified
        $social = SocialAccount::where('user_id', $matchedUser->id)->where('provider', 'google')->first();
        $this->assertNotNull($social);
        $this->assertSame($googleSub, $social->provider_id);

        // Invariant I-06: Exact 1 User and 1 CooperativeMember
        $this->assertSame(1, User::where('email', $memberEmail)->count());
        $this->assertSame(1, CooperativeMember::where('email', $memberEmail)->count());

        // ---------------------------------------------------------------------
        // STEP 4: WAITING_VERIFICATION Lifecycle Experience (Scenario G, I-12, I-21)
        // ---------------------------------------------------------------------
        // Web experience
        $this->actingAs($matchedUser)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', MemberLifecycleExperience::WaitingVerification->value)
                ->where('review_state', 'pending')
            );

        // Mobile password auth
        $matchedUser->forceFill(['password' => Hash::make('secretPassword123')])->save();
        $resMobileLogin = $this->postJson('/api/auth/login', [
            'email' => $memberEmail,
            'password' => 'secretPassword123',
            'app' => 'member',
        ]);
        $resMobileLogin->assertOk()
            ->assertJsonPath('lifecycle_experience', MemberLifecycleExperience::WaitingVerification->value)
            ->assertJsonPath('onboarding_next_step', 'waiting_admin_acceptance');
        $sanctumToken = $resMobileLogin->json('token');
        $this->assertNotEmpty($sanctumToken);

        // Invariant I-21: Active-only gates reject non-active member
        $this->withToken($sanctumToken)
            ->getJson('/api/v1/member/savings/summary')
            ->assertForbidden()
            ->assertJsonPath('error_code', ApiErrorCode::MemberNotActive->value);

        $this->actingAs($matchedUser)
            ->get(route('member.savings'))
            ->assertRedirect(route('member.onboarding'));

        // ---------------------------------------------------------------------
        // STEP 5: Admin Verification Authority (Scenario H, I-08, I-09)
        // ---------------------------------------------------------------------
        // Invariant I-08 & I-09: Member cannot self-verify or self-approve
        $this->actingAs($matchedUser)
            ->post(route('cooperative.members.validate', $importedMember))
            ->assertForbidden();

        // Unauthorized user cannot verify
        $this->actingAs($this->unauthorizedUser)
            ->post(route('cooperative.members.validate', $importedMember))
            ->assertForbidden();

        // Authorized Admin Koperasi executes validation
        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.validate', $importedMember))
            ->assertRedirect();

        $importedMember->refresh();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $importedMember->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $importedMember->validation_status);
        $this->assertSame($this->adminKoperasi->id, $importedMember->admin_validated_by);
        $this->assertNotNull($importedMember->admin_validated_at);
        $this->assertNull($importedMember->validated_at);

        // ---------------------------------------------------------------------
        // STEP 6: UNDER_REVIEW Lifecycle Experience (Scenario I, I-13)
        // ---------------------------------------------------------------------
        $matchedUser->refresh();
        $this->actingAs($matchedUser)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', MemberLifecycleExperience::UnderReview->value)
                ->where('review_state', 'review')
            );

        $this->postJson('/api/auth/login', [
            'email' => $memberEmail,
            'password' => 'secretPassword123',
            'app' => 'member',
        ])->assertOk()
            ->assertJsonPath('lifecycle_experience', MemberLifecycleExperience::UnderReview->value)
            ->assertJsonPath('onboarding_next_step', 'waiting_final_approval');

        // Under review member cannot alter profile data
        $this->actingAs($matchedUser)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Hacked Name Attempt',
            ])->assertForbidden();

        // Active-only gate still rejects under review member
        $this->withToken($sanctumToken)
            ->getJson('/api/v1/member/savings/summary')
            ->assertForbidden();

        // ---------------------------------------------------------------------
        // STEP 7: Pengurus Final Approval (Scenario L, ONB-03 Maker-Checker, I-08)
        // ---------------------------------------------------------------------
        // Maker-Checker Invariant: Verifier cannot approve their own verified member
        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.approve-final', $importedMember))
            ->assertForbidden();

        // Authorized Pengurus Koperasi executes final approval
        $this->actingAs($this->pengurusKoperasi)
            ->post(route('cooperative.members.approve-final', $importedMember))
            ->assertRedirect();

        $importedMember->refresh();
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $importedMember->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $importedMember->validation_status);
        $this->assertSame($this->pengurusKoperasi->id, $importedMember->validated_by);
        $this->assertNotNull($importedMember->validated_at);

        // ---------------------------------------------------------------------
        // STEP 8: ACTIVE Lifecycle Experience & Access Hand-Off (Scenario L, Q, I-16)
        // ---------------------------------------------------------------------
        $matchedUser->refresh();
        // Web: Onboarding redirects to member dashboard
        $this->actingAs($matchedUser)
            ->get(route('member.onboarding'))
            ->assertRedirect(route('member.dashboard'));

        // Mobile: Login reports ACTIVE and dashboard next step
        $resActiveLogin = $this->postJson('/api/auth/login', [
            'email' => $memberEmail,
            'password' => 'secretPassword123',
            'app' => 'member',
        ]);
        $resActiveLogin->assertOk()
            ->assertJsonPath('lifecycle_experience', MemberLifecycleExperience::Active->value)
            ->assertJsonPath('onboarding_next_step', 'dashboard');
        $activeToken = $resActiveLogin->json('token');
        $this->assertNotEmpty($activeToken);

        // Clear web session before making API Bearer token request
        auth('web')->logout();
        $this->app['auth']->forgetGuards();

        // Active-only API and Web routes are now accessible
        $this->withToken($activeToken)
            ->getJson('/api/v1/member/savings/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => ['total_balance']]);

        $this->actingAs($matchedUser)
            ->get(route('member.savings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Kojayaku/Savings'));
    }

    /**
     * Scenario B & C:
     * Invalid Import Fails Safely and Dry-Run Proven Zero-Persistence.
     *
     * Proves: I-02, I-03.
     */
    public function test_e2e_invalid_import_and_dry_run_zero_persistence(): void
    {
        $initialUsers = User::count();
        $initialMembers = CooperativeMember::count();
        $initialSocial = SocialAccount::count();
        $initialTokens = PersonalAccessToken::count();

        // 1. Valid preview dry-run
        $validRow = $this->validRow([
            'member_number' => 'KOP-999',
            'email' => 'preview.only@example.com',
            'identity_number' => '3201012301900999',
        ]);
        $upload = $this->createUploadWithProof([$validRow], (string) $this->organization->id);

        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $upload['file'],
                'import_date' => '2026-06-01',
                'organization_id' => (string) $this->organization->id,
            ])
            ->assertOk();

        // Invariant I-03: Zero persistence after preview
        $this->assertSame($initialUsers, User::count(), 'Zero users created on preview');
        $this->assertSame($initialMembers, CooperativeMember::count(), 'Zero members created on preview');
        $this->assertSame($initialSocial, SocialAccount::count(), 'Zero social accounts created on preview');
        $this->assertSame($initialTokens, PersonalAccessToken::count(), 'Zero tokens created on preview');

        // 2. Invalid CSV (Invalid 10-digit NIK & invalid gender)
        $invalidRow = $this->validRow([
            'member_number' => 'KOP-998',
            'email' => 'invalid.e2e@example.com',
            'identity_number' => '1234567890', // Invalid: 10 digits
            'gender' => 'X', // Invalid gender
        ]);
        $invalidCsv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [$invalidRow]);
        $invalidFile = UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv);
        $invalidHash = hash('sha256', $invalidCsv);
        $invalidProof = $this->proofService->generate($invalidHash, (string) $this->organization->id, '2026-06-01');

        $resInvalid = $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $invalidFile,
                'import_date' => '2026-06-01',
                'preview_proof' => $invalidProof,
                'confirm_import' => true,
                'organization_id' => (string) $this->organization->id,
            ]);

        // Invariant I-02: Safely rejected, atomic rollback, zero partial records
        $this->assertSame($initialUsers, User::count(), 'Zero users created on failed import');
        $this->assertSame($initialMembers, CooperativeMember::count(), 'Zero members created on failed import');
        $this->assertNull(CooperativeMember::where('email', 'invalid.e2e@example.com')->first());
    }

    /**
     * Scenario D:
     * DEV Import Idempotency and Duplicate Safety.
     *
     * Proves: I-04.
     */
    public function test_e2e_dev_import_duplicate_safety_and_idempotency(): void
    {
        $row = $this->validRow([
            'member_number' => 'KOP-888',
            'email' => 'duplicate.test@example.com',
            'identity_number' => '3201012301900888',
        ]);
        $upload = $this->createUploadWithProof([$row], (string) $this->organization->id);

        // First import succeeds
        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $upload['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $upload['proof'],
                'confirm_import' => true,
                'organization_id' => (string) $this->organization->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, CooperativeMember::where('email', 'duplicate.test@example.com')->count());

        // Second import of the identical batch must be rejected (conflict on member_number & identity_number)
        $upload2 = $this->createUploadWithProof([$row], (string) $this->organization->id);

        $resSecond = $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $upload2['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $upload2['proof'],
                'confirm_import' => true,
                'organization_id' => (string) $this->organization->id,
            ]);

        // Second import fails cleanly with conflict/error session
        $resSecond->assertSessionHasErrors();

        // Invariant I-04: No duplicate member created
        $this->assertSame(1, CooperativeMember::where('email', 'duplicate.test@example.com')->count());
    }

    /**
     * Scenario J, P, O:
     * REVISION_REQUIRED Lifecycle Experience, Safe Profile Updates, and Sensitive Data Protection.
     *
     * Proves: I-08, I-09, I-10, I-11, I-14, I-21.
     */
    public function test_e2e_revision_required_lifecycle_and_safe_profile_update(): void
    {
        $user = User::factory()->create(['email' => 'revision.e2e@example.com']);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'email' => 'revision.e2e@example.com',
            'no_anggota' => 'KOP-E2E-REV',
            'identity_number' => '3201012301900777',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        // Admin requests revision with notes
        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.request-revision', $member), [
                'notes' => 'Nomor HP dan alamat domisili mohon dilengkapi.',
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $member->validation_status);

        // Web shows REVISION_REQUIRED and validation notes
        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', MemberLifecycleExperience::RevisionRequired->value)
                ->where('review_state', 'revision')
                ->where('validation_notes', 'Nomor HP dan alamat domisili mohon dilengkapi.')
            );

        // Member submits safe profile update along with attempted malicious privilege escalations (I-08, I-09, I-10, I-11)
        $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                // Safe fields
                'phone' => '081299998888',
                'address' => 'Jl. Kebon Sirih No. 12, Jakarta Pusat',
                // Malicious attempt fields (must be IGNORED by allowlist)
                'status' => 'ACTIVE',
                'validation_status' => 'ACTIVE',
                'identity_number' => '9999999999999999',
                'no_anggota' => 'HACK-001',
                'member_no' => 'HACK-001',
            ])
            ->assertRedirect(route('member.onboarding'));

        $member->refresh();

        // Safe fields updated
        $this->assertSame('081299998888', $member->phone);
        $this->assertSame('Jl. Kebon Sirih No. 12, Jakarta Pusat', $member->address);

        // Invariants I-08, I-09, I-10, I-11: Authoritative fields remain protected
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->status, 'Member cannot self-activate status');
        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $member->validation_status, 'Member cannot self-approve validation_status');
        $this->assertSame('3201012301900777', $member->identity_number, 'NIK cannot be mutated by member');
        $this->assertSame('KOP-E2E-REV', $member->no_anggota, 'Member number cannot be mutated by member');

        // Active-only gate remains denied
        Sanctum::actingAs($user, ['member:read']);
        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    /**
     * Scenario K & O:
     * REJECTED Lifecycle Experience and Immutable Lockout.
     *
     * Proves: I-08, I-09, I-10, I-15, I-21.
     */
    public function test_e2e_rejected_member_lifecycle_and_immutable_lockout(): void
    {
        $user = User::factory()->create(['email' => 'rejected.e2e@example.com']);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'email' => 'rejected.e2e@example.com',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        // Admin rejects member with reason
        $this->actingAs($this->adminKoperasi)
            ->post(route('cooperative.members.reject', $member), [
                'notes' => 'Pendaftaran tidak memenuhi kriteria keanggotaan unit koperasi.',
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->validation_status);

        // Web renders rejected screen in read-only mode
        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('lifecycle_experience', MemberLifecycleExperience::Rejected->value)
                ->where('review_state', 'rejected')
                ->where('validation_notes', 'Pendaftaran tidak memenuhi kriteria keanggotaan unit koperasi.')
            );

        // Invariant I-08, I-09, I-10: Rejected member cannot submit or self-reactivate (403)
        $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Attempted Resubmit',
            ])
            ->assertForbidden();

        // Active-only gates denied
        Sanctum::actingAs($user, ['member:read']);
        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    /**
     * Scenario M:
     * BLOCKED_UNKNOWN Fail-Closed Zero-Token Security Invariant.
     *
     * Proves: I-17, I-18, I-19, I-21.
     */
    public function test_e2e_blocked_unknown_fail_closed_zero_token_issuance(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked.e2e@example.com',
            'password' => Hash::make('secretPassword123'),
        ]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'email' => 'blocked.e2e@example.com',
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE', // Corrupt / inconsistent pair -> BLOCKED_UNKNOWN
        ]);
        $social = SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-blocked-sub-001',
            'provider_email' => 'blocked.e2e@example.com',
            'last_login_at' => null,
        ]);

        // 1. Password Mobile Login (Invariant I-18)
        $tokensBeforePwd = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        $this->assertSame(0, $tokensBeforePwd);

        $resPwd = $this->postJson('/api/auth/login', [
            'email' => 'blocked.e2e@example.com',
            'password' => 'secretPassword123',
            'app' => 'member',
        ]);

        $resPwd->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', ApiErrorCode::MemberNotActive->value)
            ->assertJsonPath('data.lifecycle_experience', MemberLifecycleExperience::BlockedUnknown->value)
            ->assertJsonMissing(['token', 'token_type']);

        $tokensAfterPwd = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        $this->assertSame(0, $tokensAfterPwd, 'Zero tokens issued for blocked password mobile login');

        // 2. Google Mobile Login (Invariant I-19)
        $keyPair = $this->fakeRsaJwk();
        $idToken = $this->fakeGoogleIdToken($keyPair['private_key'], [
            'sub' => 'google-blocked-sub-001',
            'email' => 'blocked.e2e@example.com',
            'email_verified' => true,
            'name' => 'Blocked Member',
        ]);

        Http::fake([
            'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
                'keys' => [$keyPair['jwk']],
            ]),
        ]);

        $lastLoginBefore = $social->last_login_at;

        $resGoogle = $this->postJson('/api/auth/google/mobile', [
            'id_token' => $idToken,
            'device_name' => 'Android Member',
            'device_id' => 'android-device',
            'platform' => 'android',
            'app' => 'member',
        ]);

        $resGoogle->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', ApiErrorCode::MemberNotActive->value)
            ->assertJsonPath('data.lifecycle_experience', MemberLifecycleExperience::BlockedUnknown->value)
            ->assertJsonMissing(['token', 'token_type']);

        $tokensAfterGoogle = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        $this->assertSame(0, $tokensAfterGoogle, 'Zero tokens issued for blocked Google mobile login');

        $this->assertNull(
            $social->fresh()->last_login_at,
            'Blocked Google authentication must not be recorded as a successful social login.'
        );
        $this->assertSame(
            $lastLoginBefore,
            $social->fresh()->last_login_at,
            'Blocked Google authentication must not mutate last_login_at.'
        );

        // Invariant I-17: Active gate fails closed
        Sanctum::actingAs($user, ['member:read']);
        $this->getJson('/api/v1/member/savings/summary')->assertForbidden();
    }

    /**
     * Scenario N:
     * Web and Mobile Lifecycle Parity Across All Canonical States.
     *
     * Proves: I-07, I-20.
     */
    public function test_e2e_web_and_mobile_lifecycle_parity_across_all_canonical_states(): void
    {
        $matrix = [
            'waiting' => [
                'status' => 'PENDING',
                'validation_status' => 'PENDING',
                'expected' => MemberLifecycleExperience::WaitingVerification,
            ],
            'under_review' => [
                'status' => 'PENDING',
                'validation_status' => 'PENDING_VALIDATION',
                'expected' => MemberLifecycleExperience::UnderReview,
            ],
            'revision' => [
                'status' => 'INACTIVE',
                'validation_status' => 'REVISION',
                'expected' => MemberLifecycleExperience::RevisionRequired,
            ],
            'rejected' => [
                'status' => 'INACTIVE',
                'validation_status' => 'REJECTED',
                'expected' => MemberLifecycleExperience::Rejected,
            ],
            'active' => [
                'status' => 'ACTIVE',
                'validation_status' => 'ACTIVE',
                'expected' => MemberLifecycleExperience::Active,
            ],
            'blocked_unknown' => [
                'status' => 'INACTIVE',
                'validation_status' => 'INACTIVE',
                'expected' => MemberLifecycleExperience::BlockedUnknown,
            ],
        ];

        foreach ($matrix as $key => $row) {
            $user = User::factory()->create([
                'email' => "parity-{$key}@example.com",
                'password' => Hash::make('password123'),
            ]);
            $user->assignRole('Anggota');
            $member = CooperativeMember::factory()->create([
                'user_id' => $user->id,
                'email' => "parity-{$key}@example.com",
                'status' => $row['status'],
                'validation_status' => $row['validation_status'],
            ]);

            // Authoritative domain mapping
            $domainExperience = MemberLifecycleExperience::fromMember($member);
            $this->assertSame($row['expected'], $domainExperience);

            // Web parity check
            if ($domainExperience->isActive()) {
                $this->actingAs($user)
                    ->get(route('member.onboarding'))
                    ->assertRedirect(route('member.dashboard'));
            } elseif ($domainExperience->isNonActiveLifecycle()) {
                $this->actingAs($user)
                    ->get(route('member.onboarding'))
                    ->assertOk()
                    ->assertInertia(fn (Assert $page) => $page
                        ->component('Kojayaku/Onboarding')
                        ->where('lifecycle_experience', $row['expected']->value)
                    );
            } else {
                $this->actingAs($user)
                    ->get(route('member.onboarding'))
                    ->assertForbidden();
            }

            // Mobile parity check
            $res = $this->postJson('/api/auth/login', [
                'email' => "parity-{$key}@example.com",
                'password' => 'password123',
                'app' => 'member',
            ]);

            if ($domainExperience->isBlocked()) {
                $res->assertForbidden()
                    ->assertJsonPath('data.lifecycle_experience', $row['expected']->value);
            } else {
                $res->assertOk()
                    ->assertJsonPath('lifecycle_experience', $row['expected']->value);
            }
        }
    }

    // -------------------------------------------------------------------------
    // TEST HELPERS
    // -------------------------------------------------------------------------

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, mixed>>  $rows
     */
    private function generateCsv(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($fp, $line);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv ?: '';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'member_number' => 'KOP-001',
            'full_name' => 'Budi Santoso',
            'email' => 'budi.e2e@example.com',
            'phone_number' => '081234567890',
            'identity_number' => '3201012301900001',
            'gender' => 'L',
            'company_code' => 'IP',
            'employee_number' => null,
            'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
            'membership_type' => 'AB',
            'join_date' => '2026-06-01',
            'notes' => 'Catatan anggota E2E',
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{file: UploadedFile, proof: string, hash: string}
     */
    private function createUploadWithProof(
        array $rows,
        string $orgId,
        string $importDate = '2026-06-01',
        ?array $headers = null,
    ): array {
        $headers ??= MemberImportValidator::CANONICAL_HEADERS;
        $csvContent = $this->generateCsv($headers, $rows);
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);
        $hash = hash('sha256', $csvContent);
        $proof = $this->proofService->generate($hash, $orgId, $importDate);

        return ['file' => $file, 'proof' => $proof, 'hash' => $hash];
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

    /**
     * @return array{private_key: string, jwk: array<string, string>}
     */
    protected function fakeRsaJwk(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        return [
            'private_key' => $privateKey,
            'jwk' => [
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => 'test-kid',
                'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function fakeGoogleIdToken(string $privateKey, array $claims = []): string
    {
        $clientId = (string) (config('services.google.client_id') ?: 'web-client.apps.googleusercontent.com');
        config()->set('services.google.client_id', $clientId);
        config()->set('services.google.sso_enabled', true);

        return JWT::encode(array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => $clientId,
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
