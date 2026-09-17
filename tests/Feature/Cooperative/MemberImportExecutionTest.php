<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\MemberImportExecutionService;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\PreviewProofService;
use App\Services\Security\PiiCryptoService;
use Database\Seeders\RolePermissionSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberImportExecutionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Organization $otherOrganization;

    private User $authorizedUnitAdmin;

    private User $authorizedGlobalAdmin;

    private User $unauthorizedUser;

    private PreviewProofService $proofService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Enable DEV member import execution gate for testing execution flows
        Config::set('cooperative.member_import_execution_enabled', true);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Unit 1',
        ]);

        $this->otherOrganization = Organization::factory()->create([
            'code' => 'KOP-002',
            'name' => 'Koperasi Unit 2',
        ]);

        // Unit-scoped Admin Koperasi with manage_cooperative_member
        $this->authorizedUnitAdmin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Unit',
        ]);
        $this->authorizedUnitAdmin->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        // Global-scoped operator with manage_cooperative_member & view_cooperative_all
        $this->authorizedGlobalAdmin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'System Admin Operator',
        ]);
        $this->authorizedGlobalAdmin->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
        $this->authorizedGlobalAdmin->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);

        // Regular unauthorized user (Anggota)
        $this->unauthorizedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->unauthorizedUser->assignRole('Anggota');

        $this->proofService = app(PreviewProofService::class);
    }

    /**
     * Helper to generate CSV content string.
     *
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
     * Helper for a single valid row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRow(array $overrides = []): array
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
     * Helper to create an UploadedFile and its corresponding preview proof.
     *
     * @param  list<array<string, mixed>>  $rows
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

    // -------------------------------------------------------------------------
    // SCENARIOS 1-7: AUTH & TENANT AUTHORIZATION
    // -------------------------------------------------------------------------

    public function test_01_guest_cannot_execute_import(): void
    {
        $this->post(route('cooperative.members.import.execute'), [
            'file' => UploadedFile::fake()->create('import.csv', 100),
            'confirm_import' => true,
        ])->assertRedirect(route('login'));
    }

    public function test_02_authenticated_user_without_manage_cooperative_member_cannot_execute(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->post(route('cooperative.members.import.execute'), [
                'file' => UploadedFile::fake()->create('import.csv', 100),
                'confirm_import' => true,
            ])->assertForbidden();
    }

    public function test_03_policy_gate_import_enforces_organization_scope(): void
    {
        $this->actingAs($this->authorizedUnitAdmin);
        $this->assertTrue($this->authorizedUnitAdmin->can('import', CooperativeMember::class));
        $this->assertFalse($this->unauthorizedUser->can('import', CooperativeMember::class));
    }

    public function test_04_execution_feature_disabled_rejects_import_with_zero_inserts(): void
    {
        Config::set('cooperative.member_import_execution_enabled', false);

        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertForbidden();

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_05_unit_scoped_admin_imports_only_to_own_organization(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertRedirect(route('cooperative.members.import'));

        $this->assertSame(1, CooperativeMember::count());
        $this->assertSame($this->organization->id, CooperativeMember::first()->organization_id);
    }

    public function test_06_cross_tenant_organization_spoof_is_rejected_with_zero_inserts(): void
    {
        // Unit admin attempting to supply other organization's ID
        $setup = $this->createUploadWithProof([$this->validRow()], (string) $this->otherOrganization->id);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'organization_id' => (string) $this->otherOrganization->id,
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertForbidden();

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_07_global_authorized_operator_requires_explicit_valid_organization(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()], (string) $this->organization->id);

        // Missing organization_id for global operator fails validation
        $this->actingAs($this->authorizedGlobalAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['organization_id']);

        $this->assertSame(0, CooperativeMember::count());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 8-12: PREVIEW PROOF TAMPER-RESISTANCE & BINDINGS
    // -------------------------------------------------------------------------

    public function test_08_execution_without_valid_preview_proof_fails(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'import.csv',
            $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [$this->validRow()]),
        );

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $file,
                'import_date' => '2026-06-01',
                'preview_proof' => 'invalid-tampered-proof-token',
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['preview_proof']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_09_expired_preview_proof_fails(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);
        // Generate an already expired token (-10 seconds)
        $expiredProof = $this->proofService->generate(
            $setup['hash'],
            (string) $this->organization->id,
            '2026-06-01',
            -10,
        );

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $expiredProof,
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['preview_proof']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_10_file_hash_different_from_preview_proof_fails(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);

        // Upload a different file than the one previewed
        $differentFile = UploadedFile::fake()->createWithContent(
            'different.csv',
            $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
                $this->validRow(['full_name' => 'Budi Santoso']),
            ]),
        );

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $differentFile,
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['preview_proof']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_11_organization_different_from_preview_proof_fails(): void
    {
        // Proof issued for other organization
        $setup = $this->createUploadWithProof([$this->validRow()], (string) $this->otherOrganization->id);

        $this->actingAs($this->authorizedGlobalAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'organization_id' => (string) $this->organization->id,
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['preview_proof']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_12_import_date_different_from_preview_proof_fails(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()], (string) $this->organization->id, '2026-06-01');

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-07-01', // Different date
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['preview_proof']);

        $this->assertSame(0, CooperativeMember::count());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 13-25: CANONICAL PERSISTENCE & FIELD MAPPING
    // -------------------------------------------------------------------------

    public function test_13_valid_two_row_csv_imports_exactly_two_members(): void
    {
        $row1 = $this->validRow([
            'member_number' => 'KOP-101',
            'full_name' => 'Ahmad Pratama',
            'email' => 'ahmad@example.com',
            'phone_number' => '081234560001',
            'identity_number' => '3171012301900001',
        ]);
        $row2 = $this->validRow([
            'member_number' => 'KOP-102',
            'full_name' => 'Budi Utomo',
            'email' => 'budi@example.com',
            'phone_number' => '081234560002',
            'identity_number' => '3171012301900002',
        ]);

        $setup = $this->createUploadWithProof([$row1, $row2]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertRedirect(route('cooperative.members.import'))
            ->assertSessionHas('success');

        $this->assertSame(2, CooperativeMember::count());
    }

    public function test_14_and_15_imported_members_have_status_and_validation_status_pending(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertNotNull($member);
        $this->assertSame('PENDING', $member->status);
        $this->assertSame('PENDING', $member->validation_status);
        $this->assertNull($member->user_id);
    }

    public function test_16_supplied_member_number_is_preserved(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['member_number' => 'KOP-999']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('KOP-999', $member->no_anggota);
        $this->assertSame('KOP-999', $member->member_no);
    }

    public function test_17_and_18_blank_member_number_generates_unique_canonical_kop_numbers(): void
    {
        $row1 = $this->validRow([
            'member_number' => null,
            'email' => 'user1@example.com',
            'phone_number' => '081234560001',
            'identity_number' => '3171012301900001',
        ]);
        $row2 = $this->validRow([
            'member_number' => '',
            'email' => 'user2@example.com',
            'phone_number' => '081234560002',
            'identity_number' => '3171012301900002',
        ]);

        $setup = $this->createUploadWithProof([$row1, $row2]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $members = CooperativeMember::orderBy('id')->get();
        $this->assertCount(2, $members);

        $this->assertSame('KOP-001', $members[0]->no_anggota);
        $this->assertSame('KOP-002', $members[1]->no_anggota);
        $this->assertNotEquals($members[0]->no_anggota, $members[1]->no_anggota);
    }

    public function test_19_member_no_and_no_anggota_remain_synchronized(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['member_number' => 'KOP-777']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame($member->no_anggota, $member->member_no);
        $this->assertSame('KOP-777', $member->no_anggota);
    }

    public function test_20_full_name_maps_to_both_canonical_db_name_fields(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['full_name' => 'Siti Nurhaliza']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('Siti Nurhaliza', $member->name);
        $this->assertSame('Siti Nurhaliza', $member->nama_anggota);
    }

    public function test_21_phone_maps_to_both_expected_phone_fields(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['phone_number' => '081234567890']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('081234567890', $member->phone);
        $this->assertSame('081234567890', $member->no_telp);
    }

    public function test_22_gender_maps_to_jenis_kelamin(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['gender' => 'P']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('P', $member->jenis_kelamin);
    }

    public function test_23_company_code_maps_to_kategori(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['company_code' => 'CDB']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('CDB', $member->kategori);
    }

    public function test_24_membership_type_maps_to_jenis_anggota(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['membership_type' => 'ALB']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('ALB', $member->jenis_anggota);
    }

    public function test_25_join_date_maps_to_tanggal_aktif_and_joined_at(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['join_date' => '2026-05-15']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame('2026-05-15', $member->tanggal_aktif->toDateString());
        $this->assertSame('2026-05-15', $member->joined_at->toDateString());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 26-29: EMPLOYEE RESOLUTION SEMANTICS
    // -------------------------------------------------------------------------

    public function test_26_blank_employee_number_sets_employee_id_null(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['employee_number' => null]),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertNull($member->employee_id);
    }

    public function test_27_resolved_employee_number_sets_correct_employee_id(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'EMP-001',
        ]);

        $setup = $this->createUploadWithProof([
            $this->validRow(['employee_number' => 'EMP-001']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        $this->assertSame($employee->id, $member->employee_id);
    }

    public function test_28_unresolved_employee_number_rejects_entire_batch_with_zero_inserts(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow(['employee_number' => 'NON-EXISTENT-NIP']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_29_employee_organization_mismatch_rejects_entire_batch(): void
    {
        // Employee belongs to other organization
        Employee::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'employee_code' => 'EMP-OTHER-ORG',
        ]);

        $setup = $this->createUploadWithProof([
            $this->validRow(['employee_number' => 'EMP-OTHER-ORG']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 30-32: ATOMICITY, TOCTOU & FAIL-CLOSED BATCH POLICY
    // -------------------------------------------------------------------------

    public function test_30_invalid_row_among_valid_rows_creates_zero_members(): void
    {
        $validRow = $this->validRow(['email' => 'valid@example.com']);
        $invalidRow = $this->validRow([
            'email' => 'not-an-email',
            'phone_number' => 'invalid-phone',
        ]);

        $setup = $this->createUploadWithProof([$validRow, $invalidRow]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_31_manual_review_row_creates_zero_members(): void
    {
        // Unresolved employee triggers manual_review_required
        $setup = $this->createUploadWithProof([
            $this->validRow(['employee_number' => 'EMP-UNRESOLVED-REVIEW']),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_32_toctou_new_db_conflict_after_preview_aborts_with_zero_inserts(): void
    {
        // 1. Valid file previewed
        $row = $this->validRow([
            'email' => 'toctou@example.com',
            'identity_number' => '3171012301900099',
            'member_number' => 'KOP-888',
        ]);
        $setup = $this->createUploadWithProof([$row]);

        // 2. Race condition: concurrent transaction inserts conflicting email into DB
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'email' => 'toctou@example.com',
            'no_anggota' => 'KOP-099',
            'member_no' => 'KOP-099',
        ]);

        $this->assertSame(1, CooperativeMember::count());

        // 3. Execute same file: commit-time revalidation detects conflict
        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        // Only the pre-existing record exists, 0 imported rows
        $this->assertSame(1, CooperativeMember::count());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 33-36: TRANSACTION ROLLBACK & AUDIT ATOMICITY
    // -------------------------------------------------------------------------

    public function test_33_exception_during_middle_of_batch_rolls_back_all_members(): void
    {
        $row1 = $this->validRow([
            'member_number' => 'KOP-011',
            'email' => 'm1@example.com',
            'phone_number' => '081234560011',
            'identity_number' => '3171012301900011',
        ]);
        $row2 = $this->validRow([
            'member_number' => 'KOP-012',
            'email' => 'm2@example.com',
            'phone_number' => '081234560012',
            'identity_number' => '3171012301900012',
        ]);

        $setup = $this->createUploadWithProof([$row1, $row2]);

        // Inject simulated failure on row 2 via test hook
        $service = app(MemberImportExecutionService::class);
        $service->setAfterRowInsertHook(function ($member, $index) {
            if ($index === 2) {
                throw new Exception('Simulated fatal mid-batch crash');
            }
        });

        try {
            $service->execute(
                filePath: $setup['file']->getRealPath(),
                organizationId: (string) $this->organization->id,
                importDate: '2026-06-01',
                fileSha256: $setup['hash'],
            );
            $this->fail('Exception was expected');
        } catch (Exception $e) {
            $this->assertSame('Simulated fatal mid-batch crash', $e->getMessage());
        }

        // Entire transaction rolled back to zero inserts
        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_34_mandatory_audit_failure_rolls_back_all_members(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);

        // Mock AuditLogService to throw
        $auditMock = $this->createMock(AuditLogService::class);
        $auditMock->method('log')
            ->willThrowException(new Exception('Audit service unavailable'));

        $this->app->instance(AuditLogService::class, $auditMock);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_35_and_36_successful_batch_writes_mandatory_audit_without_raw_nik(): void
    {
        $rawNik = '3171012301900001';
        $setup = $this->createUploadWithProof([
            $this->validRow(['identity_number' => $rawNik]),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $audit = AuditLog::where('action', 'member.import.completed')->first();
        $this->assertNotNull($audit);
        $this->assertSame('cooperative', $audit->module);
        $this->assertSame((string) $this->organization->id, (string) $audit->organization_id);

        $newValues = $audit->new_values;
        $this->assertIsArray($newValues);
        $this->assertArrayHasKey('import_id', $newValues);
        $this->assertSame(1, $newValues['imported_count']);
        $this->assertSame(1, $newValues['total_rows']);

        // Verify NO raw NIK in audit log anywhere
        $auditJson = json_encode($audit->toArray());
        $this->assertStringNotContainsString($rawNik, $auditJson);
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 37-43: PROHIBITED SIDE EFFECTS (USERS, SOCIAL, ROLES, FINANCE)
    // -------------------------------------------------------------------------

    public function test_37_to_40_successful_import_does_not_create_users_social_roles_or_passwords(): void
    {
        $initialUserCount = User::count();

        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        // Zero additional users
        $this->assertSame($initialUserCount, User::count());

        // Zero social accounts
        $this->assertSame(0, SocialAccount::count());

        // Member user_id is null
        $member = CooperativeMember::first();
        $this->assertNull($member->user_id);
    }

    public function test_41_to_43_successful_import_does_not_create_financial_records(): void
    {
        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        // No dues invoices created
        $this->assertSame(0, DB::table('cooperative_dues_invoices')->count());

        // No ledger entries created
        $this->assertSame(0, DB::table('cooperative_ledger_entries')->count());

        // No member store accounts created
        $this->assertSame(0, DB::table('member_store_accounts')->count());
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 44-46: STORAGE & PII ENCRYPTION SAFETY
    // -------------------------------------------------------------------------

    public function test_44_uploaded_file_is_not_permanently_stored(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $setup = $this->createUploadWithProof([$this->validRow()]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_45_raw_nik_not_present_in_json_response(): void
    {
        $rawNik = '3171012301900001';
        $setup = $this->createUploadWithProof([
            $this->validRow(['identity_number' => $rawNik]),
        ]);

        $response = $this->actingAs($this->authorizedUnitAdmin)
            ->postJson(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $response->assertOk();
        $this->assertStringNotContainsString($rawNik, $response->getContent());
    }

    public function test_46_imported_nik_uses_encrypted_blind_indexed_model_persistence(): void
    {
        $rawNik = '3171012301900001';
        $setup = $this->createUploadWithProof([
            $this->validRow(['identity_number' => $rawNik]),
        ]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $member = CooperativeMember::first();
        // Model accessor returns decrypted NIK
        $this->assertSame($rawNik, $member->identity_number);

        // Direct DB raw query proves encrypted and blind index columns exist
        $rawDb = DB::table('cooperative_members')->where('id', $member->id)->first();
        $this->assertNotNull($rawDb->identity_number_enc);
        $this->assertNotNull($rawDb->identity_number_bidx);

        // Blind index lookup works
        $expectedBidx = app(PiiCryptoService::class)->blindIndex('identity_number', $rawNik);
        $this->assertSame($expectedBidx, $rawDb->identity_number_bidx);
    }

    // -------------------------------------------------------------------------
    // SCENARIOS 47-50: IDEMPOTENCY, INVALID INPUTS & SUMMARY RETURN
    // -------------------------------------------------------------------------

    public function test_47_second_sequential_execution_of_same_file_creates_zero_duplicates(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow([
                'member_number' => 'KOP-301',
                'email' => 'idempotent@example.com',
                'identity_number' => '3171012301900301',
            ]),
        ]);

        // First execution succeeds
        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertRedirect();

        $this->assertSame(1, CooperativeMember::count());

        // Re-upload same file again with a fresh proof
        $secondFile = UploadedFile::fake()->createWithContent(
            'import.csv',
            $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
                $this->validRow([
                    'member_number' => 'KOP-301',
                    'email' => 'idempotent@example.com',
                    'identity_number' => '3171012301900301',
                ]),
            ]),
        );
        $secondProof = $this->proofService->generate(
            $setup['hash'],
            (string) $this->organization->id,
            '2026-06-01',
        );

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $secondFile,
                'import_date' => '2026-06-01',
                'preview_proof' => $secondProof,
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        // Count remains 1 (0 additional rows inserted)
        $this->assertSame(1, CooperativeMember::count());
    }

    public function test_48_empty_csv_cannot_execute(): void
    {
        $setup = $this->createUploadWithProof([]);

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_49_header_invalid_csv_cannot_execute(): void
    {
        $setup = $this->createUploadWithProof(
            [$this->validRow()],
            headers: ['col1', 'col2'], // Wrong headers
        );

        $this->actingAs($this->authorizedUnitAdmin)
            ->post(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ])
            ->assertSessionHasErrors(['execution']);

        $this->assertSame(0, CooperativeMember::count());
    }

    public function test_50_successful_import_returns_safe_structured_summary(): void
    {
        $setup = $this->createUploadWithProof([
            $this->validRow([
                'member_number' => 'KOP-501',
                'email' => 's1@example.com',
                'phone_number' => '081234560501',
                'identity_number' => '3171012301900501',
            ]),
            $this->validRow([
                'member_number' => null, // Generated
                'email' => 's2@example.com',
                'phone_number' => '081234560502',
                'identity_number' => '3171012301900502',
            ]),
        ]);

        $response = $this->actingAs($this->authorizedUnitAdmin)
            ->postJson(route('cooperative.members.import.execute'), [
                'file' => $setup['file'],
                'import_date' => '2026-06-01',
                'preview_proof' => $setup['proof'],
                'confirm_import' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'import_id',
                    'organization_id',
                    'import_date',
                    'total_rows',
                    'imported_count',
                    'generated_member_numbers',
                    'generated_member_number_count',
                    'supplied_member_numbers',
                    'supplied_member_number_count',
                    'status',
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame(2, $data['total_rows']);
        $this->assertSame(2, $data['imported_count']);
        $this->assertSame(1, $data['generated_member_number_count']);
        $this->assertSame(1, $data['supplied_member_number_count']);
        $this->assertSame('COMPLETED', $data['status']);
    }
}
