<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeOpeningBalanceWizardService;
use App\Services\Cooperative\MemberImportValidator;
use App\Services\Cooperative\PreviewProofService;
use App\Services\Security\PiiCryptoService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class MemberImportBundle04Test extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $importer;

    private User $checker;

    private MemberImportValidator $validator;

    private PreviewProofService $proofService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-01'));
        $this->seed(RolePermissionSeeder::class);

        Config::set('cooperative.member_import_execution_enabled', true);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Jaya Mandiri',
        ]);

        $this->importer = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Pengimpor',
        ]);
        $this->importer->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value);
        $this->importer->givePermissionTo(PermissionEnum::COOPERATIVE_MEMBER_IMPORT->value);

        $this->checker = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Manajer Checker',
        ]);
        $this->checker->assignRole('Pengurus Koperasi');

        $this->validator = new MemberImportValidator(app(PiiCryptoService::class));
        $this->proofService = app(PreviewProofService::class);

        // Set up active contribution types
        foreach (CooperativeOpeningBalanceWizardService::CATEGORIES as $category) {
            CooperativeContributionType::query()->create([
                'code' => $category,
                'name' => 'Simpanan '.$category,
                'category' => $category,
                'default_amount' => 50000,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Helper to generate CSV string.
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

    private function validV1Row(array $overrides = []): array
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

    private function validV2Row(array $overrides = []): array
    {
        return array_merge($this->validV1Row(), [
            'opening_balance_pokok' => '100000.00',
            'opening_balance_wajib' => '50000.00',
            'opening_balance_sukarela' => '25000.00',
            'opening_balance_khusus' => '0.00',
        ], $overrides);
    }

    public function test_v1_csv_12_columns_accepted_with_zero_opening_balance_summary(): void
    {
        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V1, [
            $this->validV1Row(),
        ]);

        $file = UploadedFile::fake()->createWithContent('import_v1.csv', $csv);

        $result = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);

        $this->assertTrue($result->headerValid);
        $this->assertTrue($result->valid);
        $this->assertSame('v1', $result->csvVersion);
        $this->assertSame(1, $result->totalRows);
        $this->assertSame(0, $result->openingBalanceSummary['positive_members_count']);
        $this->assertSame(0.0, $result->openingBalanceSummary['grand_total']);
    }

    public function test_v2_csv_16_columns_accepted_with_correct_summary(): void
    {
        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [
            $this->validV2Row([
                'member_number' => 'KOP-001',
                'email' => 'user1@example.com',
                'identity_number' => '3171012301900001',
                'opening_balance_pokok' => '200000.00',
                'opening_balance_wajib' => '100000.00',
                'opening_balance_sukarela' => '50000.00',
                'opening_balance_khusus' => '25000.00',
            ]),
            $this->validV2Row([
                'member_number' => 'KOP-002',
                'email' => 'user2@example.com',
                'identity_number' => '3171012301900002',
                'opening_balance_pokok' => '0',
                'opening_balance_wajib' => '0',
                'opening_balance_sukarela' => '0',
                'opening_balance_khusus' => '0',
            ]),
        ]);

        $file = UploadedFile::fake()->createWithContent('import_v2.csv', $csv);

        $result = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);

        $this->assertTrue($result->headerValid);
        $this->assertTrue($result->valid);
        $this->assertSame('v2', $result->csvVersion);
        $this->assertSame(2, $result->totalRows);
        $this->assertSame(1, $result->openingBalanceSummary['positive_members_count']);
        $this->assertSame(200000.0, $result->openingBalanceSummary['total_pokok']);
        $this->assertSame(100000.0, $result->openingBalanceSummary['total_wajib']);
        $this->assertSame(50000.0, $result->openingBalanceSummary['total_sukarela']);
        $this->assertSame(25000.0, $result->openingBalanceSummary['total_khusus']);
        $this->assertSame(375000.0, $result->openingBalanceSummary['grand_total']);
        $this->assertSame('2026-09-30', $result->openingBalanceSummary['cutoff_date']);
    }

    public function test_csv_header_strict_validation_rejects_unknown_missing_or_reordered_columns(): void
    {
        // 13 columns (partial financial)
        $headers13 = array_merge(MemberImportValidator::CANONICAL_HEADERS_V1, ['opening_balance_pokok']);
        $csv13 = $this->generateCsv($headers13, [array_merge($this->validV1Row(), ['opening_balance_pokok' => '100000'])]);
        $file13 = UploadedFile::fake()->createWithContent('invalid13.csv', $csv13);
        $result13 = $this->validator->validateFile($file13->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($result13->headerValid);
        $this->assertFalse($result13->valid);

        // Reordered columns
        $reordered = MemberImportValidator::CANONICAL_HEADERS_V2;
        $temp = $reordered[0];
        $reordered[0] = $reordered[1];
        $reordered[1] = $temp;
        $csvReordered = $this->generateCsv($reordered, [$this->validV2Row()]);
        $fileReordered = UploadedFile::fake()->createWithContent('reordered.csv', $csvReordered);
        $resultReordered = $this->validator->validateFile($fileReordered->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($resultReordered->headerValid);
        $this->assertFalse($resultReordered->valid);

        // Extra 17th column
        $headers17 = array_merge(MemberImportValidator::CANONICAL_HEADERS_V2, ['extra_field']);
        $csv17 = $this->generateCsv($headers17, [array_merge($this->validV2Row(), ['extra_field' => 'foo'])]);
        $file17 = UploadedFile::fake()->createWithContent('extra17.csv', $csv17);
        $result17 = $this->validator->validateFile($file17->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($result17->headerValid);
        $this->assertFalse($result17->valid);
    }

    public function test_v2_financial_values_validation(): void
    {
        // 1. Empty financial fields default to 0.00
        $emptyRow = $this->validV2Row([
            'opening_balance_pokok' => '',
            'opening_balance_wajib' => '   ',
            'opening_balance_sukarela' => null,
            'opening_balance_khusus' => '0',
        ]);
        $csvEmpty = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$emptyRow]);
        $fileEmpty = UploadedFile::fake()->createWithContent('empty_fin.csv', $csvEmpty);
        $resEmpty = $this->validator->validateFile($fileEmpty->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertTrue($resEmpty->valid);
        $this->assertSame(0.0, $resEmpty->rows[0]->normalizedData['opening_balance_pokok']);
        $this->assertSame(0.0, $resEmpty->rows[0]->normalizedData['opening_balance_wajib']);
        $this->assertSame(0.0, $resEmpty->rows[0]->normalizedData['opening_balance_sukarela']);
        $this->assertSame(0.0, $resEmpty->rows[0]->normalizedData['opening_balance_khusus']);

        // 2. Negative values rejected
        $negRow = $this->validV2Row(['opening_balance_pokok' => '-50000']);
        $csvNeg = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$negRow]);
        $fileNeg = UploadedFile::fake()->createWithContent('neg_fin.csv', $csvNeg);
        $resNeg = $this->validator->validateFile($fileNeg->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);
        $this->assertFalse($resNeg->valid);
        $this->assertSame(MemberImportValidator::CODE_INVALID_FINANCIAL_VALUE, $resNeg->rows[0]->errors[0]->code);

        // 3. Formula injection rejected
        foreach (['=1+1', '@SUM(A1:A5)', '+10000', '-20000'] as $formula) {
            $fRow = $this->validV2Row(['opening_balance_pokok' => $formula]);
            $csvF = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$fRow]);
            $fileF = UploadedFile::fake()->createWithContent('formula.csv', $csvF);
            $resF = $this->validator->validateFile($fileF->getPathname(), [
                'organization_id' => $this->organization->id,
                'import_date' => '2026-06-01',
                'opening_balance_cutoff_date' => '2026-09-30',
            ]);
            $this->assertFalse($resF->valid, "Formula '{$formula}' must be rejected");
        }

        // 4. More than 2 decimal places rejected
        $decRow = $this->validV2Row(['opening_balance_pokok' => '100000.123']);
        $csvDec = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$decRow]);
        $fileDec = UploadedFile::fake()->createWithContent('dec.csv', $csvDec);
        $resDec = $this->validator->validateFile($fileDec->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);
        $this->assertFalse($resDec->valid);

        // 5. Exceeding max limit (999,999,999,999.99) rejected
        $maxRow = $this->validV2Row(['opening_balance_pokok' => '1000000000000.00']);
        $csvMax = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$maxRow]);
        $fileMax = UploadedFile::fake()->createWithContent('max.csv', $csvMax);
        $resMax = $this->validator->validateFile($fileMax->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);
        $this->assertFalse($resMax->valid);
    }

    public function test_fail_closed_on_missing_or_ambiguous_contribution_types(): void
    {
        // Missing active contribution type for WAJIB
        CooperativeContributionType::query()->where('category', 'WAJIB')->update(['is_active' => false]);

        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$this->validV2Row()]);
        $file = UploadedFile::fake()->createWithContent('missing_cat.csv', $csv);
        $res = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);
        $this->assertFalse($res->valid);
        $this->assertTrue(collect($res->errors)->contains('code', MemberImportValidator::CODE_AMBIGUOUS_CONTRIBUTION_TYPE));

        // Ambiguous active contribution types for WAJIB (create second active one)
        CooperativeContributionType::query()->where('category', 'WAJIB')->update(['is_active' => true]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB_2',
            'name' => 'Simpanan Wajib 2',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $resAmbiguous = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-09-30',
        ]);
        $this->assertFalse($resAmbiguous->valid);
        $this->assertTrue(collect($resAmbiguous->errors)->contains('code', MemberImportValidator::CODE_AMBIGUOUS_CONTRIBUTION_TYPE));
    }

    public function test_cutoff_date_validation_rules(): void
    {
        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [$this->validV2Row([
            'join_date' => '2026-08-01',
        ])]);
        $file = UploadedFile::fake()->createWithContent('cutoff.csv', $csv);

        // Missing cutoff date when positive balance exists
        $resMissing = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
        ]);
        $this->assertFalse($resMissing->valid);
        $this->assertTrue(collect($resMissing->errors)->contains('code', MemberImportValidator::CODE_MISSING_CUTOFF_DATE));

        // Cutoff date in future
        $resFuture = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-12-31',
        ]);
        $this->assertFalse($resFuture->valid);
        $this->assertTrue(collect($resFuture->errors)->contains('code', MemberImportValidator::CODE_CUTOFF_DATE_IN_FUTURE));

        // Member join_date after cutoff date
        $resJoinAfterCutoff = $this->validator->validateFile($file->getPathname(), [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-07-01', // member join_date is 2026-08-01
        ]);
        $this->assertFalse($resJoinAfterCutoff->valid);
        $this->assertTrue(collect($resJoinAfterCutoff->rows[0]->errors)->contains('code', MemberImportValidator::CODE_JOIN_DATE_AFTER_CUTOFF));
    }

    public function test_preview_proof_binds_csv_version_and_cutoff_date(): void
    {
        $fileSha256 = hash('sha256', 'dummy_content');
        $orgId = (string) $this->organization->id;
        $importDate = '2026-06-01';
        $cutoffDate = '2026-09-30';

        $proof = $this->proofService->generate(
            $fileSha256,
            $orgId,
            $importDate,
            PreviewProofService::DEFAULT_TTL_SECONDS,
            'v2',
            $cutoffDate
        );

        // Exact match verification succeeds
        $verified = $this->proofService->verify($proof, $fileSha256, $orgId, $importDate, 'v2', $cutoffDate);
        $this->assertTrue($verified['valid']);

        // Modified cutoff date fails verification
        $tamperedCutoff = $this->proofService->verify($proof, $fileSha256, $orgId, $importDate, 'v2', '2026-08-31');
        $this->assertFalse($tamperedCutoff['valid']);

        // Modified version fails verification
        $tamperedVersion = $this->proofService->verify($proof, $fileSha256, $orgId, $importDate, 'v1', $cutoffDate);
        $this->assertFalse($tamperedVersion['valid']);
    }

    public function test_import_execution_creates_members_and_opening_balance_drafts_atomically(): void
    {
        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [
            $this->validV2Row([
                'member_number' => 'KOP-001',
                'full_name' => 'Anggota Dengan Saldo',
                'email' => 'saldo@example.com',
                'identity_number' => '3171012301900001',
                'opening_balance_pokok' => '200000.00',
                'opening_balance_wajib' => '100000.00',
                'opening_balance_sukarela' => '50000.00',
                'opening_balance_khusus' => '0.00',
            ]),
            $this->validV2Row([
                'member_number' => 'KOP-002',
                'full_name' => 'Anggota Tanpa Saldo',
                'email' => 'tanpasaldo@example.com',
                'identity_number' => '3171012301900002',
                'opening_balance_pokok' => '0',
                'opening_balance_wajib' => '0',
                'opening_balance_sukarela' => '0',
                'opening_balance_khusus' => '0',
            ]),
        ]);

        $file = UploadedFile::fake()->createWithContent('batch_import.csv', $csv);
        $fileSha256 = hash('sha256', $csv);
        $importDate = '2026-06-01';
        $cutoffDate = '2026-09-30';

        $proof = $this->proofService->generate(
            $fileSha256,
            (string) $this->organization->id,
            $importDate,
            PreviewProofService::DEFAULT_TTL_SECONDS,
            'v2',
            $cutoffDate
        );

        $response = $this->actingAs($this->importer)->post(route('cooperative.members.import.execute'), [
            'file' => $file,
            'file_sha256' => $fileSha256,
            'organization_id' => $this->organization->id,
            'import_date' => $importDate,
            'opening_balance_cutoff_date' => $cutoffDate,
            'preview_proof' => $proof,
            'confirm_import' => true,
        ]);

        $response->assertRedirect(route('cooperative.members.import'));
        $response->assertSessionHas('success');

        // Verify members created
        $this->assertDatabaseHas('cooperative_members', ['member_no' => 'KOP-001']);
        $this->assertDatabaseHas('cooperative_members', ['member_no' => 'KOP-002']);

        $memberWithBalance = CooperativeMember::query()->where('member_no', 'KOP-001')->firstOrFail();
        $memberWithoutBalance = CooperativeMember::query()->where('member_no', 'KOP-002')->firstOrFail();

        // Exactly one opening balance batch created, for memberWithBalance
        $batches = CooperativeMemberOpeningBalanceBatch::query()->get();
        $this->assertCount(1, $batches);

        $batch = $batches->first();
        $this->assertSame($memberWithBalance->id, $batch->cooperative_member_id);
        $this->assertTrue($batch->isDraft());
        $this->assertSame('DIRECT', $batch->metadata['mode']);
        $this->assertSame('EXCEL_IMPORT', $batch->source_type);
        $this->assertNotEmpty($batch->metadata['import_id']);
        $this->assertEquals(350000.0, $batch->total_amount);

        // Lines created for positive categories (POKOK, WAJIB, SUKARELA)
        $lines = $batch->lines()->with('contributionType')->get();
        $this->assertCount(3, $lines);
        $categoriesInLines = $lines->map(fn ($line) => $line->contributionType->category)->all();
        $this->assertEqualsCanonicalizing(['POKOK', 'WAJIB', 'SUKARELA'], $categoriesInLines);

        // NO batch for memberWithoutBalance
        $this->assertDatabaseMissing('cooperative_member_opening_balance_batches', [
            'cooperative_member_id' => $memberWithoutBalance->id,
        ]);

        // STRICTLY ZERO ledger entries at import time
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);
    }

    public function test_maker_checker_enforcement_for_csv_imported_draft(): void
    {
        $csv = $this->generateCsv(MemberImportValidator::CANONICAL_HEADERS_V2, [
            $this->validV2Row([
                'member_number' => 'KOP-001',
                'email' => 'makerchecker@example.com',
                'identity_number' => '3171012301900001',
                'opening_balance_pokok' => '200000.00',
                'opening_balance_wajib' => '100000.00',
                'opening_balance_sukarela' => '0.00',
                'opening_balance_khusus' => '0.00',
            ]),
        ]);

        $file = UploadedFile::fake()->createWithContent('maker_checker.csv', $csv);
        $fileSha256 = hash('sha256', $csv);
        $importDate = '2026-06-01';
        $cutoffDate = '2026-09-30';

        $proof = $this->proofService->generate(
            $fileSha256,
            (string) $this->organization->id,
            $importDate,
            PreviewProofService::DEFAULT_TTL_SECONDS,
            'v2',
            $cutoffDate
        );

        $this->actingAs($this->importer)->post(route('cooperative.members.import.execute'), [
            'file' => $file,
            'file_sha256' => $fileSha256,
            'organization_id' => $this->organization->id,
            'import_date' => $importDate,
            'opening_balance_cutoff_date' => $cutoffDate,
            'preview_proof' => $proof,
            'confirm_import' => true,
        ]);

        $batch = CooperativeMemberOpeningBalanceBatch::query()->firstOrFail();
        $wizardService = app(CooperativeOpeningBalanceWizardService::class);

        // 1. Importer CANNOT post/approve their own imported draft (maker-checker violation)
        $thrown = false;
        try {
            $wizardService->post($batch, $this->importer);
        } catch (RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('Maker-checker violation', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Importer must not be allowed to post an imported batch draft.');
        $this->assertTrue($batch->fresh()->isDraft());
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);

        // 2. Checker (different user with Pengurus/approval role) CAN post the draft
        $postedBatch = $wizardService->post($batch->fresh(), $this->checker);
        $this->assertTrue($postedBatch->isPosted());
        $this->assertSame($this->checker->id, $postedBatch->posted_by);

        // Ledger entries created upon posting
        $ledgerEntries = CooperativeLedgerEntry::query()->where('entry_type', 'OPENING_BALANCE')->get();
        $this->assertCount(2, $ledgerEntries);
        $this->assertEquals(300000.0, $ledgerEntries->sum('credit'));

        // 3. Reversal / VOID works on posted imported batch
        $voidedBatch = $wizardService->void($postedBatch->fresh(), $this->checker, 'Pembatalan saldo awal impor');
        $this->assertTrue($voidedBatch->isVoided());
        $this->assertSame($this->checker->id, $voidedBatch->voided_by);
        $this->assertEquals(300000.0, CooperativeLedgerEntry::query()->where('entry_type', 'OPENING_BALANCE_REVERSAL')->sum('debit'));
    }

    public function test_download_template_v2_endpoint(): void
    {
        $response = $this->actingAs($this->importer)->get(route('cooperative.members.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=kojaya-member-import-v2.csv');

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        // Read first line (headers)
        $lines = explode("\n", trim($content));
        $headerLine = str_getcsv($lines[0]);

        $this->assertSame(MemberImportValidator::CANONICAL_HEADERS_V2, $headerLine);

        // Validate the downloaded template content cleanly
        $tempPath = tempnam(sys_get_temp_dir(), 'tmpl_');
        file_put_contents($tempPath, $content);

        // Create the 3 dummy employees referenced in template
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

        $result = $this->validator->validateFile($tempPath, [
            'organization_id' => $this->organization->id,
            'import_date' => '2026-06-01',
            'opening_balance_cutoff_date' => '2026-06-01',
        ]);

        unlink($tempPath);

        $this->assertTrue($result->headerValid);
        $this->assertTrue($result->valid);
        $this->assertSame(4, $result->totalRows);
        $this->assertSame(0, $result->invalidRows);
    }
}
