<?php

namespace Tests\Feature\Member;

use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\MemberResignationRequest;
use App\Models\Organization;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\Cooperative\MemberProfileCompletenessService;
use App\Services\Cooperative\MemberStatusConsistencyReport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberLifecycleAndProfileFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->organization = Organization::factory()->create();
    }

    // =========================================================================
    // MEM-001: Member Onboarding View & Eligibility
    // =========================================================================

    public function test_mem_001_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('member.onboarding'));
        $response->assertRedirect('/login');
    }

    public function test_mem_001_user_without_cooperative_member_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');

        $response = $this->actingAs($user)->get(route('member.onboarding'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_mem_001_non_active_lifecycle_stages_can_access_onboarding_view(): void
    {
        $cases = [
            [
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
                'expected_review_state' => 'pending',
                'expected_experience' => MemberLifecycleExperience::WaitingVerification->value,
            ],
            [
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'expected_review_state' => 'review',
                'expected_experience' => MemberLifecycleExperience::UnderReview->value,
            ],
            [
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REVISION,
                'expected_review_state' => 'revision',
                'expected_experience' => MemberLifecycleExperience::RevisionRequired->value,
            ],
            [
                'status' => CooperativeMember::VALIDATION_INACTIVE,
                'validation_status' => CooperativeMember::VALIDATION_REJECTED,
                'expected_review_state' => 'rejected',
                'expected_experience' => MemberLifecycleExperience::Rejected->value,
            ],
        ];

        foreach ($cases as $case) {
            $user = User::factory()->create(['organization_id' => $this->organization->id]);
            $user->assignRole('Anggota');
            CooperativeMember::factory()->create([
                'organization_id' => $this->organization->id,
                'user_id' => $user->id,
                'status' => $case['status'],
                'validation_status' => $case['validation_status'],
                'onboarding_submitted_at' => null,
            ]);

            $response = $this->actingAs($user)->get(route('member.onboarding'));
            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', $case['expected_review_state'])
                ->where('lifecycle_experience', $case['expected_experience'])
                ->where('submitted', false)
            );
        }
    }

    public function test_mem_001_active_member_visiting_onboarding_is_redirected_to_member_dashboard(): void
    {
        $user = $this->createActiveMemberUser();

        $response = $this->actingAs($user)->get(route('member.onboarding'));
        $response->assertRedirect(route('member.dashboard'));
    }

    public function test_mem_001_blocked_unknown_lifecycle_is_denied_with_403(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED, // Inconsistent / blocked
        ]);

        $response = $this->actingAs($user)->get(route('member.onboarding'));
        $response->assertForbidden();
    }

    // =========================================================================
    // MEM-002: Member Onboarding Form Submission
    // =========================================================================

    public function test_mem_002_eligible_pending_member_can_submit_onboarding_and_does_not_self_advance(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'name' => 'Nama Lama',
            'phone' => null,
            'address' => null,
            'identity_number' => '3201000000000001',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'onboarding_submitted_at' => null,
            'profile_completed_at' => null,
        ]);

        $payload = [
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Kebon Jeruk No. 5',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1992-05-15',
            'tempat_lahir' => 'Jakarta',
            'pekerjaan' => 'Karyawan Swasta',
        ];

        $response = $this->actingAs($user)
            ->from(route('member.onboarding'))
            ->post(route('member.onboarding.submit'), $payload);

        $response->assertRedirect(route('member.onboarding'));
        $response->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame('Budi Santoso', $fresh->name);
        $this->assertSame('081234567890', $fresh->phone);
        $this->assertSame('Jl. Kebon Jeruk No. 5', $fresh->address);
        $this->assertSame('L', $fresh->jenis_kelamin);
        $this->assertNotNull($fresh->onboarding_submitted_at);
        $this->assertNotNull($fresh->profile_completed_at);

        // Security invariant: status MUST NOT self-advance to ACTIVE
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->validation_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.profile.updated',
            'subject_id' => $member->id,
        ]);
    }

    public function test_mem_002_revision_member_can_resubmit_onboarding(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
            'onboarding_submitted_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('member.onboarding'))
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Revisi',
                'phone' => '081299998888',
                'address' => 'Jl. Revisi No. 12',
            ]);

        $response->assertRedirect(route('member.onboarding'));
        $this->assertSame('Budi Revisi', $member->fresh()->name);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $member->fresh()->validation_status);
    }

    public function test_mem_002_active_member_cannot_submit_onboarding(): void
    {
        $user = $this->createActiveMemberUser();

        $response = $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Hacker',
                'phone' => '081200000000',
            ]);

        $response->assertForbidden();
    }

    public function test_mem_002_under_review_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Change',
            ]);

        $response->assertForbidden();
    }

    public function test_mem_002_rejected_member_cannot_resubmit_onboarding(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $response = $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Try Again',
            ]);

        $response->assertForbidden();
    }

    public function test_mem_002_blocked_member_submission_is_denied_with_403(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED, // BlockedUnknown
        ]);

        $response = $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Blocked',
            ]);

        $response->assertForbidden();
    }

    public function test_mem_002_immutable_fields_in_payload_are_strictly_ignored_without_mutation(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com', 'organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'no_anggota' => 'KOP-ORIGINAL-001',
            'member_no' => 'KOP-ORIGINAL-001',
            'identity_number' => '3201000000000001',
            'kategori' => 'IP',
            'npwp' => '12.345.678.9-000.000',
            'no_rekening' => '1111222233',
            'nama_bank' => 'BCA',
            'nama_pemilik_rekening' => 'Pemilik Asli',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $payload = [
            'name' => 'Safe New Name',
            'phone' => '081234567890',
            'address' => 'Jl. Aman No. 1',
            // Tamper attempts:
            'email' => 'hacked@example.com',
            'identity_number' => '3201999999999999',
            'member_no' => 'KOP-HACKED-999',
            'kategori' => 'CDB',
            'npwp' => '99.999.999.9-999.999',
            'no_rekening' => '9999999999',
            'nama_bank' => 'Bank Hacked',
            'nama_pemilik_rekening' => 'Hacker',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'organization_id' => 99999,
        ];

        $this->actingAs($user)
            ->from(route('member.onboarding'))
            ->post(route('member.onboarding.submit'), $payload)
            ->assertRedirect(route('member.onboarding'));

        $fresh = $member->fresh();
        // Safe fields updated:
        $this->assertSame('Safe New Name', $fresh->name);
        $this->assertSame('081234567890', $fresh->phone);
        $this->assertSame('Jl. Aman No. 1', $fresh->address);

        // Immutable fields strictly preserved:
        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame('3201000000000001', $fresh->identity_number);
        $this->assertSame('KOP-ORIGINAL-001', $fresh->member_no);
        $this->assertSame('IP', $fresh->kategori);
        $this->assertSame('12.345.678.9-000.000', $fresh->npwp);
        $this->assertSame('1111222233', $fresh->no_rekening);
        $this->assertSame('BCA', $fresh->nama_bank);
        $this->assertSame('Pemilik Asli', $fresh->nama_pemilik_rekening);
        $this->assertSame($this->organization->id, $fresh->organization_id);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->validation_status);
    }

    public function test_mem_002_duplicate_nik_submission_across_organizations_cannot_mutate_identity_or_cross_organization_boundaries(): void
    {
        // 1. Existing Member A in Organization 1 with registered NIK
        $userA = User::factory()->create(['organization_id' => $this->organization->id]);
        $userA->assignRole('Anggota');
        $memberA = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $userA->id,
            'name' => 'Anggota Org Satu',
            'identity_number' => '3201012345670001',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        // 2. Member B in a separate Organization 2 undergoing onboarding
        $org2 = Organization::factory()->create();
        $userB = User::factory()->create(['organization_id' => $org2->id]);
        $userB->assignRole('Anggota');
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $org2->id,
            'user_id' => $userB->id,
            'name' => 'Anggota Org Dua',
            'identity_number' => '3202022345670002',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        // Member B attempts to submit onboarding specifying Member A's NIK (duplicate collision attempt)
        $response = $this->actingAs($userB)
            ->from(route('member.onboarding'))
            ->post(route('member.onboarding.submit'), [
                'name' => 'Anggota Org Dua Updated',
                'phone' => '081299990002',
                'address' => 'Jl. Organisasi Dua No. 2',
                'identity_number' => '3201012345670001',
            ]);

        $response->assertRedirect(route('member.onboarding'));
        $response->assertSessionHas('success');

        // Safe profile fields updated for Member B, but identity_number remains unchanged
        $freshB = $memberB->fresh();
        $this->assertSame('Anggota Org Dua Updated', $freshB->name);
        $this->assertSame('081299990002', $freshB->phone);
        $this->assertSame('3202022345670002', $freshB->identity_number);
        $this->assertSame($org2->id, $freshB->organization_id);

        // Member A in Org 1 remains completely unmutated with no cross-organization leakage
        $freshA = $memberA->fresh();
        $this->assertSame('Anggota Org Satu', $freshA->name);
        $this->assertSame('3201012345670001', $freshA->identity_number);
        $this->assertSame($this->organization->id, $freshA->organization_id);
    }

    // =========================================================================
    // MEM-003: Member Profile Retrieval (Self-Service)
    // =========================================================================

    public function test_mem_003_web_profile_resolves_authoritative_member_identity_ignoring_tampered_param(): void
    {
        $user = $this->createActiveMemberUser();
        $otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Target Korban',
        ]);

        // Attempt to view other member's profile via query parameter tampering
        $response = $this->actingAs($user)->get(route('member.profile', ['member_id' => $otherMember->id]));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Kojayaku/Profile')
            ->where('member.id', $user->cooperativeMember->id)
            ->where('member.name', $user->cooperativeMember->name)
        );
    }

    public function test_mem_003_api_profile_resolves_authoritative_member_identity_ignoring_tampered_param(): void
    {
        $user = $this->createActiveMemberUser();
        $otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Korban API',
        ]);

        Sanctum::actingAs($user, ['member:read']);

        $response = $this->getJson('/api/v1/member/profile?member_id='.$otherMember->id);
        $response->assertOk();
        $response->assertJsonPath('data.member.id', $user->cooperativeMember->id);
        $response->assertJsonPath('data.member.name', $user->cooperativeMember->name);
        $response->assertJsonPath('data.user.id', $user->id);
        $response->assertJsonPath('data.user.email', $user->email);
    }

    public function test_mem_003_user_without_cooperative_member_receives_403_on_api_profile(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');

        Sanctum::actingAs($user, ['member:read']);

        $response = $this->getJson('/api/v1/member/profile');
        $response->assertForbidden();
    }

    public function test_mem_003_profile_completeness_calculation_accuracy(): void
    {
        $service = app(MemberProfileCompletenessService::class);

        // Member with only minimal fields (name, email, jenis_anggota)
        $memberMinimal = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Anggota Minimal',
            'email' => 'minimal@example.com',
            'phone' => null,
            'address' => null,
            'identity_number' => null,
            'jenis_anggota' => 'AB',
            'kategori' => null,
        ]);

        $summaryMinimal = $service->summarize($memberMinimal);
        $this->assertSame(3, $summaryMinimal['completed_fields']);
        $this->assertSame(7, $summaryMinimal['total_fields']);
        $this->assertSame(43, $summaryMinimal['progress_percent']);
        $this->assertFalse($summaryMinimal['is_complete']);
        $this->assertCount(4, $summaryMinimal['missing']);

        // Member with all 7 required fields completed
        $memberComplete = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Anggota Lengkap',
            'email' => 'lengkap@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Lengkap No. 1',
            'identity_number' => '3201000000000002',
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);

        $summaryComplete = $service->summarize($memberComplete);
        $this->assertSame(7, $summaryComplete['completed_fields']);
        $this->assertSame(7, $summaryComplete['total_fields']);
        $this->assertSame(100, $summaryComplete['progress_percent']);
        $this->assertTrue($summaryComplete['is_complete']);
        $this->assertCount(0, $summaryComplete['missing']);
    }

    // =========================================================================
    // MEM-004: Member Profile Update (Phone / Address / Safe Fields)
    // =========================================================================

    public function test_mem_004_web_profile_update_updates_allowed_fields_and_strictly_ignores_immutable_fields(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        $payload = [
            'name' => 'Nama Baru Web',
            'email' => 'emailbaru@example.com',
            'phone' => '081288887777',
            'address' => 'Jl. Profil Baru No. 9',
            // Tamper attempts on immutable fields:
            'member_no' => 'TAMPERED-001',
            'status' => 'INACTIVE',
            'validation_status' => 'REJECTED',
            'organization_id' => 8888,
        ];

        $response = $this->actingAs($user)
            ->from(route('member.profile'))
            ->put(route('member.profile.update'), $payload);

        $response->assertRedirect(route('member.profile'));
        $response->assertSessionHas('success');

        $freshMember = $member->fresh();
        $this->assertSame('Nama Baru Web', $freshMember->name);
        $this->assertSame('emailbaru@example.com', $freshMember->email);
        $this->assertSame('081288887777', $freshMember->phone);
        $this->assertSame('Jl. Profil Baru No. 9', $freshMember->address);

        // Immutable fields strictly intact:
        $this->assertSame($member->member_no, $freshMember->member_no);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $freshMember->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $freshMember->validation_status);
        $this->assertSame($this->organization->id, $freshMember->organization_id);
    }

    public function test_mem_004_api_profile_update_updates_allowed_fields_via_member_profile_service(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        Sanctum::actingAs($user, ['member:write']);

        $payload = [
            'name' => 'Nama Baru API',
            'email' => 'apiupdate@example.com',
            'phone' => '081255554444',
            'address' => 'Jl. API Update No. 12',
            'gender' => 'L',
            'birth_date' => '1995-03-20',
            'birth_place' => 'Surabaya',
            'occupation' => 'Software Engineer',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '1370001234567',
            'bank_account_holder' => 'Nama Baru API',
        ];

        $response = $this->putJson('/api/v1/member/profile', $payload);
        $response->assertOk();
        $response->assertJsonPath('data.member.name', 'Nama Baru API');
        $response->assertJsonPath('data.member.phone', '081255554444');

        $freshMember = $member->fresh();
        $this->assertSame('Nama Baru API', $freshMember->name);
        $this->assertSame('081255554444', $freshMember->phone);
        $this->assertSame('Surabaya', $freshMember->tempat_lahir);
        $this->assertSame('Software Engineer', $freshMember->pekerjaan);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.profile.updated',
            'subject_id' => $member->id,
        ]);
    }

    public function test_mem_004_api_profile_update_strictly_ignores_immutable_fields_tamper(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        Sanctum::actingAs($user, ['member:write']);

        $payload = [
            'name' => 'Safe Name',
            'email' => $user->email,
            // Tamper attempts:
            'member_no' => 'HACKED-NO-999',
            'status' => 'PENDING',
            'validation_status' => 'PENDING',
            'organization_id' => 9999,
            'user_id' => 9999,
        ];

        $response = $this->putJson('/api/v1/member/profile', $payload);
        $response->assertOk();

        $freshMember = $member->fresh();
        $this->assertSame($member->member_no, $freshMember->member_no);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $freshMember->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $freshMember->validation_status);
        $this->assertSame($this->organization->id, $freshMember->organization_id);
        $this->assertSame($user->id, $freshMember->user_id);
    }

    public function test_mem_004_sso_linked_member_cannot_modify_email_directly(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;
        $member->update(['sso_provider' => 'google']);

        Sanctum::actingAs($user, ['member:write']);

        $payload = [
            'name' => 'Nama SSO',
            'email' => 'new-sso-email@example.com', // Attempting to change email on SSO account
        ];

        $response = $this->putJson('/api/v1/member/profile', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    // =========================================================================
    // MEM-005: Member Status Experience Resolution
    // =========================================================================

    public function test_mem_005_all_canonical_status_pairs_resolve_deterministically(): void
    {
        $matrix = [
            [CooperativeMember::VALIDATION_PENDING, CooperativeMember::VALIDATION_PENDING, MemberLifecycleExperience::WaitingVerification],
            [CooperativeMember::VALIDATION_PENDING, CooperativeMember::VALIDATION_PENDING_REVIEW, MemberLifecycleExperience::UnderReview],
            [CooperativeMember::VALIDATION_PENDING, 'PENDING_REVIEW', MemberLifecycleExperience::UnderReview],
            [CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_REVISION, MemberLifecycleExperience::RevisionRequired],
            [CooperativeMember::VALIDATION_INACTIVE, CooperativeMember::VALIDATION_REJECTED, MemberLifecycleExperience::Rejected],
            [CooperativeMember::VALIDATION_ACTIVE, CooperativeMember::VALIDATION_ACTIVE, MemberLifecycleExperience::Active],
            [CooperativeMember::VALIDATION_RESIGNED, CooperativeMember::VALIDATION_RESIGNED, MemberLifecycleExperience::BlockedUnknown],
            ['ACTIVE', 'REJECTED', MemberLifecycleExperience::BlockedUnknown],
            ['PENDING', 'ACTIVE', MemberLifecycleExperience::BlockedUnknown],
            [null, null, MemberLifecycleExperience::BlockedUnknown],
        ];

        foreach ($matrix as [$status, $validationStatus, $expectedExperience]) {
            $experience = MemberLifecycleExperience::fromStatuses($status, $validationStatus);
            $this->assertSame(
                $expectedExperience,
                $experience,
                "Failed resolving experience for pair [{$status}, {$validationStatus}]"
            );
        }

        // Null member resolution
        $this->assertSame(MemberLifecycleExperience::BlockedUnknown, MemberLifecycleExperience::fromMember(null));
    }

    public function test_mem_005_updating_validation_notes_does_not_alter_lifecycle_experience(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'validation_notes' => null,
            'admin_validation_notes' => null,
        ]);

        $this->assertSame(MemberLifecycleExperience::WaitingVerification, MemberLifecycleExperience::fromMember($member));

        // Update validation notes without changing status
        $member->update([
            'validation_notes' => 'Catatan admin untuk perbaikan dokumen KTP.',
            'admin_validation_notes' => 'Internal reviewer note: NIK verified.',
        ]);

        $this->assertSame(
            MemberLifecycleExperience::WaitingVerification,
            MemberLifecycleExperience::fromMember($member->fresh()),
            'Validation notes update must not corrupt lifecycle experience resolution'
        );
    }

    // =========================================================================
    // MEM-006: Member Resignation Request Submission & Loan Blocking
    // =========================================================================

    public function test_mem_006_active_member_without_loans_can_submit_resignation_request(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Pindah ke luar kota dan berganti pekerjaan.',
            'effective_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', MemberResignationRequest::STATUS_PENDING);

        $this->assertDatabaseHas('member_resignation_requests', [
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'status' => MemberResignationRequest::STATUS_PENDING,
            'reason' => 'Pindah ke luar kota dan berganti pekerjaan.',
        ]);
    }

    public function test_mem_006_resignation_submission_with_active_loans_must_block(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        // Create an active loan with outstanding balance for this member
        Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'outstanding_amount' => 5000000,
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Ingin berhenti dari koperasi.',
            'effective_date' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('member');

        // Verify no pending resignation request was created
        $this->assertDatabaseMissing('member_resignation_requests', [
            'cooperative_member_id' => $member->id,
        ]);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
    }

    public function test_mem_006_direct_admin_resignation_with_active_loans_must_block(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Pengurus Koperasi');

        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        // Create an active loan with outstanding balance
        Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'outstanding_amount' => 2500000,
        ]);

        $response = $this->actingAs($admin)
            ->from('/cooperative/members')
            ->post(route('cooperative.members.resign', $member));

        $response->assertSessionHasErrors('member');
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
    }

    public function test_mem_006_member_cannot_submit_duplicate_resignation_request(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'status' => MemberResignationRequest::STATUS_PENDING,
            'reason' => 'Pengajuan pertama',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Pengajuan kedua',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('member');
    }

    public function test_mem_006_already_resigned_member_cannot_submit_resignation_request(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'status' => 'RESIGNED',
            'validation_status' => CooperativeMember::VALIDATION_RESIGNED,
        ]);

        $service = app(\App\Services\Cooperative\MemberResignationRequestService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->submit($member, ['reason' => 'Resign again'], $user);
    }

    public function test_mem_006_member_can_cancel_pending_resignation_request(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        $resignation = MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'status' => MemberResignationRequest::STATUS_PENDING,
            'reason' => 'Ingin berhenti',
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->deleteJson('/api/v1/member/resignation');
        $response->assertOk();

        $this->assertSame(MemberResignationRequest::STATUS_CANCELLED, $resignation->fresh()->status);
    }

    public function test_mem_006_resignation_submission_with_pending_reward_redemptions_must_block(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        // Pending reward redemption exists for this member
        RewardRedemption::factory()->create([
            'cooperative_member_id' => $member->id,
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Ingin berhenti dari koperasi.',
            'effective_date' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('member');

        // Verify request rejected + no MemberResignationRequest created + status unchanged
        $this->assertDatabaseMissing('member_resignation_requests', [
            'cooperative_member_id' => $member->id,
        ]);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
    }

    public function test_mem_006_resignation_submission_with_unpaid_invoices_must_block(): void
    {
        $user = $this->createActiveMemberUser();
        $member = $user->cooperativeMember;

        $wajibType = CooperativeContributionType::firstOrCreate(
            ['code' => 'WAJIB'],
            [
                'name' => 'Simpanan Wajib',
                'category' => 'SAVINGS',
                'default_amount' => 50000,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ]
        );

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajibType->id,
            'period' => '2026-03',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($user, ['member:write']);

        $response = $this->postJson('/api/v1/member/resignation', [
            'reason' => 'Ingin berhenti dari koperasi.',
            'effective_date' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('member');

        // Verify request rejected + no MemberResignationRequest created + status unchanged
        $this->assertDatabaseMissing('member_resignation_requests', [
            'cooperative_member_id' => $member->id,
        ]);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
    }

    // =========================================================================
    // MEM-007: Member Account Link to SSO & Isolation
    // =========================================================================

    public function test_mem_007_admin_can_link_unlinked_verified_user_in_same_organization(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');

        $targetUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'email_verified_at' => now(),
        ]);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $targetUser->id,
                'reason' => 'business_verification',
            ]);

        $response->assertRedirect();
        $this->assertSame($targetUser->id, $member->fresh()->user_id);
        $this->assertTrue($targetUser->fresh()->hasRole('Anggota'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.account.link.completed',
            'subject_id' => $member->id,
        ]);
    }

    public function test_mem_007_cross_organization_linking_is_rejected_without_mutation(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');

        $otherOrg = Organization::factory()->create();
        $foreignUser = User::factory()->create([
            'organization_id' => $otherOrg->id,
            'email_verified_at' => now(),
        ]);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $foreignUser->id,
                'reason' => 'business_verification',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertNull($member->fresh()->user_id);
        $this->assertFalse($foreignUser->fresh()->hasRole('Anggota'));
    }

    public function test_mem_007_unverified_target_user_is_rejected_without_mutation(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');

        $unverifiedUser = User::factory()->unverified()->create([
            'organization_id' => $this->organization->id,
        ]);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $unverifiedUser->id,
                'reason' => 'business_verification',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertNull($member->fresh()->user_id);
    }

    public function test_mem_007_user_already_linked_to_another_member_is_rejected(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');

        $existingUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'email_verified_at' => now(),
        ]);

        // Member A already has this user
        CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $existingUser->id,
        ]);

        // Member B attempts to link the same user
        $memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('cooperative.members.account-link.store', $memberB), [
                'user_id' => $existingUser->id,
                'reason' => 'business_verification',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertNull($memberB->fresh()->user_id);
    }

    public function test_mem_007_privileged_roles_cannot_be_linked_as_member(): void
    {
        $admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $admin->assignRole('Admin Koperasi');

        $privilegedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'email_verified_at' => now(),
        ]);
        $privilegedUser->assignRole('System Admin');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $privilegedUser->id,
                'reason' => 'business_verification',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertNull($member->fresh()->user_id);
    }

    // =========================================================================
    // MEM-008: Member Status Consistency Check & Diagnostics
    // =========================================================================

    public function test_mem_008_consistency_report_identifies_valid_and_inconsistent_pairs(): void
    {
        $report = app(MemberStatusConsistencyReport::class);

        // Consistent member
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        // Inconsistent member (ACTIVE status but REJECTED validation)
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        $counts = $report->counts();
        $this->assertSame(2, $counts['total']);
        $this->assertSame(1, $counts['ACTIVE/ACTIVE']);
        $this->assertGreaterThan(0, $counts['ACTIVE/non-active-validation']);
        $this->assertGreaterThan(0, $report->manualReviewQuery()->count());
    }

    public function test_mem_008_consistency_audit_command_runs_successfully(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $exitCode = Artisan::call('members:audit-status-consistency');
        $this->assertSame(0, $exitCode);
    }

    public function test_mem_008_backfill_command_applies_deterministic_repairs_with_acknowledge(): void
    {
        // 1. Candidate 1: INACTIVE status with ACTIVE validation (terminal mismatch => deterministic repair)
        $inactiveMismatch = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        // 2. Candidate 2: RESIGNED status with ACTIVE validation (terminal mismatch => deterministic repair)
        $resignedMismatch = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'RESIGNED',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        // 3. Candidate 3: ACTIVE with REJECTED validation (contradictory pair => manual review candidate, must not be mutated automatically)
        $manualReview = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);

        // Dry-run: Must not mutate persistent state
        $dryRunExit = Artisan::call('members:backfill-status-consistency');
        $this->assertSame(0, $dryRunExit);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $inactiveMismatch->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $resignedMismatch->fresh()->validation_status);

        // Apply without acknowledge: Must fail-closed without mutating
        $missingAckExit = Artisan::call('members:backfill-status-consistency', ['--apply' => true]);
        $this->assertSame(1, $missingAckExit);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $inactiveMismatch->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $resignedMismatch->fresh()->validation_status);

        // Apply with acknowledge: Deterministic repair executed
        // Note: Command returns 1 if manual review candidates remain in database
        Artisan::call('members:backfill-status-consistency', [
            '--apply' => true,
            '--acknowledge' => true,
        ]);

        // Deterministic mismatches repaired to match terminal status
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $inactiveMismatch->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_RESIGNED, $resignedMismatch->fresh()->validation_status);

        // Manual review candidate protected from automatic corruption
        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $manualReview->fresh()->validation_status);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createActiveMemberUser(): User
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
