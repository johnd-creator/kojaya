<?php

namespace Tests\Feature\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\PreviewProofService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMemberManagementFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Organization $otherOrganization;

    protected User $admin;

    protected User $pengurus;

    protected User $memberUser;

    protected PreviewProofService $proofService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Config::set('cooperative.member_import_execution_enabled', true);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Unit Satu',
        ]);

        $this->otherOrganization = Organization::factory()->create([
            'code' => 'KOP-002',
            'name' => 'Koperasi Unit Dua',
        ]);

        // Unit-scoped Admin Koperasi with member management and validation permissions
        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Unit',
            'email' => 'admin.kop@kojaya.test',
        ]);
        $this->admin->assignRole('Admin Koperasi');
        $this->admin->givePermissionTo([
            'view_cooperative_member',
            'manage_cooperative_member',
            'validate_cooperative_member',
            'verify_cooperative_member',
            'import_cooperative_member_batch',
        ]);

        // Unit-scoped Pengurus Koperasi with final approval and PII viewing permissions
        $this->pengurus = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Pengurus Koperasi Unit',
            'email' => 'pengurus.kop@kojaya.test',
        ]);
        $this->pengurus->assignRole('Pengurus Koperasi');
        $this->pengurus->givePermissionTo([
            'view_cooperative_member',
            'approve_cooperative_member',
            'view_cooperative_member_pii',
        ]);

        // Standard Anggota user without staff permissions
        $this->memberUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Anggota Biasa',
            'email' => 'anggota.biasa@kojaya.test',
        ]);
        $this->memberUser->assignRole('Anggota');

        $this->proofService = app(PreviewProofService::class);
    }

    // =========================================================================
    // ADM-001: Member Directory Listing & Filters
    // =========================================================================

    public function test_adm_001_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('cooperative.members.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_adm_001_unauthorized_user_without_view_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->memberUser)
            ->get(route('cooperative.members.index'));

        $response->assertForbidden();
    }

    public function test_adm_001_admin_can_view_paginated_member_list(): void
    {
        CooperativeMember::factory()->count(18)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Index')
                ->has('members.data', 15)
                ->where('members.total', 18)
                ->where('members.per_page', 15)
                ->where('members.current_page', 1)
            );
    }

    public function test_adm_001_filter_by_search_matches_supported_text_fields(): void
    {
        $m1 = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Budi Pratama',
            'name' => 'Budi Pratama',
            'no_anggota' => '001',
            'member_no' => '001',
            'email' => 'budi@kojaya.test',
            'no_telp' => '0811111111',
            'phone' => '0811111111',
        ]);

        $m2 = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Siti Nurhaliza',
            'name' => 'Siti Nurhaliza',
            'no_anggota' => '002',
            'member_no' => '002',
            'email' => 'siti@kojaya.test',
            'no_telp' => '0822222222',
            'phone' => '0822222222',
        ]);

        // 1. Search by nama_anggota
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['search' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $m1->id)
            );

        // 2. Search by no_anggota
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['search' => '002']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $m2->id)
            );

        // 3. Search by email
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['search' => 'siti@kojaya.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $m2->id)
            );

        // 4. Search by no_telp
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['search' => '0811111111']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $m1->id)
            );
    }

    public function test_adm_001_filter_by_search_exact_nik_via_blind_index_for_pii_viewer(): void
    {
        $nik = '3171012301900088';
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Rian Hidayat',
            'identity_number' => $nik,
        ]);

        // 1. User with COOPERATIVE_MEMBER_PII_VIEW can search exact NIK via blind index
        $this->actingAs($this->pengurus)
            ->get(route('cooperative.members.index', ['search' => $nik]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $member->id)
            );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.pii.searched',
            'module' => 'cooperative.member',
            'user_id' => $this->pengurus->id,
        ]);

        // 2. User without PII permission cannot find member via NIK blind index search
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['search' => $nik]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 0)
            );
    }

    public function test_adm_001_filter_by_status_and_validation_status(): void
    {
        $active = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'ACT-01',
        ]);

        $resigned = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'RES-01',
            'status' => 'RESIGNED',
            'validation_status' => 'ACTIVE',
        ]);

        $pending = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'PEN-01',
        ]);

        $revision = CooperativeMember::factory()->revision()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'REV-01',
        ]);

        // Filter status=ACTIVE
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['status' => 'ACTIVE']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $active->id)
            );

        // Filter status=INACTIVE (matches INACTIVE and RESIGNED)
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['status' => 'INACTIVE']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 2)
            );

        // Filter validation_status=PENDING
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['validation_status' => 'PENDING']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $pending->id)
            );

        // Filter validation_status=REVISION
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['validation_status' => 'REVISION']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $revision->id)
            );
    }

    public function test_adm_001_filter_by_jenis_anggota_and_kategori(): void
    {
        $abMember = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);

        $albMember = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'jenis_anggota' => 'ALB',
            'kategori' => 'CDB',
        ]);

        // Filter jenis_anggota=ALB
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['jenis_anggota' => 'ALB']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $albMember->id)
            );

        // Filter kategori=IP
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', ['kategori' => 'IP']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $abMember->id)
            );
    }

    public function test_adm_001_multi_filter_combination_and_empty_result(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Eko Sulistyo',
            'status' => 'ACTIVE',
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);

        // Combination that matches 1 member
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', [
                'search' => 'Eko',
                'status' => 'ACTIVE',
                'jenis_anggota' => 'AB',
                'kategori' => 'IP',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
            );

        // Combination that matches 0 members
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index', [
                'search' => 'Eko',
                'jenis_anggota' => 'ALB',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 0)
            );
    }

    public function test_adm_001_organization_isolation_enforces_tenant_boundary(): void
    {
        $org1Member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Anggota Unit Satu',
        ]);

        $org2Member = CooperativeMember::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'nama_anggota' => 'Anggota Unit Dua',
        ]);

        // Unit-scoped Admin sees only own organization members
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.id', $org1Member->id)
            );

        // Global admin sees across all organizations
        $globalAdmin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'System Admin Global',
        ]);
        $globalAdmin->assignRole('System Admin');
        $globalAdmin->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);
        $globalAdmin->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_VIEW->value);

        $this->actingAs($globalAdmin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members.data', 2)
            );
    }

    // =========================================================================
    // ADM-002: Member Registration (Admin Direct)
    // =========================================================================

    public function test_adm_002_unauthorized_user_cannot_register_member(): void
    {
        $payload = [
            'tanggal_aktif' => '2026-06-01',
            'nama_anggota' => 'Calon Anggota',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ];

        $response = $this->actingAs($this->memberUser)
            ->post(route('cooperative.members.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseMissing('cooperative_members', ['nama_anggota' => 'Calon Anggota']);
    }

    public function test_adm_002_admin_can_register_member_with_valid_payload_and_generates_dues_invoice(): void
    {
        CooperativeContributionType::query()->create([
            'name' => 'Simpanan Pokok',
            'code' => 'POKOK',
            'type' => 'SAVINGS',
            'category' => 'POKOK',
            'frequency' => 'ONCE',
            'default_amount' => 100000,
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        $payload = [
            'tanggal_aktif' => '2026-06-01',
            'nama_anggota' => 'Bambang Sudarsono',
            'email' => 'bambang.sudarsono@example.com',
            'no_telp' => '081234567890',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.store'), $payload);

        $response->assertRedirect(route('cooperative.members.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cooperative_members', [
            'nama_anggota' => 'Bambang Sudarsono',
            'organization_id' => $this->organization->id,
            'status' => 'PENDING',
            'validation_status' => 'PENDING',
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);

        $createdMember = CooperativeMember::query()->where('email', 'bambang.sudarsono@example.com')->firstOrFail();

        // Verifies one-time POKOK dues invoice generation side effect
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $createdMember->id,
            'period' => '2026-06',
            'amount' => 100000,
            'status' => 'UNPAID',
        ]);
    }

    public function test_adm_002_duplicate_no_anggota_is_rejected_with_validation_error(): void
    {
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'KOP-DUP-01',
        ]);

        $payload = [
            'no_anggota' => 'KOP-DUP-01',
            'tanggal_aktif' => '2026-06-01',
            'nama_anggota' => 'Anggota Duplikat',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.store'), $payload);

        $response->assertSessionHasErrors('no_anggota');
        $this->assertSame(1, CooperativeMember::query()->where('no_anggota', 'KOP-DUP-01')->count());
    }

    public function test_adm_002_prohibited_fields_are_strictly_rejected(): void
    {
        $payload = [
            'tanggal_aktif' => '2026-06-01',
            'nama_anggota' => 'Anggota Serangan',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            // Prohibited fields
            'status' => 'ACTIVE',
            'validation_status' => 'ACTIVE',
            'user_id' => 999,
            'member_no' => 'ATTACK-999',
            'identity_number' => '3171012301900001',
            'npwp' => '01.234.567.8-901.000',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.store'), $payload);

        $response->assertSessionHasErrors([
            'status',
            'validation_status',
            'user_id',
            'member_no',
            'identity_number',
            'npwp',
        ]);

        $this->assertDatabaseMissing('cooperative_members', ['nama_anggota' => 'Anggota Serangan']);
    }

    public function test_adm_002_required_field_missing_prevents_creation_and_rolls_back(): void
    {
        $payload = [
            'nama_anggota' => '',
            'tanggal_aktif' => '',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.store'), $payload);

        $response->assertSessionHasErrors(['nama_anggota', 'tanggal_aktif']);
        $this->assertSame(0, CooperativeMember::count());
    }

    // =========================================================================
    // ADM-003: Member Detail & Sensitive Data Masking
    // =========================================================================

    public function test_adm_003_unauthorized_user_cannot_view_member_detail(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->get(route('cooperative.members.show', $member))
            ->assertRedirect(route('login'));

        $this->actingAs($this->memberUser)
            ->get(route('cooperative.members.show', $member))
            ->assertForbidden();
    }

    public function test_adm_003_cross_organization_member_detail_is_forbidden(): void
    {
        $memberOtherOrg = CooperativeMember::factory()->create([
            'organization_id' => $this->otherOrganization->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('cooperative.members.show', $memberOtherOrg))
            ->assertForbidden();
    }

    public function test_adm_003_admin_without_pii_permission_receives_server_side_masked_data(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'identity_number' => '3171012301901234',
            'npwp' => '01.234.567.8-901.000',
            'no_rekening' => '1234567890',
            'nama_bank' => 'Bank Mandiri',
            'nama_pemilik_rekening' => 'Pemilik Mandiri',
            'address' => 'Jl. Kebon Jeruk No. 5, Jakarta Barat',
            'notes' => 'Catatan sensitif verifikator',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('cooperative.members.show', $member));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Show')
                ->where('member.id', $member->id)
                // Sensitive fields masked
                ->where('member.identity_number', '************1234')
                ->where('member.npwp', '****************.000')
                ->where('member.no_rekening', '******7890')
                // Nullified fields
                ->where('member.nama_bank', null)
                ->where('member.nama_pemilik_rekening', null)
                ->where('member.address', null)
                ->where('member.notes', null)
            );

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'member.pii.viewed',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_adm_003_authorized_pii_viewer_receives_unmasked_data_and_logs_audit(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'identity_number' => '3171012301901234',
            'npwp' => '01.234.567.8-901.000',
            'no_rekening' => '1234567890',
            'nama_bank' => 'Bank Mandiri',
            'nama_pemilik_rekening' => 'Pemilik Mandiri',
            'address' => 'Jl. Kebon Jeruk No. 5, Jakarta Barat',
            'notes' => 'Catatan sensitif verifikator',
        ]);

        $response = $this->actingAs($this->pengurus)
            ->get(route('cooperative.members.show', $member));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Show')
                ->where('member.id', $member->id)
                // Sensitive fields completely unmasked
                ->where('member.identity_number', '3171012301901234')
                ->where('member.npwp', '01.234.567.8-901.000')
                ->where('member.no_rekening', '1234567890')
                ->where('member.nama_bank', 'Bank Mandiri')
                ->where('member.nama_pemilik_rekening', 'Pemilik Mandiri')
                ->where('member.address', 'Jl. Kebon Jeruk No. 5, Jakarta Barat')
                ->where('member.notes', 'Catatan sensitif verifikator')
            );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.pii.viewed',
            'module' => 'cooperative.member',
            'user_id' => $this->pengurus->id,
        ]);
    }

    // =========================================================================
    // ADM-004: Member Validation Review (Under Review)
    // =========================================================================

    public function test_adm_004_admin_can_validate_pending_member_to_pending_review(): void
    {
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.validate', $member), [
                'notes' => 'Berkas pendaftaran telah diverifikasi oleh admin koperasi.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $fresh->validation_status);
        $this->assertSame($this->admin->id, $fresh->admin_validated_by);
        $this->assertNotNull($fresh->admin_validated_at);
        $this->assertNull($fresh->validated_at);
    }

    public function test_adm_004_transition_from_already_active_status_is_rejected_with_conflict_409(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.validate', $member), [
                'notes' => 'Percobaan validasi ulang anggota aktif.',
            ]);

        $response->assertStatus(409);

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->validation_status);
        $this->assertSame('ACTIVE', $fresh->status);
        $this->assertNull($fresh->admin_validated_at);
    }

    public function test_adm_004_unauthorized_user_cannot_validate_member(): void
    {
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->actingAs($this->memberUser)
            ->post(route('cooperative.members.validate', $member))
            ->assertForbidden();

        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
    }

    // =========================================================================
    // ADM-005: Member Revision Request
    // =========================================================================

    public function test_adm_005_validator_can_request_revision_with_valid_notes(): void
    {
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.request-revision', $member), [
                'notes' => 'Foto identitas KTP tidak jelas, mohon unggah ulang dokumen yang tajam.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $fresh->status);
        $this->assertSame('Foto identitas KTP tidak jelas, mohon unggah ulang dokumen yang tajam.', $fresh->validation_notes);
        $this->assertSame($this->admin->id, $fresh->validated_by);
    }

    public function test_adm_005_revision_request_requires_notes_between_5_and_1000_chars(): void
    {
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
        ]);

        // 1. Empty notes
        $this->actingAs($this->admin)
            ->post(route('cooperative.members.request-revision', $member), ['notes' => ''])
            ->assertSessionHasErrors('notes');

        // 2. Too short notes (< 5 chars)
        $this->actingAs($this->admin)
            ->post(route('cooperative.members.request-revision', $member), ['notes' => 'cek'])
            ->assertSessionHasErrors('notes');

        // 3. Too long notes (> 1000 chars)
        $this->actingAs($this->admin)
            ->post(route('cooperative.members.request-revision', $member), ['notes' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('notes');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
    }

    public function test_adm_005_revision_request_rejected_if_not_in_pending_review(): void
    {
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.request-revision', $member), [
                'notes' => 'Catatan revisi sebelum verifikasi awal.',
            ]);

        $response->assertStatus(409);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
    }

    // =========================================================================
    // ADM-006: Member Final Approval (Pengurus Only)
    // =========================================================================

    public function test_adm_006_pengurus_can_final_approve_member(): void
    {
        $user = User::factory()->create(['organization_id' => $this->organization->id]);

        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'admin_validated_at' => now(),
            'admin_validated_by' => $this->admin->id,
        ]);

        $this->assertFalse($user->hasRole('Anggota'));

        $response = $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.approve-final', $member), [
                'notes' => 'Persetujuan keanggotaan disetujui resmi oleh Pengurus Koperasi.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->status);
        $this->assertSame($this->pengurus->id, $fresh->validated_by);
        $this->assertNotNull($fresh->validated_at);
        $this->assertTrue($user->fresh()->hasRole('Anggota'));
    }

    public function test_adm_006_admin_koperasi_cannot_final_approve_member_must_403(): void
    {
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
            'admin_validated_at' => now(),
            'admin_validated_by' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.approve-final', $member), [
                'notes' => 'Admin mencoba menyetujui final.',
            ]);

        $response->assertForbidden();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
    }

    public function test_adm_006_maker_checker_segregation_verifier_cannot_final_approve(): void
    {
        // Pengurus validated the member in step 1
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
            'admin_validated_at' => now(),
            'admin_validated_by' => $this->pengurus->id,
        ]);

        // Same Pengurus attempts to final approve in step 2
        $response = $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.approve-final', $member), [
                'notes' => 'Mencoba approve sendiri.',
            ]);

        $response->assertSessionHasErrors('approved_by');
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
        $this->assertNull($member->fresh()->validated_at);
    }

    public function test_adm_006_final_approval_rejected_if_not_in_pending_review(): void
    {
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.approve-final', $member), [
                'notes' => 'Approve langsung tanpa verifikasi awal.',
            ]);

        $response->assertStatus(409);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
    }

    // =========================================================================
    // ADM-007: Member Rejection (Pengurus / Validator)
    // =========================================================================

    public function test_adm_007_validator_can_reject_member_with_valid_notes(): void
    {
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.reject', $member), [
                'notes' => 'Dokumen identitas terbukti tidak valid dan ditolak.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $fresh->status);
        $this->assertSame($this->pengurus->id, $fresh->validated_by);
        $this->assertSame('Dokumen identitas terbukti tidak valid dan ditolak.', $fresh->validation_notes);
    }

    public function test_adm_007_rejection_requires_notes_between_5_and_1000_chars(): void
    {
        $member = CooperativeMember::factory()->pendingReview()->create([
            'organization_id' => $this->organization->id,
        ]);

        // 1. Empty notes
        $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.reject', $member), ['notes' => ''])
            ->assertSessionHasErrors('notes');

        // 2. Too short notes (< 5 chars)
        $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.reject', $member), ['notes' => 'tolk'])
            ->assertSessionHasErrors('notes');

        // 3. Too long notes (> 1000 chars)
        $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.reject', $member), ['notes' => str_repeat('b', 1001)])
            ->assertSessionHasErrors('notes');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
    }

    public function test_adm_007_rejection_rejected_if_not_in_pending_review(): void
    {
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->pengurus)
            ->post(route('cooperative.members.reject', $member), [
                'notes' => 'Ditolak sebelum melalui review operasional.',
            ]);

        $response->assertStatus(409);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->validation_status);
    }

    // =========================================================================
    // ADM-008: Member Batch Import (Upload, Preview, Execute)
    // =========================================================================

    public function test_adm_008_unauthorized_user_cannot_access_import(): void
    {
        $this->actingAs($this->memberUser)
            ->get(route('cooperative.members.import'))
            ->assertForbidden();

        $this->actingAs($this->memberUser)
            ->post(route('cooperative.members.import.preview'))
            ->assertForbidden();

        $this->actingAs($this->memberUser)
            ->post(route('cooperative.members.import.execute'))
            ->assertForbidden();
    }

    public function test_adm_008_preview_validates_canonical_12_headers(): void
    {
        // 11 headers (missing 'notes')
        $invalidHeaders = array_slice(MemberImportValidator::CANONICAL_HEADERS, 0, 11);
        $row = $this->validImportRow();
        $csvContent = $this->generateCsv($invalidHeaders, [$row]);
        $file = UploadedFile::fake()->createWithContent('invalid_headers.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
                'organization_id' => (string) $this->organization->id,
            ]);

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.valid', false)
                ->where('preview.errors.0.code', MemberImportValidator::CODE_INVALID_HEADER)
            );
    }

    public function test_adm_008_syntax_or_row_validation_error_prevents_batch_execution_and_rolls_back(): void
    {
        $validRow = $this->validImportRow([
            'member_number' => 'KOP-VALID-1',
            'full_name' => 'Anggota Valid',
            'email' => 'valid@kojaya.test',
            'phone_number' => '081234560001',
            'identity_number' => '3171012301900001',
        ]);

        $invalidRow = $this->validImportRow([
            'member_number' => 'KOP-INVALID-2',
            'full_name' => 'Anggota Rusak',
            'email' => 'invalid-email-format',
            'phone_number' => '123', // invalid phone format
            'identity_number' => '123', // invalid NIK length
        ]);

        $setup = $this->createUploadWithProof([$validRow, $invalidRow]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $response->assertSessionHasErrors(['execution']);

        // Assert atomic transaction rollback: 0 members persisted
        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_adm_008_successful_batch_import_creates_members_and_audits(): void
    {
        $row1 = $this->validImportRow([
            'member_number' => 'KOP-701',
            'full_name' => 'Impor Anggota Pertama',
            'email' => 'impor1@kojaya.test',
            'phone_number' => '081234560011',
            'identity_number' => '3171012301900011',
        ]);

        $row2 = $this->validImportRow([
            'member_number' => 'KOP-702',
            'full_name' => 'Impor Anggota Kedua',
            'email' => 'impor2@kojaya.test',
            'phone_number' => '081234560022',
            'identity_number' => '3171012301900022',
        ]);

        $setup = $this->createUploadWithProof([$row1, $row2]);

        $response = $this->actingAs($this->admin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $response->assertRedirect(route('cooperative.members.import'))
            ->assertSessionHas('success');

        // Assert exactly 2 members persisted in organization
        $this->assertSame(2, CooperativeMember::where('organization_id', $this->organization->id)->count());

        $this->assertDatabaseHas('cooperative_members', [
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Impor Anggota Pertama',
            'email' => 'impor1@kojaya.test',
        ]);

        $this->assertDatabaseHas('cooperative_members', [
            'organization_id' => $this->organization->id,
            'nama_anggota' => 'Impor Anggota Kedua',
            'email' => 'impor2@kojaya.test',
        ]);

        // Assert audit log completed
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.import.completed',
            'module' => 'cooperative',
            'user_id' => $this->admin->id,
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

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
    private function validImportRow(array $overrides = []): array
    {
        return array_merge([
            'member_number' => 'KOP-001',
            'full_name' => 'Ahmad Pratama',
            'email' => 'ahmad.pratama@example.com',
            'phone_number' => '081234560001',
            'identity_number' => '3171012301900001',
            'gender' => 'L',
            'company_code' => 'IP',
            'employee_number' => null,
            'address' => 'Jl. Merdeka No. 10, Jakarta Pusat',
            'membership_type' => 'AB',
            'join_date' => '2026-06-01',
            'notes' => 'Catatan anggota aktif',
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>|null  $headers
     * @return array{file: UploadedFile, proof: string, hash: string}
     */
    private function createUploadWithProof(
        array $rows,
        ?string $orgId = null,
        string $importDate = '2026-06-01',
        ?array $headers = null,
    ): array {
        $headers ??= MemberImportValidator::CANONICAL_HEADERS;
        $csvContent = $this->generateCsv($headers, $rows);
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);
        $hash = hash('sha256', $csvContent);
        $orgId ??= (string) $this->organization->id;
        $proof = $this->proofService->generate($hash, $orgId, $importDate);

        return ['file' => $file, 'proof' => $proof, 'hash' => $hash];
    }
}
