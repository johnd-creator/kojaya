<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Cooperative\ImportRowResult;
use App\Services\Cooperative\ImportValidationError;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Security\PiiCryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberImportValidatorTest extends TestCase
{
    use RefreshDatabase;

    private MemberImportValidator $validator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MemberImportValidator(app(PiiCryptoService::class));
        $this->organization = Organization::factory()->create(['code' => 'KOP-001']);
    }

    /**
     * Helper to generate a valid canonical 12-column row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'member_number' => 'KOP-001',
            'full_name' => 'Ahmad Pratama',
            'email' => 'ahmad.pratama.dummy@example.com',
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
     * Convert array of rows to CSV string.
     *
     * @param  list<string>  $headers
     * @param  list<array<int|string, mixed>>  $rows
     */
    private function toCsv(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            if (array_is_list($row)) {
                fputcsv($fp, $row);
            } else {
                $line = [];
                foreach ($headers as $col) {
                    $line[] = $row[$col] ?? '';
                }
                fputcsv($fp, $line);
            }
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv ?: '';
    }

    public function test_exact_canonical_headers_pass(): void
    {
        $csv = $this->toCsv(MemberImportValidator::CANONICAL_HEADERS, [
            $this->validRow(),
        ]);

        $result = $this->validator->validate($csv, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->headerValid);
        $this->assertTrue($result->valid);
        $this->assertSame(1, $result->totalRows);
        $this->assertSame(1, $result->validRows);
        $this->assertSame(0, $result->invalidRows);
        $this->assertCount(0, $result->errors);
    }

    public function test_headers_fail_when_missing_columns(): void
    {
        $headers = MemberImportValidator::CANONICAL_HEADERS;
        array_pop($headers); // 11 columns

        $csv = $this->toCsv($headers, [
            ['KOP-001', 'Ahmad', 'ahmad@example.com', '081234560001', '3171012301900001', 'L', 'IP', '', 'Jl. Merdeka', 'AB', '2026-06-01'],
        ]);

        $result = $this->validator->validate($csv);

        $this->assertFalse($result->headerValid);
        $this->assertFalse($result->valid);
        $this->assertSame(0, $result->totalRows);
        $this->assertSame(MemberImportValidator::CODE_INVALID_HEADER, $result->errors[0]->code);
        $this->assertSame(ImportValidationError::SEVERITY_FATAL, $result->errors[0]->severity);
    }

    public function test_headers_fail_when_extra_or_system_columns_present(): void
    {
        $headers = array_merge(MemberImportValidator::CANONICAL_HEADERS, ['user_id']);

        $csv = $this->toCsv($headers, [
            array_merge(array_values($this->validRow()), [123]),
        ]);

        $result = $this->validator->validate($csv);

        $this->assertFalse($result->headerValid);
        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_HEADER, $result->errors[0]->code);
    }

    public function test_headers_fail_when_reordered(): void
    {
        $headers = MemberImportValidator::CANONICAL_HEADERS;
        // Swap first two columns
        $temp = $headers[0];
        $headers[0] = $headers[1];
        $headers[1] = $temp;

        $csv = $this->toCsv($headers, []);

        $result = $this->validator->validate($csv);

        $this->assertFalse($result->headerValid);
        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_HEADER, $result->errors[0]->code);
    }

    public function test_headers_fail_when_duplicate_columns_exist(): void
    {
        $headers = MemberImportValidator::CANONICAL_HEADERS;
        $headers[1] = 'member_number'; // duplicate member_number

        $csv = $this->toCsv($headers, []);

        $result = $this->validator->validate($csv);

        $this->assertFalse($result->headerValid);
        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_HEADER, $result->errors[0]->code);
    }

    public function test_headers_fail_when_fuzzy_or_unnamed_columns_present(): void
    {
        $headers = MemberImportValidator::CANONICAL_HEADERS;
        $headers[0] = 'Member Number'; // fuzzy variation

        $csv = $this->toCsv($headers, []);

        $result = $this->validator->validate($csv);

        $this->assertFalse($result->headerValid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_HEADER, $result->errors[0]->code);
    }

    public function test_valid_row_normalizes_12_canonical_keys(): void
    {
        $rows = [
            $this->validRow([
                'member_number' => '  kop-042  ',
                'full_name' => '  Budi   Santoso  ',
                'email' => '  Budi.Santoso@Example.COM  ',
                'phone_number' => '+62 812-3456-7890',
                'identity_number' => '  3171012301900001  ',
                'gender' => '  l  ',
                'company_code' => '  cdb  ',
                'employee_number' => null,
                'address' => "  Jl. Sudirman No. 45 \n Blok B  ",
                'membership_type' => '  alb  ',
                'join_date' => '  2026-06-01  ',
                'notes' => '  Catatan penting  ',
            ]),
        ];

        $result = $this->validator->validateRows($rows, MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->valid);
        $this->assertSame(1, $result->totalRows);
        $this->assertSame(1, $result->validRows);

        $row = $result->rows[0];
        $this->assertTrue($row->valid);
        $this->assertTrue($row->persistable);
        $this->assertFalse($row->manualReviewRequired);
        $this->assertFalse($row->memberNumberGenerationRequired);

        $norm = $row->normalizedData;
        $this->assertSame('KOP-042', $norm['member_number']);
        $this->assertSame('Budi   Santoso', $norm['full_name']);
        $this->assertSame('budi.santoso@example.com', $norm['email']);
        $this->assertSame('081234567890', $norm['phone_number']);
        $this->assertSame('3171012301900001', $norm['identity_number']);
        $this->assertSame('L', $norm['gender']);
        $this->assertSame('CDB', $norm['company_code']);
        $this->assertNull($norm['employee_number']);
        $this->assertSame("Jl. Sudirman No. 45 \n Blok B", $norm['address']);
        $this->assertSame('ALB', $norm['membership_type']);
        $this->assertSame('2026-06-01', $norm['join_date']);
        $this->assertSame('Catatan penting', $norm['notes']);
    }

    public function test_required_field_validations(): void
    {
        $row = $this->validRow([
            'full_name' => '   ',
            'email' => '',
            'phone_number' => '',
            'identity_number' => '',
            'gender' => '',
            'company_code' => '',
            'address' => '',
        ]);

        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $rowErrors = $result->rows[0]->errors;
        $codes = array_map(fn (ImportValidationError $e) => $e->code, $rowErrors);
        $fields = array_map(fn (ImportValidationError $e) => $e->field, $rowErrors);

        $this->assertContains(MemberImportValidator::CODE_MISSING_REQUIRED_FIELD, $codes);
        $this->assertContains('full_name', $fields);
        $this->assertContains('email', $fields);
        $this->assertContains('phone_number', $fields);
        $this->assertContains('identity_number', $fields);
        $this->assertContains('gender', $fields);
        $this->assertContains('company_code', $fields);
        $this->assertContains('address', $fields);
    }

    public function test_email_normalization_and_syntactic_validation(): void
    {
        // Invalid email
        $row = $this->validRow(['email' => 'not-an-email']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $result->rows[0]->errors[0]->code);
        $this->assertSame('email', $result->rows[0]->errors[0]->field);
    }

    public function test_phone_number_formats(): void
    {
        // Test 08..., 628..., +628...
        $cases = [
            '081234567890' => '081234567890',
            '6281234567890' => '081234567890',
            '+6281234567890' => '081234567890',
            '+62 812-3456-7890' => '081234567890',
        ];

        foreach ($cases as $input => $expected) {
            $row = $this->validRow(['phone_number' => $input]);
            $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
                'organization_id' => $this->organization->id,
                'import_date' => '2026-06-01',
            ]);
            $this->assertTrue($result->valid, "Phone input '{$input}' should be valid.");
            $this->assertSame($expected, $result->rows[0]->normalizedData['phone_number']);
        }

        // Invalid phone numbers
        $invalid = ['0211234567', '123456', '+14155552671', '080123456789012345'];
        foreach ($invalid as $badPhone) {
            $row = $this->validRow(['phone_number' => $badPhone]);
            $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
                'organization_id' => $this->organization->id,
                'import_date' => '2026-06-01',
            ]);
            $this->assertFalse($result->valid, "Phone input '{$badPhone}' should be invalid.");
        }
    }

    public function test_identity_number_preserves_leading_zeros_and_rejects_non_16_digits(): void
    {
        // 16 digits starting with 0
        $nikWithLeadingZero = '0123456789012345';
        $row = $this->validRow(['identity_number' => $nikWithLeadingZero]);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($result->valid);
        $this->assertSame($nikWithLeadingZero, $result->rows[0]->normalizedData['identity_number']);

        // 15 digits, 17 digits, non-numeric
        $invalidNiks = ['123456789012345', '12345678901234567', '317101230190000a'];
        foreach ($invalidNiks as $badNik) {
            $row = $this->validRow(['identity_number' => $badNik]);
            $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
                'organization_id' => $this->organization->id,
                'import_date' => '2026-06-01',
            ]);
            $this->assertFalse($result->valid);
            $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $result->rows[0]->errors[0]->code);
            // Verify safe message: raw NIK not disclosed
            $this->assertStringNotContainsString($badNik, $result->rows[0]->errors[0]->message);
        }
    }

    public function test_gender_and_company_code_validation(): void
    {
        // Invalid gender
        $rowBadGender = $this->validRow(['gender' => 'X']);
        $result1 = $this->validator->validateRows([$rowBadGender], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($result1->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $result1->rows[0]->errors[0]->code);

        // Company name instead of company_code
        $rowBadCompany = $this->validRow(['company_code' => 'PT Indonesia Power']);
        $result2 = $this->validator->validateRows([$rowBadCompany], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($result2->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $result2->rows[0]->errors[0]->code);
    }

    public function test_membership_type_defaults_to_ab_and_validates(): void
    {
        // Blank membership_type
        $rowBlank = $this->validRow(['membership_type' => '']);
        $result = $this->validator->validateRows([$rowBlank], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($result->valid);
        $this->assertSame('AB', $result->rows[0]->normalizedData['membership_type']);

        // Invalid membership_type
        $rowInvalid = $this->validRow(['membership_type' => 'GOLD']);
        $resultInvalid = $this->validator->validateRows([$rowInvalid], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($resultInvalid->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $resultInvalid->rows[0]->errors[0]->code);
    }

    public function test_join_date_defaults_to_explicit_import_date_context(): void
    {
        // Blank join_date with explicit import_date in context
        $rowBlank = $this->validRow(['join_date' => '']);
        $result = $this->validator->validateRows([$rowBlank], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($result->valid);
        $this->assertSame('2026-06-01', $result->rows[0]->normalizedData['join_date']);

        // Blank join_date with NO import_date in context
        $resultNoDate = $this->validator->validateRows([$rowBlank], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
        ]);
        $this->assertFalse($resultNoDate->valid);
        $this->assertSame(MemberImportValidator::CODE_MISSING_REQUIRED_FIELD, $resultNoDate->rows[0]->errors[0]->code);

        // Invalid calendar date
        $rowBadDate = $this->validRow(['join_date' => '2026-02-31']);
        $resultBad = $this->validator->validateRows([$rowBadDate], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($resultBad->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $resultBad->rows[0]->errors[0]->code);
    }

    public function test_notes_normalization_and_blank_handling(): void
    {
        $rowBlank = $this->validRow(['notes' => '   ']);
        $result = $this->validator->validateRows([$rowBlank], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($result->valid);
        $this->assertNull($result->rows[0]->normalizedData['notes']);
    }

    public function test_member_number_blank_sets_generation_required(): void
    {
        $row = $this->validRow(['member_number' => '']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($result->valid);
        $this->assertNull($result->rows[0]->normalizedData['member_number']);
        $this->assertTrue($result->rows[0]->memberNumberGenerationRequired);
    }

    public function test_batch_duplicate_detection_for_email_nik_and_member_no(): void
    {
        $row1 = $this->validRow([
            'member_number' => 'KOP-001',
            'email' => 'duplicate@example.com',
            'identity_number' => '3171012301900001',
        ]);

        $row2 = $this->validRow([
            'member_number' => 'KOP-001', // duplicate member_number
            'email' => 'duplicate@example.com', // duplicate email
            'identity_number' => '3171012301900001', // duplicate NIK
            'full_name' => 'Budi Pratama',
        ]);

        $result = $this->validator->validateRows([$row1, $row2], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(2, $result->invalidRows);

        $row1Codes = array_map(fn ($e) => $e->code, $result->rows[0]->errors);
        $row2Codes = array_map(fn ($e) => $e->code, $result->rows[1]->errors);

        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_EMAIL_BATCH, $row1Codes);
        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_IDENTITY_NUMBER_BATCH, $row1Codes);
        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_MEMBER_NUMBER_BATCH, $row1Codes);

        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_EMAIL_BATCH, $row2Codes);
        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_IDENTITY_NUMBER_BATCH, $row2Codes);
        $this->assertContains(MemberImportValidator::CODE_DUPLICATE_MEMBER_NUMBER_BATCH, $row2Codes);
    }

    public function test_database_conflict_detection_for_email_nik_and_member_no(): void
    {
        // Existing user email
        User::factory()->create([
            'email' => 'existing.user@example.com',
            'organization_id' => $this->organization->id,
        ]);

        // Existing cooperative member with NIK and member_no
        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => 'KOP-999',
            'member_no' => 'KOP-999',
            'identity_number' => '3171012301909999',
            'email' => 'existing.member@example.com',
        ]);

        $rowConflict = $this->validRow([
            'member_number' => 'KOP-999',
            'email' => 'existing.user@example.com',
            'identity_number' => '3171012301909999',
        ]);

        $result = $this->validator->validateRows([$rowConflict], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $codes = array_map(fn ($e) => $e->code, $result->rows[0]->errors);

        $this->assertContains(MemberImportValidator::CODE_EMAIL_ALREADY_EXISTS, $codes);
        $this->assertContains(MemberImportValidator::CODE_IDENTITY_NUMBER_ALREADY_EXISTS, $codes);
        $this->assertContains(MemberImportValidator::CODE_MEMBER_NUMBER_ALREADY_EXISTS, $codes);

        // Verify safe error messages (no raw NIK exposed)
        foreach ($result->rows[0]->errors as $err) {
            if ($err->code === MemberImportValidator::CODE_IDENTITY_NUMBER_ALREADY_EXISTS) {
                $this->assertStringNotContainsString('3171012301909999', $err->message);
            }
        }
    }

    public function test_employee_reference_blank_resolves_not_provided(): void
    {
        $row = $this->validRow(['employee_number' => '']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_NOT_PROVIDED, $result->rows[0]->employeeResolutionStatus);
        $this->assertNull($result->rows[0]->resolvedEmployeeId);
        $this->assertFalse($result->rows[0]->manualReviewRequired);
        $this->assertTrue($result->rows[0]->persistable);
    }

    public function test_employee_reference_resolves_in_authoritative_organization(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-102938',
        ]);

        $row = $this->validRow(['employee_number' => 'IP-102938']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_RESOLVED, $result->rows[0]->employeeResolutionStatus);
        $this->assertSame($employee->id, $result->rows[0]->resolvedEmployeeId);
        $this->assertFalse($result->rows[0]->manualReviewRequired);
        $this->assertTrue($result->rows[0]->persistable);
    }

    public function test_employee_reference_fails_closed_when_organization_context_missing(): void
    {
        $row = $this->validRow(['employee_number' => 'IP-102938']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'import_date' => '2026-06-01',
            // No organization_id provided!
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_UNRESOLVED, $result->rows[0]->employeeResolutionStatus);
        $this->assertTrue($result->rows[0]->manualReviewRequired);
        $this->assertFalse($result->rows[0]->persistable);
        $this->assertSame(MemberImportValidator::CODE_REFERENCE_CONTEXT_UNRESOLVED, $result->rows[0]->errors[0]->code);
    }

    public function test_employee_reference_unresolved_when_not_found_or_org_mismatched(): void
    {
        $otherOrg = Organization::factory()->create(['code' => 'ORG-OTHER']);
        Employee::factory()->create([
            'organization_id' => $otherOrg->id,
            'employee_code' => 'IP-999999',
        ]);

        // Querying with $this->organization->id, but employee belongs to $otherOrg
        $row = $this->validRow(['employee_number' => 'IP-999999']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_UNRESOLVED, $result->rows[0]->employeeResolutionStatus);
        $this->assertTrue($result->rows[0]->manualReviewRequired);
        $this->assertFalse($result->rows[0]->persistable);
        $this->assertSame(MemberImportValidator::CODE_UNRESOLVED_EMPLOYEE_REFERENCE, $result->rows[0]->errors[0]->code);

        // Unresolved employee reference is NEVER saved to notes!
        $this->assertSame('Catatan anggota aktif', $result->rows[0]->normalizedData['notes']);
    }

    public function test_employee_reference_fails_when_already_linked_to_active_member(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-888888',
        ]);

        CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_id' => $employee->id,
        ]);

        $row = $this->validRow(['employee_number' => 'IP-888888']);
        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_CONFLICT, $result->rows[0]->employeeResolutionStatus);
        $this->assertTrue($result->rows[0]->manualReviewRequired);
        $this->assertFalse($result->rows[0]->persistable);
        $this->assertSame(MemberImportValidator::CODE_EMPLOYEE_REFERENCE_CONFLICT, $result->rows[0]->errors[0]->code);
    }

    public function test_employee_reference_fails_when_batch_duplicates_resolve_to_same_employee(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-777777',
        ]);

        $row1 = $this->validRow([
            'member_number' => 'KOP-101',
            'email' => 'emp1@example.com',
            'identity_number' => '3171012301900101',
            'employee_number' => 'IP-777777',
        ]);

        $row2 = $this->validRow([
            'member_number' => 'KOP-102',
            'email' => 'emp2@example.com',
            'identity_number' => '3171012301900102',
            'employee_number' => 'IP-777777',
        ]);

        $result = $this->validator->validateRows([$row1, $row2], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_CONFLICT, $result->rows[0]->employeeResolutionStatus);
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_CONFLICT, $result->rows[1]->employeeResolutionStatus);
        $this->assertSame(MemberImportValidator::CODE_EMPLOYEE_REFERENCE_CONFLICT, $result->rows[0]->errors[0]->code);
    }

    public function test_no_persistence_guarantee(): void
    {
        $membersBefore = CooperativeMember::withTrashed()->count();
        $usersBefore = User::count();
        $employeesBefore = Employee::count();
        $auditBefore = AuditLog::count();
        $socialBefore = SocialAccount::count();

        $rows = [
            $this->validRow(),
            $this->validRow([
                'member_number' => 'KOP-002',
                'email' => 'user2@example.com',
                'identity_number' => '3171012301900002',
            ]),
        ];

        $this->validator->validateRows($rows, MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertSame($membersBefore, CooperativeMember::withTrashed()->count(), 'CooperativeMember count must be unchanged.');
        $this->assertSame($usersBefore, User::count(), 'User count must be unchanged.');
        $this->assertSame($employeesBefore, Employee::count(), 'Employee count must be unchanged.');
        $this->assertSame($auditBefore, AuditLog::count(), 'AuditLog count must be unchanged.');
        $this->assertSame($socialBefore, SocialAccount::count(), 'SocialAccount count must be unchanged.');
    }

    public function test_canonical_import_template_file_validation(): void
    {
        $templatePath = base_path('docs/onboarding/member-import-template.csv');
        $this->assertFileExists($templatePath);

        // Create the 3 employees referenced in the template for $this->organization
        Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-102938',
        ]);
        Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'CDB-204911',
        ]);
        Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-305819',
        ]);

        $result = $this->validator->validateFile($templatePath, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->headerValid);
        $this->assertTrue($result->valid);
        $this->assertSame(4, $result->totalRows);
        $this->assertSame(4, $result->validRows);
        $this->assertSame(0, $result->invalidRows);

        // Row 1 (KOP-001): resolved employee IP-102938
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_RESOLVED, $result->rows[0]->employeeResolutionStatus);
        $this->assertFalse($result->rows[0]->memberNumberGenerationRequired);

        // Row 3 (KOP-003): ALB non-karyawan with blank employee_number
        $this->assertSame(MemberImportValidator::EMPLOYEE_RESOLUTION_NOT_PROVIDED, $result->rows[2]->employeeResolutionStatus);
        $this->assertSame('ALB', $result->rows[2]->normalizedData['membership_type']);

        // Row 4 (blank member_number): auto-generate required
        $this->assertNull($result->rows[3]->normalizedData['member_number']);
        $this->assertTrue($result->rows[3]->memberNumberGenerationRequired);
    }

    public function test_full_name_normalization_is_trim_only_preserving_internal_whitespace_and_text(): void
    {
        $nameWithInternalSpaces = 'Ir.   H.   Budi   Santoso,   M.Sc.';
        $row = $this->validRow([
            'full_name' => '   '.$nameWithInternalSpaces.'   ',
        ]);

        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->valid);
        $this->assertSame($nameWithInternalSpaces, $result->rows[0]->normalizedData['full_name']);
    }

    public function test_full_name_accepts_between_101_and_255_characters_preserving_trim_only_internal_whitespace(): void
    {
        // 1. Name with 150 characters (previously incorrectly rejected by > 100 limit)
        $name150 = str_repeat('A', 70).'   '.str_repeat('B', 77);
        $row1 = $this->validRow([
            'full_name' => '   '.$name150.'   ',
        ]);

        $result1 = $this->validator->validateRows([$row1], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result1->valid);
        $this->assertSame($name150, $result1->rows[0]->normalizedData['full_name']);
        $this->assertSame(150, mb_strlen($result1->rows[0]->normalizedData['full_name']));

        // 2. Name with exactly 255 characters (authoritative maximum mapping to cooperative_members.name varchar(255))
        $name255 = 'Prof.   '.str_repeat('A', 241).'   Jr.';
        $this->assertSame(255, mb_strlen($name255));

        $row2 = $this->validRow([
            'full_name' => '   '.$name255.'   ',
        ]);

        $result2 = $this->validator->validateRows([$row2], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result2->valid);
        $this->assertSame($name255, $result2->rows[0]->normalizedData['full_name']);
        $this->assertSame(255, mb_strlen($result2->rows[0]->normalizedData['full_name']));
    }

    public function test_full_name_rejects_exceeding_255_characters(): void
    {
        $name256 = str_repeat('A', 256);
        $row = $this->validRow([
            'full_name' => $name256,
        ]);

        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $errors = $result->rows[0]->errors;
        $this->assertCount(1, $errors);
        $this->assertSame('full_name', $errors[0]->field);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $errors[0]->code);
        $this->assertSame('Nama lengkap maksimal 255 karakter.', $errors[0]->message);
    }

    public function test_full_name_rejects_fewer_than_3_characters(): void
    {
        $row = $this->validRow([
            'full_name' => '  AB  ',
        ]);

        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertFalse($result->valid);
        $errors = $result->rows[0]->errors;
        $this->assertCount(1, $errors);
        $this->assertSame('full_name', $errors[0]->field);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $errors[0]->code);
        $this->assertSame('Nama lengkap minimal 3 karakter.', $errors[0]->message);
    }

    public function test_address_boundary_validation_max_1000(): void
    {
        // 1000 characters is accepted
        $address1000 = str_repeat('A', 1000);
        $rowValid = $this->validRow(['address' => '   '.$address1000.'   ']);
        $resultValid = $this->validator->validateRows([$rowValid], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($resultValid->valid);
        $this->assertSame($address1000, $resultValid->rows[0]->normalizedData['address']);

        // 1001 characters is rejected
        $address1001 = str_repeat('A', 1001);
        $rowInvalid = $this->validRow(['address' => $address1001]);
        $resultInvalid = $this->validator->validateRows([$rowInvalid], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($resultInvalid->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_CONTROLLED_VALUE, $resultInvalid->rows[0]->errors[0]->code);
        $this->assertSame('Alamat domisili maksimal 1000 karakter.', $resultInvalid->rows[0]->errors[0]->message);
    }

    public function test_import_row_result_does_not_expose_raw_nik_in_raw_data_or_to_array_or_json(): void
    {
        $rawNik = '3171012301900001';
        $row = $this->validRow([
            'identity_number' => $rawNik,
        ]);

        $result = $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->valid);
        $rowResult = $result->rows[0];

        // 1. Check raw_data property on ImportRowResult
        $this->assertArrayHasKey('identity_number', $rowResult->rawData);
        $this->assertNotSame($rawNik, $rowResult->rawData['identity_number']);
        $this->assertSame('[REDACTED]', $rowResult->rawData['identity_number']);

        // 2. Check toArray() output
        $rowArray = $rowResult->toArray();
        $this->assertSame('[REDACTED]', $rowArray['raw_data']['identity_number']);
        $this->assertNotSame($rawNik, $rowArray['raw_data']['identity_number']);

        // 3. Check JSON serialization of single row
        $rowJson = json_encode($rowResult);
        $this->assertIsString($rowJson);
        $this->assertStringNotContainsString('"identity_number":"'.$rawNik.'"', (string) json_encode($rowResult->toArray()['raw_data']));
        $decodedRow = json_decode($rowJson, true);
        $this->assertSame('[REDACTED]', $decodedRow['raw_data']['identity_number']);

        // 4. Check entire ImportValidationResult JSON output
        $batchJson = json_encode($result);
        $this->assertIsString($batchJson);
        $decodedBatch = json_decode($batchJson, true);
        $this->assertSame('[REDACTED]', $decodedBatch['rows'][0]['raw_data']['identity_number']);

        // 5. Test direct instantiation of ImportRowResult to prove immunity
        $directRowResult = new ImportRowResult(
            rowNumber: 1,
            valid: true,
            rawData: ['identity_number' => $rawNik, 'full_name' => 'Direct Test'],
            normalizedData: ['identity_number' => $rawNik],
            resolvedEmployeeId: null,
            employeeResolutionStatus: MemberImportValidator::EMPLOYEE_RESOLUTION_NOT_PROVIDED,
            memberNumberGenerationRequired: false,
            manualReviewRequired: false,
            persistable: true,
        );
        $this->assertSame('[REDACTED]', $directRowResult->rawData['identity_number']);
        $this->assertSame('[REDACTED]', $directRowResult->toArray()['raw_data']['identity_number']);
        $this->assertStringNotContainsString($rawNik, (string) json_encode($directRowResult->toArray()['raw_data']));
    }

    public function test_employee_resolution_query_is_scoped_to_organization_at_sql_level(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
            ];
        });

        Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'IP-112233',
        ]);

        $row = $this->validRow(['employee_number' => 'IP-112233']);
        $this->validator->validateRows([$row], MemberImportValidator::CANONICAL_HEADERS, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $employeeQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['sql']), 'from "employees"')
                || str_contains(strtolower($q['sql']), 'from `employees`')
                || str_contains(strtolower($q['sql']), 'employees');
        });

        $this->assertNotEmpty($employeeQueries, 'Expected query against employees table.');
        foreach ($employeeQueries as $q) {
            $sql = strtolower($q['sql']);
            if (str_contains($sql, 'select') && str_contains($sql, 'employee_code')) {
                $this->assertStringContainsString('organization_id', $sql, 'Employee query must contain organization_id in WHERE clause.');
                $this->assertContains((string) $this->organization->id, array_map('strval', $q['bindings']), 'Bindings must include the explicit organization ID.');
            }
        }
    }
}
