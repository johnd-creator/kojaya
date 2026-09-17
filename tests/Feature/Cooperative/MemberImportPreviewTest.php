<?php

namespace Tests\Feature\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Cooperative\MemberImportValidator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $authorizedUnitUser;

    private User $authorizedGlobalUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Unit 1',
        ]);

        // Unit-scoped user with manage_cooperative_member
        $this->authorizedUnitUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->authorizedUnitUser->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);

        // Global-scoped user with manage_cooperative_member and view_cooperative_all
        $this->authorizedGlobalUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->authorizedGlobalUser->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
        $this->authorizedGlobalUser->givePermissionTo(PermissionEnum::COOPERATIVE_VIEW_ALL->value);

        // Unauthorized user without manage_cooperative_member
        $this->unauthorizedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->unauthorizedUser->assignRole('Anggota');
    }

    /**
     * Helper to generate canonical CSV content from rows.
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

    public function test_guest_cannot_access_import_page(): void
    {
        $this->get(route('cooperative.members.import'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_manage_cooperative_member_cannot_access_import_page(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('cooperative.members.import'))
            ->assertForbidden();
    }

    public function test_authorized_manager_can_open_import_page(): void
    {
        $this->actingAs($this->authorizedUnitUser)
            ->get(route('cooperative.members.import'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('is_global', false)
                ->where('current_organization_id', (string) $this->organization->id)
                ->has('organizations', 1)
                ->where('default_import_date', now()->toDateString())
                ->where('preview', null)
            );
    }

    public function test_authorized_manager_sees_expected_canonical_guidance(): void
    {
        $this->actingAs($this->authorizedUnitUser)
            ->get(route('cooperative.members.import'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('canonical_headers', MemberImportValidator::CANONICAL_HEADERS)
            );
    }

    public function test_authorized_manager_can_download_template_csv(): void
    {
        $response = $this->actingAs($this->authorizedUnitUser)
            ->get(route('cooperative.members.import.template'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString(implode(',', MemberImportValidator::CANONICAL_HEADERS), $content);
    }

    public function test_missing_csv_fails_request_validation(): void
    {
        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'import_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors(['file']);
    }

    public function test_xlsx_upload_fails_validation(): void
    {
        $xlsxFile = UploadedFile::fake()->create('members.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $xlsxFile,
                'import_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors(['file']);
    }

    public function test_oversized_file_fails_validation(): void
    {
        // 11 MB file exceeds 10 MB limit
        $largeFile = UploadedFile::fake()->create('large.csv', 11264, 'text/csv');

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $largeFile,
                'import_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors(['file']);
    }

    public function test_invalid_header_returns_safe_preview_failure(): void
    {
        $badHeaders = ['member_number', 'full_name', 'email']; // Only 3 columns
        $csvContent = $this->generateCsv($badHeaders, [
            ['member_number' => 'KOP-001', 'full_name' => 'Budi', 'email' => 'budi@example.com'],
        ]);
        $file = UploadedFile::fake()->createWithContent('invalid_headers.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.header_valid', false)
                ->where('preview.valid', false)
                ->where('preview.total_rows', 0)
                ->has('preview.errors', 1)
            );
    }

    public function test_valid_canonical_csv_produces_preview(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(),
        ]);
        $file = UploadedFile::fake()->createWithContent('valid.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.header_valid', true)
                ->where('preview.valid', true)
                ->where('preview.total_rows', 1)
                ->where('preview.valid_rows', 1)
                ->where('preview.invalid_rows', 0)
                ->where('preview.rows.0.normalized_data.full_name', 'Ahmad Pratama')
            );
    }

    public function test_summary_reports_correct_total_valid_invalid_rows(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['email' => 'valid1@example.com', 'identity_number' => '3171012301900001', 'member_number' => 'KOP-001']),
            $this->validRow(['email' => 'not-an-email', 'identity_number' => '3171012301900002', 'member_number' => 'KOP-002']), // invalid email
            $this->validRow(['email' => 'valid2@example.com', 'identity_number' => '3171012301900003', 'member_number' => 'KOP-003']),
        ]);
        $file = UploadedFile::fake()->createWithContent('batch.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.total_rows', 3)
                ->where('preview.valid_rows', 2)
                ->where('preview.invalid_rows', 1)
                ->where('preview.valid', false)
            );
    }

    public function test_invalid_rows_expose_structured_safe_errors(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['email' => 'invalid-email']),
        ]);
        $file = UploadedFile::fake()->createWithContent('errors.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.rows.0.valid', false)
                ->where('preview.rows.0.errors.0.field', 'email')
                ->where('preview.rows.0.errors.0.code', MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE)
            );
    }

    public function test_unresolved_employee_reference_requires_manual_review_and_is_not_persistable(): void
    {
        // NIP IP-999999 does not exist in $this->organization
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['employee_number' => 'IP-999999']),
        ]);
        $file = UploadedFile::fake()->createWithContent('unresolved_emp.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.rows.0.employee_resolution_status', MemberImportValidator::EMPLOYEE_RESOLUTION_UNRESOLVED)
                ->where('preview.rows.0.manual_review_required', true)
                ->where('preview.rows.0.persistable', false)
            );
    }

    public function test_blank_employee_reference_remains_legitimate(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['employee_number' => '']),
        ]);
        $file = UploadedFile::fake()->createWithContent('blank_emp.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.rows.0.employee_resolution_status', MemberImportValidator::EMPLOYEE_RESOLUTION_NOT_PROVIDED)
                ->where('preview.rows.0.manual_review_required', false)
                ->where('preview.rows.0.persistable', true)
            );
    }

    public function test_target_organization_is_passed_to_validator_correctly(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-102938',
        ]);

        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['employee_number' => 'IP-102938']),
        ]);
        $file = UploadedFile::fake()->createWithContent('resolved_emp.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.rows.0.employee_resolution_status', MemberImportValidator::EMPLOYEE_RESOLUTION_RESOLVED)
                ->where('preview.rows.0.resolved_employee_id', $employee->id)
                ->where('preview.rows.0.persistable', true)
            );
    }

    public function test_unit_scoped_user_cannot_spoof_another_organization(): void
    {
        $otherOrg = Organization::factory()->create(['code' => 'KOP-OTHER']);
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [$this->validRow()]);
        $file = UploadedFile::fake()->createWithContent('spoof.csv', $csvContent);

        // Attempting to submit otherOrg->id as a unit-scoped user
        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'organization_id' => $otherOrg->id,
                'import_date' => '2026-06-01',
            ])
            ->assertForbidden();
    }

    public function test_global_scoped_authorized_user_must_choose_valid_target_organization(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [$this->validRow()]);
        $file = UploadedFile::fake()->createWithContent('global.csv', $csvContent);

        // 1. Missing target organization fails with validation error
        $this->actingAs($this->authorizedGlobalUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'organization_id' => '',
                'import_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors(['organization_id']);

        // 2. Non-existent organization UUID fails with validation error
        $this->actingAs($this->authorizedGlobalUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'organization_id' => '00000000-0000-0000-0000-000000000000',
                'import_date' => '2026-06-01',
            ])
            ->assertSessionHasErrors(['organization_id']);

        // 3. Valid target organization succeeds
        $targetOrg = Organization::factory()->create(['code' => 'TARGET-01']);
        $this->actingAs($this->authorizedGlobalUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'organization_id' => $targetOrg->id,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('current_organization_id', (string) $targetOrg->id)
                ->where('preview.valid', true)
            );
    }

    public function test_import_date_is_explicitly_passed_to_validator(): void
    {
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['join_date' => '']),
        ]);
        $file = UploadedFile::fake()->createWithContent('import_date.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-09-30',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.rows.0.normalized_data.join_date', '2026-09-30')
            );
    }

    public function test_browser_inertia_preview_nik_is_redacted_in_raw_and_normalized_data(): void
    {
        $rawNik = '3171012301900001';
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(['identity_number' => $rawNik]),
        ]);
        $file = UploadedFile::fake()->createWithContent('nik_test.csv', $csvContent);

        $response = $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ]);

        $response->assertOk();

        // Check Inertia props: both raw_data and normalized_data identity_number must be [REDACTED]
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cooperative/Members/ImportPreview')
            ->where('preview.rows.0.raw_data.identity_number', '[REDACTED]')
            ->where('preview.rows.0.normalized_data.identity_number', '[REDACTED]')
        );

        // Strong privacy assertion: raw 16-digit NIK is completely absent from the response payload
        $this->assertStringNotContainsString($rawNik, $response->getContent());
    }

    public function test_preview_creates_zero_cooperative_members(): void
    {
        $membersBefore = CooperativeMember::withTrashed()->count();

        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(),
        ]);
        $file = UploadedFile::fake()->createWithContent('no_members.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk();

        $this->assertSame($membersBefore, CooperativeMember::withTrashed()->count());
    }

    public function test_preview_creates_zero_users_and_zero_social_accounts_and_no_roles(): void
    {
        $usersBefore = User::count();
        $socialBefore = SocialAccount::count();

        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(),
        ]);
        $file = UploadedFile::fake()->createWithContent('no_users.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk();

        $this->assertSame($usersBefore, User::count());
        $this->assertSame($socialBefore, SocialAccount::count());
    }

    public function test_uploaded_csv_is_not_persisted_to_application_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(),
        ]);
        $file = UploadedFile::fake()->createWithContent('storage_check.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk();

        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_zero_row_csv_never_produces_ready_for_import_ui_state(): void
    {
        // Header only, no data rows
        $csvContent = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS, []);
        $file = UploadedFile::fake()->createWithContent('empty.csv', $csvContent);

        $this->actingAs($this->authorizedUnitUser)
            ->post(route('cooperative.members.import.preview'), [
                'file' => $file,
                'import_date' => '2026-06-01',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/ImportPreview')
                ->where('preview.header_valid', true)
                ->where('preview.total_rows', 0)
                ->where('preview.valid_rows', 0)
                ->where('preview.invalid_rows', 0)
                ->where('preview.rows', [])
            );
    }
}
