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
}
