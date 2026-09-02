<?php

namespace Tests\Feature\Security;

use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Enums\TokenApp;
use App\Http\Resources\EmployeeCertificateResource;
use App\Http\Resources\MedicalCheckupResource;
use App\Models\DownloadLog;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenIssuanceService;
use App\Services\Security\EmployeeDocumentStorage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Tests\TestCase;

class SensitiveEmployeeFileStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Storage::fake(EmployeeDocumentStorage::DISK);
        Storage::fake(EmployeeDocumentStorage::LEGACY_DISK);
    }

    private function createCertificate(string $employeeId, array $overrides = []): EmployeeCertificate
    {
        return EmployeeCertificate::query()->create(array_merge([
            'employee_id' => $employeeId,
            'certificate_type' => CertificateType::TRAINING,
            'certificate_number' => 'CERT-'.uniqid(),
            'issue_date' => '2026-01-01',
            'status' => 'VALID',
        ], $overrides));
    }

    private function createMcu(string $employeeId, array $overrides = []): MedicalCheckup
    {
        return MedicalCheckup::query()->create(array_merge([
            'employee_id' => $employeeId,
            'checkup_date' => '2026-01-01',
            'result' => McuResult::FIT,
        ], $overrides));
    }

    public function test_certificate_upload_writes_to_employee_documents_and_not_public(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $cert = $this->createCertificate($employee->id);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Test Device');
        $this->withToken($token->plainTextToken);

        $file = UploadedFile::fake()->create('training.pdf', 120, 'application/pdf');

        $response = $this->postJson("/api/employees/{$employee->id}/certificates/{$cert->id}/upload", [
            'document' => $file,
        ]);

        $response->assertOk();
        $path = $cert->fresh()->document_path;
        $this->assertNotNull($path);

        Storage::disk(EmployeeDocumentStorage::DISK)->assertExists($path);
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->assertMissing($path);
    }

    public function test_mcu_upload_writes_to_employee_documents_and_not_public(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $mcu = $this->createMcu($employee->id);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Test Device');
        $this->withToken($token->plainTextToken);

        $file = UploadedFile::fake()->image('mcu_report.png');

        $response = $this->postJson("/api/employees/{$employee->id}/mcu/{$mcu->id}/upload", [
            'document' => $file,
        ]);

        $response->assertOk();
        $path = $mcu->fresh()->document_path;
        $this->assertNotNull($path);

        Storage::disk(EmployeeDocumentStorage::DISK)->assertExists($path);
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->assertMissing($path);
    }

    public function test_upload_responses_contain_no_storage_url_and_provide_download_url(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $cert = $this->createCertificate($employee->id);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Test Device');
        $this->withToken($token->plainTextToken);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/employees/{$employee->id}/certificates/{$cert->id}/upload", [
            'document' => $file,
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertArrayNotHasKey('document_url', $data);
        $this->assertTrue($data['has_document']);
        $this->assertStringContainsString("/api/employees/{$employee->id}/certificates/{$cert->id}/document", $data['document_download_url']);
        $this->assertStringNotContainsString('/storage/', $response->getContent());
    }

    public function test_certificate_resource_contains_no_public_url(): void
    {
        $employee = Employee::factory()->create();
        $cert = $this->createCertificate($employee->id, [
            'document_path' => "certificates/{$employee->id}/cert1.pdf",
        ]);

        $resource = (new EmployeeCertificateResource($cert))->toArray(request());

        $this->assertNull($resource['document_url']);
        $this->assertTrue($resource['has_document']);
        $this->assertStringContainsString("/api/employees/{$employee->id}/certificates/{$cert->id}/document", $resource['document_download_url']);
        $this->assertStringNotContainsString('/storage/', (string) json_encode($resource));
    }

    public function test_mcu_resource_contains_no_public_url(): void
    {
        $employee = Employee::factory()->create();
        $mcu = $this->createMcu($employee->id, [
            'document_path' => "mcu/{$employee->id}/mcu1.png",
        ]);

        $resource = (new MedicalCheckupResource($mcu))->toArray(request());

        $this->assertNull($resource['document_url']);
        $this->assertTrue($resource['has_document']);
        $this->assertStringContainsString("/api/employees/{$employee->id}/mcu/{$mcu->id}/document", $resource['document_download_url']);
        $this->assertStringNotContainsString('/storage/', (string) json_encode($resource));
    }

    public function test_same_organization_reader_with_employee_documents_read_downloads_certificate(): void
    {
        $org = Organization::factory()->create();
        $reader = User::factory()->create(['organization_id' => $org->id]);
        $reader->givePermissionTo('view_employee_unit');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $certPath = "certificates/{$employee->id}/training_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, '%PDF-1.4 test certificate binary stream');

        $cert = $this->createCertificate($employee->id, [
            'certificate_type' => CertificateType::TRAINING,
            'document_path' => $certPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($reader, TokenApp::ADMIN, 'Reader Device');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");

        $response->assertOk();
        $this->assertSame('%PDF-1.4 test certificate binary stream', $response->streamedContent());
    }

    public function test_same_organization_reader_downloads_mcu(): void
    {
        $org = Organization::factory()->create();
        $reader = User::factory()->create(['organization_id' => $org->id]);
        $reader->givePermissionTo('view_employee_unit');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $mcuPath = "mcu/{$employee->id}/mcu_checkup.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($mcuPath, '%PDF-1.4 test mcu binary stream');

        $mcu = $this->createMcu($employee->id, [
            'checkup_date' => '2026-03-01',
            'result' => McuResult::FIT,
            'document_path' => $mcuPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($reader, TokenApp::ADMIN, 'Reader Device');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/mcu/{$mcu->id}/document");

        $response->assertOk();
        $this->assertSame('%PDF-1.4 test mcu binary stream', $response->streamedContent());
    }

    public function test_unauthenticated_api_download_returns_401(): void
    {
        $employee = Employee::factory()->create();
        $cert = $this->createCertificate($employee->id, [
            'document_path' => "certificates/{$employee->id}/cert.pdf",
        ]);
        $mcu = $this->createMcu($employee->id, [
            'document_path' => "mcu/{$employee->id}/mcu.pdf",
        ]);

        $this->getJson("/api/employees/{$employee->id}/certificates/{$cert->id}/document")->assertUnauthorized();
        $this->getJson("/api/employees/{$employee->id}/mcu/{$mcu->id}/document")->assertUnauthorized();
    }

    public function test_wrong_ability_api_download_returns_403(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        // User has only attendance ability, no employee-documents:read
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $cert = $this->createCertificate($employee->id, [
            'document_path' => "certificates/{$employee->id}/cert.pdf",
        ]);
        $mcu = $this->createMcu($employee->id, [
            'document_path' => "mcu/{$employee->id}/mcu.pdf",
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ESS, 'ESS Device');
        $this->withToken($token->plainTextToken);

        $this->getJson("/api/employees/{$employee->id}/certificates/{$cert->id}/document")->assertForbidden();
        $this->getJson("/api/employees/{$employee->id}/mcu/{$mcu->id}/document")->assertForbidden();
    }

    public function test_foreign_organization_employee_returns_404(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo(['view_employee_unit', 'edit_employee']);

        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $certB = $this->createCertificate($employeeB->id, [
            'document_path' => "certificates/{$employeeB->id}/cert_b.pdf",
        ]);
        $mcuB = $this->createMcu($employeeB->id, [
            'document_path' => "mcu/{$employeeB->id}/mcu_b.pdf",
        ]);

        Storage::disk(EmployeeDocumentStorage::DISK)->put($certB->document_path, '%PDF-1.4 secret B cert');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($mcuB->document_path, '%PDF-1.4 secret B mcu');

        $token = app(TokenIssuanceService::class)->issue($userA, TokenApp::ADMIN, 'HR Device A');
        $this->withToken($token->plainTextToken);

        $this->getJson("/api/employees/{$employeeB->id}/certificates/{$certB->id}/document")->assertNotFound();
        $this->getJson("/api/employees/{$employeeB->id}/mcu/{$mcuB->id}/document")->assertNotFound();
    }

    public function test_mismatched_certificate_employee_returns_404(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_unit');

        $employee1 = Employee::factory()->create(['organization_id' => $org->id]);
        $employee2 = Employee::factory()->create(['organization_id' => $org->id]);

        $cert2 = $this->createCertificate($employee2->id, [
            'document_path' => "certificates/{$employee2->id}/cert2.pdf",
        ]);
        Storage::disk(EmployeeDocumentStorage::DISK)->put($cert2->document_path, '%PDF-1.4 cert2 content');

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Reader');
        $this->withToken($token->plainTextToken);

        // Attempting to access cert2 through employee1 prefix
        $this->getJson("/api/employees/{$employee1->id}/certificates/{$cert2->id}/document")->assertNotFound();
    }

    public function test_mismatched_mcu_employee_returns_404(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_unit');

        $employee1 = Employee::factory()->create(['organization_id' => $org->id]);
        $employee2 = Employee::factory()->create(['organization_id' => $org->id]);

        $mcu2 = $this->createMcu($employee2->id, [
            'document_path' => "mcu/{$employee2->id}/mcu2.pdf",
        ]);
        Storage::disk(EmployeeDocumentStorage::DISK)->put($mcu2->document_path, '%PDF-1.4 mcu2 content');

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Reader');
        $this->withToken($token->plainTextToken);

        // Attempting to access mcu2 through employee1 prefix
        $this->getJson("/api/employees/{$employee1->id}/mcu/{$mcu2->id}/document")->assertNotFound();
    }

    public function test_certificate_and_mcu_file_is_not_retrievable_through_public_storage(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $cert = $this->createCertificate($employee->id);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Test Device');
        $this->withToken($token->plainTextToken);

        $file = UploadedFile::fake()->create('confidential.pdf', 100, 'application/pdf');

        $this->postJson("/api/employees/{$employee->id}/certificates/{$cert->id}/upload", [
            'document' => $file,
        ])->assertOk();

        $path = $cert->fresh()->document_path;

        // Verify file is on private disk and absent from public disk
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
    }

    public function test_pdf_response_uses_application_pdf_mime_type(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $path = "certificates/{$employee->id}/sample.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4 content');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_jpeg_and_png_responses_use_the_correct_mime_type(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        // 1. JPEG certificate
        $jpgPath = "certificates/{$employee->id}/photo.jpg";
        $jpgBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00`\x00`\x00\x00\xFF\xDB\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\t\t\x08\n\x0C\x14\r\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C $.\' \",#\x1C\x1C(7),01444\x1F\'9=82<.342\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01\x11\x00\xFF\xDA\x00\x08\x01\x01\x00\x00?\x00\xBF\x00\xFF\xD9";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($jpgPath, $jpgBytes);
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $jpgPath,
        ]);

        // 2. PNG MCU
        $pngPath = "mcu/{$employee->id}/scan.png";
        $pngBytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($pngPath, $pngBytes);
        $mcu = $this->createMcu($employee->id, [
            'document_path' => $pngPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->get("/api/employees/{$employee->id}/mcu/{$mcu->id}/document")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_download_responses_contain_no_store_private_and_nosniff_headers(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $path = "certificates/{$employee->id}/doc.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    public function test_replacing_a_file_removes_the_previous_private_file_only_after_success(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $oldPath = "certificates/{$employee->id}/old_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, '%PDF-1.4 old content');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $newFile = UploadedFile::fake()->create('new_cert.pdf', 150, 'application/pdf');

        $response = $this->postJson("/api/employees/{$employee->id}/certificates/{$cert->id}/upload", [
            'document' => $newFile,
        ]);

        $response->assertOk();
        $newPath = $cert->fresh()->document_path;

        $this->assertNotSame($oldPath, $newPath);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath));
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPath));
    }

    public function test_a_failed_replacement_preserves_the_previous_file_and_database_path(): void
    {
        $employeeId = '123';
        $oldPath = "certificates/{$employeeId}/existing.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, '%PDF-1.4 preserved content');

        $storage = app(EmployeeDocumentStorage::class);
        $newFile = UploadedFile::fake()->create('new.pdf', 100, 'application/pdf');

        $exceptionThrown = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employeeId,
                $oldPath,
                function (string $path) {
                    throw new \RuntimeException('Simulated DB failure during update.');
                }
            );
        } catch (\RuntimeException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath));
        $this->assertSame('%PDF-1.4 preserved content', Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));

        // Check that any newly stored orphan file was cleaned up
        $allFiles = Storage::disk(EmployeeDocumentStorage::DISK)->allFiles("certificates/{$employeeId}");
        $this->assertEquals([$oldPath], $allFiles);
    }

    public function test_deleting_a_record_deletes_its_owned_private_file(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $certPath = "certificates/{$employee->id}/to_delete.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, '%PDF-1.4 to delete');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $certPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $this->deleteJson("/api/employees/{$employee->id}/certificates/{$cert->id}")->assertOk();

        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($certPath));
    }

    public function test_a_foreign_failed_mutation_does_not_alter_database_state_or_storage(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo(['edit_employee', 'view_employee_unit']);

        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $certPath = "certificates/{$employeeB->id}/b_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, '%PDF-1.4 intact B cert');
        $certB = $this->createCertificate($employeeB->id, [
            'certificate_number' => 'CERT-B-ORIG',
            'document_path' => $certPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($userA, TokenApp::ADMIN, 'Admin A');
        $this->withToken($token->plainTextToken);

        // Foreign delete attempt
        $this->deleteJson("/api/employees/{$employeeB->id}/certificates/{$certB->id}")->assertNotFound();

        // Foreign upload attempt
        $maliciousFile = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');
        $this->postJson("/api/employees/{$employeeB->id}/certificates/{$certB->id}/upload", [
            'document' => $maliciousFile,
        ])->assertNotFound();

        // Foreign update attempt
        $this->putJson("/api/employees/{$employeeB->id}/certificates/{$certB->id}", [
            'certificate_number' => 'TAMPERED',
        ])->assertNotFound();

        $this->assertDatabaseHas('employee_certificates', [
            'id' => $certB->id,
            'certificate_number' => 'CERT-B-ORIG',
            'document_path' => $certPath,
        ]);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($certPath));
        $this->assertSame('%PDF-1.4 intact B cert', Storage::disk(EmployeeDocumentStorage::DISK)->get($certPath));
    }

    public function test_migration_dry_run_performs_no_copy_or_delete(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/legacy_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 legacy content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private')
            ->expectsOutputToContain('DRY-RUN mode')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
    }

    public function test_migration_copy_phase_verifies_the_destination_and_preserves_the_source(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/legacy_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 legacy binary bytes');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('%PDF-1.4 legacy binary bytes', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
        // Source is preserved when --cleanup is not requested
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
    }

    public function test_migration_is_idempotent(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/legacy_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 sample bytes');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        // First run
        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(0);

        // Second run
        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->expectsOutputToContain('Already Verified on Private Disk')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('%PDF-1.4 sample bytes', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_migration_conflict_fails_closed_and_does_not_overwrite_or_delete_either_copy(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/conflicting.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 source content A');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4 different destination content B');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('%PDF-1.4 source content A', Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->get($path));
        $this->assertSame('%PDF-1.4 different destination content B', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_cleanup_deletes_only_a_verified_public_source(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/cleanup_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 cleanup content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('%PDF-1.4 cleanup content', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
    }

    public function test_invalid_path_prefixes_and_directory_traversal_are_rejected(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $this->expectException(InvalidArgumentException::class);
        $storage->validatePath('../../../etc/passwd');
    }

    public function test_web_signed_document_download_controller_enforces_organization_scope_and_correct_mime(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userA->givePermissionTo('view_employee_unit');

        $employeeA = Employee::factory()->create(['organization_id' => $orgA->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        $certAPath = "certificates/{$employeeA->id}/valid_cert.png";
        $pngBytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certAPath, $pngBytes);
        $certA = $this->createCertificate($employeeA->id, [
            'document_path' => $certAPath,
        ]);

        $certBPath = "certificates/{$employeeB->id}/foreign_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certBPath, '%PDF-1.4 pdf bytes');
        $certB = $this->createCertificate($employeeB->id, [
            'document_path' => $certBPath,
        ]);

        // 1. Valid signed URL for same-org employee -> 200 OK + correct image/png Content-Type + nosniff
        $validSignedUrl = URL::temporarySignedRoute(
            'download.certificate',
            now()->addMinutes(30),
            ['employee' => $employeeA->id, 'certificate' => $certA->id]
        );

        $responseA = $this->actingAs($userA)->get($validSignedUrl);
        $responseA->assertOk();
        $responseA->assertHeader('Content-Type', 'image/png');
        $responseA->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($pngBytes, $responseA->streamedContent());

        // 2. Foreign-organization signed download -> 404 Not Found
        $foreignSignedUrl = URL::temporarySignedRoute(
            'download.certificate',
            now()->addMinutes(30),
            ['employee' => $employeeB->id, 'certificate' => $certB->id]
        );

        $responseB = $this->actingAs($userA)->get($foreignSignedUrl);
        $responseB->assertNotFound();

        // 3. Invalid signature -> 403
        $this->actingAs($userA)->get("/download/certificate/{$employeeA->id}/{$certA->id}")->assertForbidden();
    }

    // ==========================================
    // R2 MANDATORY TESTS
    // ==========================================

    public function test_certificate_for_employee_a_pointing_to_employee_b_path_is_rejected(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match expected employee ID');
        $storage->validateOwnedPath('certificates/999/cert.pdf', EmployeeDocumentStorage::PREFIX_CERTIFICATES, '100');
    }

    public function test_mcu_for_employee_a_pointing_to_employee_b_path_is_rejected(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match expected employee ID');
        $storage->validateOwnedPath('mcu/888/mcu.pdf', EmployeeDocumentStorage::PREFIX_MCU, '200');
    }

    public function test_certificate_pointing_to_mcu_prefix_is_rejected(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match expected prefix');
        $storage->validateOwnedPath('mcu/100/cert.pdf', EmployeeDocumentStorage::PREFIX_CERTIFICATES, '100');
    }

    public function test_mcu_pointing_to_certificates_prefix_is_rejected(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not match expected prefix');
        $storage->validateOwnedPath('certificates/200/mcu.pdf', EmployeeDocumentStorage::PREFIX_MCU, '200');
    }

    public function test_replacement_cannot_delete_another_employees_file(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $victimPath = 'certificates/victim-999/victim_cert.pdf';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($victimPath, '%PDF-1.4 victim confidential file');

        $attackerEmployeeId = 'attacker-111';
        $file = UploadedFile::fake()->create('attacker.pdf', 100, 'application/pdf');

        $this->expectException(InvalidArgumentException::class);

        try {
            $storage->replace(
                $file,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $attackerEmployeeId,
                $victimPath,
                function (string $path) {}
            );
        } finally {
            // Victim file must remain completely intact
            $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($victimPath));
            $this->assertSame('%PDF-1.4 victim confidential file', Storage::disk(EmployeeDocumentStorage::DISK)->get($victimPath));
        }
    }

    public function test_destroy_cannot_delete_another_employees_file(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);

        $employeeA = Employee::factory()->create(['organization_id' => $org->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $org->id]);

        $victimPath = "certificates/{$employeeB->id}/victim_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($victimPath, '%PDF-1.4 victim cert content');

        // Employee A's certificate is maliciously pointed to employee B's path
        $certA = $this->createCertificate($employeeA->id, [
            'document_path' => $victimPath,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->deleteJson("/api/employees/{$employeeA->id}/certificates/{$certA->id}");
        $response->assertStatus(400);

        // Victim file must remain intact
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($victimPath));
        // Database record for certA must still exist
        $this->assertModelExists($certA);
    }

    public function test_failed_public_delete_keeps_database_record(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);

        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $certPath = "certificates/{$employee->id}/test_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($certPath, '%PDF-1.4 legacy cert');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, '%PDF-1.4 private cert');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $certPath,
        ]);

        // Mock public disk delete to fail
        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('exists')->with($certPath)->andReturn(true);
        $publicMock->shouldReceive('delete')->with($certPath)->andReturn(false);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->deleteJson("/api/employees/{$employee->id}/certificates/{$cert->id}");
        $response->assertStatus(500);

        $this->assertModelExists($cert);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($certPath));
    }

    public function test_failed_private_delete_keeps_database_record(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo(['edit_employee', 'view_employee_all']);

        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $certPath = "certificates/{$employee->id}/test_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, '%PDF-1.4 private cert');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $certPath,
        ]);

        // Mock private disk delete to fail
        $privateMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::DISK))->makePartial();
        $privateMock->shouldReceive('exists')->with($certPath)->andReturn(true);
        $privateMock->shouldReceive('delete')->with($certPath)->andReturn(false);
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->deleteJson("/api/employees/{$employee->id}/certificates/{$cert->id}");
        $response->assertStatus(500);

        $this->assertModelExists($cert);
    }

    public function test_migration_delete_returning_false_produces_exit_code_1(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 content');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4 content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('exists')->with($path)->andReturn(true);
        $publicMock->shouldReceive('delete')->with($path)->andReturn(false);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_public_source_still_existing_after_delete_produces_exit_code_1(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 content');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4 content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        // Return true on delete but still exists
        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('exists')->with($path)->andReturn(true);
        $publicMock->shouldReceive('delete')->with($path)->andReturn(true);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_cleaned_count_increments_only_after_verified_absence(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/verified_clean.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 clean content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Cleaned from Public Disk')
            ->assertExitCode(0);

        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
    }

    public function test_soft_deleted_certificate_public_file_is_inventoried(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/soft_deleted_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 soft deleted cert');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);
        $cert->delete(); // Soft-delete

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->expectsOutputToContain('Soft-Deleted Records Inspected')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('%PDF-1.4 soft deleted cert', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_soft_deleted_mcu_public_file_is_inventoried(): void
    {
        $employee = Employee::factory()->create();
        $path = "mcu/{$employee->id}/soft_deleted_mcu.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, '%PDF-1.4 soft deleted mcu');

        $mcu = $this->createMcu($employee->id, [
            'document_path' => $path,
        ]);
        $mcu->delete(); // Soft-delete

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->expectsOutputToContain('Soft-Deleted Records Inspected')
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('%PDF-1.4 soft deleted mcu', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_unreferenced_public_certificate_file_is_reported(): void
    {
        $orphanPath = 'certificates/999/unreferenced_cert.pdf';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($orphanPath, '%PDF-1.4 orphan cert');

        $this->artisan('security:migrate-employee-documents-private')
            ->expectsOutputToContain('Unreferenced public orphan file detected: [certificates/999/unreferenced_cert.pdf]')
            ->assertExitCode(1);
    }

    public function test_unreferenced_public_mcu_file_is_reported(): void
    {
        $orphanPath = 'mcu/999/unreferenced_mcu.pdf';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($orphanPath, '%PDF-1.4 orphan mcu');

        $this->artisan('security:migrate-employee-documents-private')
            ->expectsOutputToContain('Unreferenced public orphan file detected: [mcu/999/unreferenced_mcu.pdf]')
            ->assertExitCode(1);
    }

    public function test_unresolved_public_orphan_prevents_full_success(): void
    {
        $orphanPath = 'certificates/999/orphan.pdf';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($orphanPath, '%PDF-1.4 orphan bytes');

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(1);
    }

    public function test_missing_source_and_target_produces_non_zero_result(): void
    {
        $employee = Employee::factory()->create();
        $this->createCertificate($employee->id, [
            'document_path' => "certificates/{$employee->id}/non_existent.pdf",
        ]);

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->expectsOutputToContain('Missing Files (Source & Target Absent)')
            ->assertExitCode(1);
    }

    public function test_missing_download_file_does_not_create_download_log(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => "certificates/{$employee->id}/missing.pdf",
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");
        $response->assertNotFound();

        $this->assertSame(0, DownloadLog::query()->where('document_type', 'certificate')->count());
    }

    public function test_successful_download_creates_exactly_one_download_log(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $path = "certificates/{$employee->id}/valid.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, '%PDF-1.4 content');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");
        $response->assertOk();

        $logs = DownloadLog::query()
            ->where('document_type', 'certificate')
            ->where('document_id', $cert->id)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertSame($user->id, $logs->first()->user_id);
    }

    public function test_mismatched_actual_mime_does_not_receive_a_trusted_image_pdf_mime_solely_from_its_extension(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_employee_all');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);

        $path = "certificates/{$employee->id}/fake.pdf";
        // Plain text script masquerading as PDF extension
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, 'PLAIN TEXT SCRIPT CONTENT');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $token = app(TokenIssuanceService::class)->issue($user, TokenApp::ADMIN, 'Admin');
        $this->withToken($token->plainTextToken);

        $response = $this->get("/api/employees/{$employee->id}/certificates/{$cert->id}/document");
        $response->assertOk();
        // Since detected mime is text/plain, it must NOT be trusted as application/pdf
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    // ==========================================
    // R3 MANDATORY DATA-SAFETY TESTS
    // ==========================================

    public function test_migration_cleanup_public_delete_throws_retains_verified_private_copy_and_returns_exit_code_1(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/throw_on_delete.pdf";
        $content = '%PDF-1.4 verified content to retain';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, $content);

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        // Mock public disk delete to throw
        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('delete')->with($path)->andThrow(new \RuntimeException('Public disk deletion IO exception'));
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Exception during public source cleanup')
            ->assertExitCode(1);

        // Verified private copy MUST be retained and intact
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame($content, Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
        // Legacy public file still exists because delete threw
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
    }

    public function test_migration_cleanup_post_delete_exists_throws_retains_verified_private_copy_and_returns_exit_code_1(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/throw_on_post_exists.pdf";
        $content = '%PDF-1.4 verified content to retain 2';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, $content);

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        // Mock public disk: delete succeeds, but post-delete exists() verification throws
        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('delete')->with($path)->andReturn(true);
        // First exists() calls during scan and verification return true, but post-delete exists() call throws
        $existsCallCount = 0;
        $publicMock->shouldReceive('exists')->with($path)->andReturnUsing(function () use (&$existsCallCount) {
            $existsCallCount++;
            // During copy/verification (1st/2nd call) return actual, on post-delete verification throw
            if ($existsCallCount >= 2) {
                throw new \RuntimeException('Connection lost during post-delete verification');
            }

            return true;
        });
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Exception during public source cleanup')
            ->assertExitCode(1);

        // Verified private copy MUST be retained
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame($content, Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_migration_already_private_cleanup_throws_retains_verified_private_copy_and_returns_exit_code_1(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/already_private_throw.pdf";
        $content = '%PDF-1.4 already private bytes';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, $content);
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, $content);

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $publicMock = \Mockery::mock(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK))->makePartial();
        $publicMock->shouldReceive('delete')->with($path)->andThrow(new \RuntimeException('Permission denied during delete'));
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Exception during public source cleanup')
            ->assertExitCode(1);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame($content, Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_replacement_compensates_when_legacy_public_delete_returns_false_and_old_file_confirmed_present(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_false.pdf";
        $oldContent = '%PDF-1.4 original public document';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $publicMock->shouldReceive('get')->with($oldPath)->andReturn($oldContent);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement.pdf', '%PDF-1.4 new valid replacement');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Storage driver returned false', $e->getMessage());
        }

        $this->assertTrue($caught, 'Expected replace() to throw RuntimeException on delete failure');

        // Compensating assertions (Requirement 7 & R5 Requirement 3):
        // 1. DB path safely rolls back to previousPath
        $this->assertSame($oldPath, $cert->fresh()->document_path);
        // 2. Old file remains readable
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath) || Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($oldPath));
        // 3. New private file was removed and verified deleted (assert exists === false on captured newPath)
        $this->assertNotNull($newPathCaptured);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_compensates_when_delete_throws_before_performing_deletion(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_throw_before_delete.pdf";
        $oldContent = '%PDF-1.4 original persistent document before throw';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath) {
            if ($path === $oldPath) {
                throw new \RuntimeException('S3 delete connection timeout before delete');
            }

            return $realPrivateDisk->delete($path);
        });
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement2.pdf', '%PDF-1.4 new valid replacement 2');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertSame('S3 delete connection timeout before delete', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Compensating assertions (Requirement 7 & R5 Requirement 3):
        $this->assertSame($oldPath, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath));
        $this->assertSame($oldContent, Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));
        $this->assertNotNull($newPathCaptured);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_handles_public_old_file_actually_deleted_then_post_delete_exists_throws(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_actually_deleted_ambiguous.pdf";
        $oldContent = '%PDF-1.4 original public doc to delete';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        // delete actually deletes the file
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk) {
            return $realPublicDisk->delete($path);
        });
        // exists pre-checks work, post-delete check throws
        $existsCount = 0;
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk, &$existsCount) {
            $existsCount++;
            if ($existsCount <= 2) {
                return $realPublicDisk->exists($path);
            }

            throw new \RuntimeException('Network split on public post-delete exists check');
        });
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        // Ensure private disk does not hold an old copy
        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $privateMock->shouldReceive('put')->with($oldPath, \Mockery::any())->andReturn(false);
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath) {
            if ($path === $oldPath) {
                return false;
            }

            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath) {
            if ($path === $oldPath) {
                return false;
            }

            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement3.pdf', '%PDF-1.4 new valid replacement 3');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert) {
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
            $this->assertStringContainsString('Network split on public post-delete exists check', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Ambiguous assertions (Requirement 6):
        // 1. DB remains on newPath (never rolled back to missing oldPath)
        $currentDbPath = $cert->fresh()->document_path;
        $this->assertNotSame($oldPath, $currentDbPath);
        $this->assertStringStartsWith("certificates/{$employee->id}/", $currentDbPath);

        // 2. New private file remains present and readable on private disk
        $this->assertTrue($realPrivateDisk->exists($currentDbPath));
        $this->assertSame('%PDF-1.4 new valid replacement 3', $realPrivateDisk->get($currentDbPath));

        // 3. Old public file was actually removed
        $this->assertFalse($realPublicDisk->exists($oldPath));
    }

    public function test_replacement_handles_private_old_file_actually_deleted_then_post_delete_exists_throws(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_priv_actually_deleted_ambiguous.pdf";
        $oldContent = '%PDF-1.4 private doc to delete';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->delete($path);
        });

        $existsCount = 0;
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath, &$existsCount) {
            if ($path === $oldPath) {
                $existsCount++;
                if ($existsCount >= 2) {
                    throw new \RuntimeException('Network timeout on private post-delete exists check');
                }
            }

            return $realPrivateDisk->exists($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement4.pdf', '%PDF-1.4 new valid replacement 4');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert) {
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Ambiguous assertions (Requirement 6):
        $currentDbPath = $cert->fresh()->document_path;
        $this->assertNotSame($oldPath, $currentDbPath);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($currentDbPath));
        $this->assertSame('%PDF-1.4 new valid replacement 4', Storage::disk(EmployeeDocumentStorage::DISK)->get($currentDbPath));
    }

    public function test_replacement_handles_both_public_and_private_ambiguous_verification(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_both_ambiguous.pdf";
        $oldContent = '%PDF-1.4 dual disk doc';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk) {
            return $realPublicDisk->delete($path);
        });
        $pubExistsCount = 0;
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk, &$pubExistsCount) {
            $pubExistsCount++;
            if ($pubExistsCount >= 2) {
                throw new \RuntimeException('Public exists verification failed');
            }

            return $realPublicDisk->exists($path);
        });
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->delete($path);
        });
        $privExistsCount = 0;
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath, &$privExistsCount) {
            if ($path === $oldPath) {
                $privExistsCount++;
                if ($privExistsCount >= 2) {
                    throw new \RuntimeException('Private exists verification failed');
                }
            }

            return $realPrivateDisk->exists($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement5.pdf', '%PDF-1.4 new valid replacement 5');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert) {
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Ambiguous assertions (Requirement 6):
        $currentDbPath = $cert->fresh()->document_path;
        $this->assertNotSame($oldPath, $currentDbPath);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($currentDbPath));
        $this->assertSame('%PDF-1.4 new valid replacement 5', Storage::disk(EmployeeDocumentStorage::DISK)->get($currentDbPath));
    }

    public function test_replacement_preserves_new_file_when_rollback_callback_itself_fails(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_rollback_callback_fails.pdf";
        $oldContent = '%PDF-1.4 persistent document';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath) {
            if ($path === $oldPath) {
                return false;
            }

            return $realPrivateDisk->delete($path);
        });
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement6.pdf', '%PDF-1.4 new valid replacement 6');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                },
                function (string $path) {
                    throw new \RuntimeException('Database deadlock during rollback callback');
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Database deadlock during rollback callback', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Because rollback failed:
        // 1. DB points to newPath (updated during onUpdateDb)
        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        // 2. newPath was NOT deleted from disk (it is preserved)
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
        $this->assertSame('%PDF-1.4 new valid replacement 6', Storage::disk(EmployeeDocumentStorage::DISK)->get($newPathCaptured));
    }

    public function test_replacement_preserves_materialized_private_safety_copy_and_succeeds_rollback_when_public_becomes_unavailable(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_legacy_only_safety_test.pdf";
        $oldContent = '%PDF-1.4 legacy only document that will be materialized';

        // File exists ONLY on legacy public disk initially
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath));

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $publicMock->shouldReceive('get')->with($oldPath)->andReturn($oldContent);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_safety.pdf', '%PDF-1.4 new valid replacement safety');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Storage driver returned false', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Public disk now completely fails / becomes unavailable
        $brokenPublicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $brokenPublicMock->shouldReceive('exists')->andReturn(false);
        $brokenPublicMock->shouldReceive('get')->andThrow(new \RuntimeException('Public disk permanently unavailable'));
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $brokenPublicMock);

        // Assertions for R5 Requirement 2:
        // 1. DB path safely rolled back to previousPath
        $this->assertSame($oldPath, $cert->fresh()->document_path);

        // 2. Verified private safety copy was preserved on employee_documents disk!
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($oldPath));
        $this->assertSame($oldContent, Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));

        // 3. Document is safely downloadable from private disk even with public storage unavailable
        $downloadResponse = $storage->download($oldPath, 'cert.pdf', EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $downloadResponse);

        // 4. New file was verified deleted from private storage
        $this->assertNotNull($newPathCaptured);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_rejects_zero_byte_or_empty_old_file_and_preserves_new_document(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_zero_byte.pdf";

        // Previous file is empty/0-byte on disk
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, '');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        // Delete returns false
        $privateMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $privateMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $privateMock->shouldReceive('get')->with($oldPath)->andReturn('');
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_nonzero.pdf', '%PDF-1.4 new valid non-zero replacement');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Assertions for R5 Requirement 1:
        // 1. DB was NOT rolled back to the 0-byte corrupt file; DB remains on newPath
        $currentDbPath = $cert->fresh()->document_path;
        $this->assertSame($newPathCaptured, $currentDbPath);

        // 2. New valid file is preserved on private disk
        $this->assertTrue($realPrivateDisk->exists($currentDbPath));
        $this->assertSame('%PDF-1.4 new valid non-zero replacement', $realPrivateDisk->get($currentDbPath));
    }

    public function test_replacement_rejects_hash_mismatched_corrupt_old_file_and_preserves_new_document(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_hash_mismatch.pdf";
        $initialContent = '%PDF-1.4 original valid content before corruption';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $initialContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $callCount = 0;
        $privateMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $privateMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        // Pre-capture returns initialContent, but post-cleanup check returns corrupted content
        $privateMock->shouldReceive('get')->with($oldPath)->andReturnUsing(function () use (&$callCount, $initialContent) {
            $callCount++;
            if ($callCount <= 1) {
                return $initialContent;
            }

            return '%PDF-1.4 CORRUPTED DATA BIT ROT';
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_valid.pdf', '%PDF-1.4 new valid replacement doc');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // DB was NOT rolled back to corrupt file; DB remains on newPath
        $currentDbPath = $cert->fresh()->document_path;
        $this->assertSame($newPathCaptured, $currentDbPath);
        $this->assertTrue($realPrivateDisk->exists($currentDbPath));
        $this->assertSame('%PDF-1.4 new valid replacement doc', $realPrivateDisk->get($currentDbPath));
    }

    public function test_replacement_reports_unresolved_private_orphan_when_new_file_delete_returns_false(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_orphan_delete_false.pdf";
        $oldContent = '%PDF-1.4 valid old document';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $newPathCaptured = null;

        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath, &$newPathCaptured) {
            if ($path === $oldPath) {
                return false; // Trigger rollback
            }
            if ($newPathCaptured && $path === $newPathCaptured) {
                return false; // New file deletion fails!
            }

            return $realPrivateDisk->delete($path);
        });
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_orphan1.pdf', '%PDF-1.4 new valid replacement orphan 1');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('unresolved private orphan', $e->getMessage());
            $this->assertStringContainsString($newPathCaptured, $e->getMessage());
        }

        $this->assertTrue($caught);

        // Assertions for R5 Requirement 3:
        // 1. DB rolled back to previousPath
        $this->assertSame($oldPath, $cert->fresh()->document_path);
        // 2. Old file remains readable
        $this->assertSame($oldContent, Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));
        // 3. New file still exists on disk (reported as orphan)
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_reports_unresolved_private_orphan_when_new_file_post_delete_exists_throws(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_orphan_post_exists_throws.pdf";
        $oldContent = '%PDF-1.4 valid old document 2';
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $newPathCaptured = null;

        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk, $oldPath) {
            if ($path === $oldPath) {
                return false; // Trigger rollback
            }

            return $realPrivateDisk->delete($path);
        });
        $newExistsCount = 0;
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk, &$newPathCaptured, &$newExistsCount) {
            if ($newPathCaptured && $path === $newPathCaptured) {
                $newExistsCount++;
                if ($newExistsCount >= 2) {
                    throw new \RuntimeException('Network timeout on new file post-delete exists check');
                }
            }

            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_orphan2.pdf', '%PDF-1.4 new valid replacement orphan 2');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('unresolved private orphan', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Assertions for R5 Requirement 3:
        $this->assertSame($oldPath, $cert->fresh()->document_path);
        $this->assertSame($oldContent, Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));
    }

    public function test_replacement_succeeds_when_public_exists_returns_true_but_get_throws_and_delete_succeeds(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_get_throws_delete_succeeds.pdf";
        $oldContent = '%PDF-1.4 unreadable doc that will be deleted';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('get')->with($oldPath)->andThrow(new \RuntimeException('Cannot read unreadable public file'));
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk) {
            return $realPublicDisk->delete($path);
        });
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturnUsing(function ($path) use ($realPublicDisk) {
            return $realPublicDisk->exists($path);
        });
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_pub_clean.pdf', '%PDF-1.4 valid new file');

        $newPath = $storage->replace(
            $newFile,
            EmployeeDocumentStorage::PREFIX_CERTIFICATES,
            $employee->id,
            $cert->document_path,
            function (string $path) use ($cert) {
                $cert->update(['document_path' => $path]);
            }
        );

        $this->assertSame($newPath, $cert->fresh()->document_path);
        $this->assertFalse($realPublicDisk->exists($oldPath));
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPath));
        $this->assertSame('%PDF-1.4 valid new file', Storage::disk(EmployeeDocumentStorage::DISK)->get($newPath));
    }

    public function test_replacement_fails_and_preserves_new_document_when_public_exists_true_get_throws_and_delete_fails(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_get_throws_delete_fails.pdf";
        $oldContent = '%PDF-1.4 corrupted public doc';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('get')->with($oldPath)->andThrow(new \RuntimeException('Disk I/O failure on public get'));
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_pub_fail.pdf', '%PDF-1.4 new valid doc');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught, 'Expected replace() to fail when old public file cannot be deleted');

        // Cannot return success while old public file still exists!
        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
        $this->assertTrue($realPublicDisk->exists($oldPath));
    }

    public function test_replacement_succeeds_when_public_old_file_is_zero_byte_and_delete_succeeds(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_zero_byte_success.pdf";

        // Create 0-byte file on public disk
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, '');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_after_zero.pdf', '%PDF-1.4 new valid doc after 0-byte');

        $newPath = $storage->replace(
            $newFile,
            EmployeeDocumentStorage::PREFIX_CERTIFICATES,
            $employee->id,
            $cert->document_path,
            function (string $path) use ($cert) {
                $cert->update(['document_path' => $path]);
            }
        );

        $this->assertSame($newPath, $cert->fresh()->document_path);
        $this->assertFalse(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($oldPath));
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPath));
    }

    public function test_replacement_fails_and_preserves_new_document_when_public_old_file_is_zero_byte_and_delete_fails(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_zero_byte_delete_fails.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, '');

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $publicMock->shouldReceive('get')->with($oldPath)->andReturn('');
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_after_zero_fail.pdf', '%PDF-1.4 new valid doc 2');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Never roll back to zero-byte file; DB remains on newPath
        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
        $this->assertTrue($realPublicDisk->exists($oldPath));
    }

    public function test_replacement_handles_public_existence_pre_check_throw_and_preserves_new_document(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_precheck_throws.pdf";
        $oldContent = '%PDF-1.4 original content';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $oldContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $publicMock->shouldReceive('exists')->with($oldPath)->andThrow(new \RuntimeException('Network timeout during public existence check'));
        $publicMock->shouldReceive('delete')->with($oldPath)->andThrow(new \RuntimeException('Network timeout during public delete'));
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_precheck_throw.pdf', '%PDF-1.4 new replacement');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_rejects_rollback_when_public_content_changes_between_evidence_capture_and_cleanup(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_pub_content_changes.pdf";
        $initialContent = '%PDF-1.4 original initial content';
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($oldPath, $initialContent);

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPublicDisk = Storage::disk(EmployeeDocumentStorage::LEGACY_DISK);
        $publicMock = \Mockery::mock($realPublicDisk)->makePartial();
        $callCount = 0;
        $publicMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $publicMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $publicMock->shouldReceive('get')->with($oldPath)->andReturnUsing(function () use (&$callCount, $initialContent) {
            $callCount++;
            if ($callCount <= 1) {
                return $initialContent; // captureDocumentEvidence gets original
            }

            return '%PDF-1.4 modified after capture'; // post-cleanup verification gets altered content
        });
        Storage::set(EmployeeDocumentStorage::LEGACY_DISK, $publicMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_content_changed.pdf', '%PDF-1.4 new valid doc');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // DB was NOT rolled back to modified file; DB remains on newPath
        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_replacement_rejects_rollback_when_evidence_capture_failed_even_if_old_file_later_readable(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $oldPath = "certificates/{$employee->id}/old_cert_evidence_null_later_readable.pdf";
        $validContent = '%PDF-1.4 late readable valid content';

        $cert = $this->createCertificate($employee->id, [
            'document_path' => $oldPath,
        ]);

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $getCallCount = 0;
        $privateMock->shouldReceive('delete')->with($oldPath)->andReturn(false);
        $privateMock->shouldReceive('exists')->with($oldPath)->andReturn(true);
        $privateMock->shouldReceive('get')->with($oldPath)->andReturnUsing(function () use (&$getCallCount, $validContent) {
            $getCallCount++;
            if ($getCallCount <= 1) {
                return false; // captureDocumentEvidence fails => previousEvidence is null
            }

            return $validContent; // Later reads return valid content
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_replacement_evidence_null.pdf', '%PDF-1.4 new valid replacement');

        $caught = false;
        $newPathCaptured = null;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                $cert->document_path,
                function (string $path) use ($cert, &$newPathCaptured) {
                    if ($newPathCaptured === null) {
                        $newPathCaptured = $path;
                    }
                    $cert->update(['document_path' => $path]);
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Ambiguous or incomplete cleanup', $e->getMessage());
        }

        $this->assertTrue($caught);

        // Because pre-cleanup evidence was null, DB rollback was strictly prohibited!
        $this->assertSame($newPathCaptured, $cert->fresh()->document_path);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_store_and_update_reports_unresolved_private_orphan_when_initial_db_update_fails_and_delete_returns_false(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $newPathCaptured = null;

        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk, &$newPathCaptured) {
            if ($newPathCaptured && $path === $newPathCaptured) {
                return false; // Deletion of orphan returns false!
            }

            return $realPrivateDisk->delete($path);
        });
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_orphan_db_fail1.pdf', '%PDF-1.4 new valid doc orphan 1');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                null,
                function (string $path) use (&$newPathCaptured) {
                    $newPathCaptured = $path;
                    throw new \RuntimeException('Deadlock during DB insert on update callback');
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('unresolved private orphan', $e->getMessage());
            $this->assertStringContainsString($newPathCaptured, $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertSame('Deadlock during DB insert on update callback', $e->getPrevious()->getMessage());
        }

        $this->assertTrue($caught);
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($newPathCaptured));
    }

    public function test_store_and_update_reports_unresolved_private_orphan_when_initial_db_update_fails_and_post_delete_exists_throws(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();

        $realPrivateDisk = Storage::disk(EmployeeDocumentStorage::DISK);
        $privateMock = \Mockery::mock($realPrivateDisk)->makePartial();
        $newPathCaptured = null;

        $privateMock->shouldReceive('delete')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->delete($path);
        });
        $postExistsCount = 0;
        $privateMock->shouldReceive('exists')->andReturnUsing(function ($path) use ($realPrivateDisk, &$newPathCaptured, &$postExistsCount) {
            if ($newPathCaptured && $path === $newPathCaptured) {
                $postExistsCount++;
                if ($postExistsCount >= 2) {
                    throw new \RuntimeException('Network timeout on post-delete orphan exists check');
                }
            }

            return $realPrivateDisk->exists($path);
        });
        $privateMock->shouldReceive('get')->andReturnUsing(function ($path) use ($realPrivateDisk) {
            return $realPrivateDisk->get($path);
        });
        Storage::set(EmployeeDocumentStorage::DISK, $privateMock);

        $newFile = UploadedFile::fake()->createWithContent('new_orphan_db_fail2.pdf', '%PDF-1.4 new valid doc orphan 2');

        $caught = false;
        try {
            $storage->replace(
                $newFile,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employee->id,
                null,
                function (string $path) use (&$newPathCaptured) {
                    $newPathCaptured = $path;
                    throw new \RuntimeException('Database constraint error on insert');
                }
            );
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('unresolved private orphan', $e->getMessage());
            $this->assertStringContainsString($newPathCaptured, $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertSame('Database constraint error on insert', $e->getPrevious()->getMessage());
        }

        $this->assertTrue($caught);
    }

    public function test_is_confirmed_present_and_readable_integrity_verification(): void
    {
        $storage = app(EmployeeDocumentStorage::class);
        $employee = Employee::factory()->create();
        $validPath = "certificates/{$employee->id}/valid_test_doc.pdf";
        $content = '%PDF-1.4 test valid content';

        Storage::disk(EmployeeDocumentStorage::DISK)->put($validPath, $content);
        $evidence = [
            'size' => strlen($content),
            'sha256' => hash('sha256', $content),
        ];

        // Valid evidence match => true
        $this->assertTrue($storage->isConfirmedPresentAndReadable($validPath, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id, $evidence));

        // Missing file => false
        $this->assertFalse($storage->isConfirmedPresentAndReadable("certificates/{$employee->id}/nonexistent.pdf", EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id, $evidence));

        // Zero-byte file => false
        $zeroBytePath = "certificates/{$employee->id}/zerobyte.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($zeroBytePath, '');
        $this->assertFalse($storage->isConfirmedPresentAndReadable($zeroBytePath, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id));

        // SHA-256 mismatch => false
        $badHashEvidence = [
            'size' => strlen($content),
            'sha256' => hash('sha256', 'completely different content'),
        ];
        $this->assertFalse($storage->isConfirmedPresentAndReadable($validPath, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id, $badHashEvidence));

        // Size mismatch => false
        $badSizeEvidence = [
            'size' => 99999,
            'sha256' => hash('sha256', $content),
        ];
        $this->assertFalse($storage->isConfirmedPresentAndReadable($validPath, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employee->id, $badSizeEvidence));
    }

    public function test_safe_filename_regex_accepts_valid_filenames(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $validPaths = [
            'certificates/123/cert1.pdf',
            'certificates/emp-1/my_training-cert.pdf',
            'certificates/EMP_99/cert.PDF',
            'mcu/456/mcu_2026.png',
            'mcu/1/0123456789abcdef0123456789abcdef.jpg',
        ];

        foreach ($validPaths as $path) {
            // Should not throw
            $storage->validatePath($path);
        }

        $this->assertTrue(true);
    }

    public function test_safe_filename_regex_rejects_invalid_characters_and_formats(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $invalidFilenames = [
            'certificates/123/cert file.pdf',   // Space
            'certificates/123/cert..pdf',       // Double dots
            'certificates/123/cert',            // Missing extension
            'certificates/123/cert.pdf.exe',    // Multiple extension dots
            'certificates/123/cert@name.pdf',   // Special char @
            'certificates/123/cert$1.pdf',      // Special char $
            'certificates/123/cert;rm.pdf',     // Semicolon
            "certificates/123/cert\0.pdf",      // Null byte
            'certificates/123/.hidden.pdf',     // Leading dot
        ];

        foreach ($invalidFilenames as $invalidPath) {
            try {
                $storage->validatePath($invalidPath);
                $this->fail("Expected validatePath to reject invalid path: [{$invalidPath}]");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid', $e->getMessage());
            }
        }
    }

    public function test_safe_employee_id_regex_rejects_invalid_characters(): void
    {
        $storage = app(EmployeeDocumentStorage::class);

        $invalidEmployeeIds = [
            'certificates/emp id/cert.pdf',     // Space
            'certificates/emp@1/cert.pdf',      // Special char @
            'certificates/emp#1/cert.pdf',      // Special char #
            'certificates/../cert.pdf',         // Path traversal segment
        ];

        foreach ($invalidEmployeeIds as $invalidPath) {
            try {
                $storage->validatePath($invalidPath);
                $this->fail("Expected validatePath to reject invalid employee ID in path: [{$invalidPath}]");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid', $e->getMessage());
            }
        }
    }
}
