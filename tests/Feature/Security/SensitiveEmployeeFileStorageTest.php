<?php

namespace Tests\Feature\Security;

use App\Enums\CertificateType;
use App\Enums\McuResult;
use App\Enums\TokenApp;
use App\Http\Resources\EmployeeCertificateResource;
use App\Http\Resources\MedicalCheckupResource;
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

        Storage::disk(EmployeeDocumentStorage::DISK)->put($certB->document_path, 'secret B cert');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($mcuB->document_path, 'secret B mcu');

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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($cert2->document_path, 'cert2 content');

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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($mcu2->document_path, 'mcu2 content');

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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($jpgPath, 'fake-jpg-bytes');
        $cert = $this->createCertificate($employee->id, [
            'document_path' => $jpgPath,
        ]);

        // 2. PNG MCU
        $pngPath = "mcu/{$employee->id}/scan.png";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($pngPath, 'fake-png-bytes');
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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, 'old content');

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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($oldPath, 'preserved content');

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
        $this->assertSame('preserved content', Storage::disk(EmployeeDocumentStorage::DISK)->get($oldPath));

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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, 'to delete');
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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certPath, 'intact B cert');
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
        $this->assertSame('intact B cert', Storage::disk(EmployeeDocumentStorage::DISK)->get($certPath));
    }

    public function test_migration_dry_run_performs_no_copy_or_delete(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/legacy_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, 'legacy content');

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
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, 'legacy binary bytes');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('legacy binary bytes', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
        // Source is preserved when --cleanup is not requested
        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->exists($path));
    }

    public function test_migration_is_idempotent(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/legacy_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, 'sample bytes');

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
        $this->assertSame('sample bytes', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_migration_conflict_fails_closed_and_does_not_overwrite_or_delete_either_copy(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/conflicting.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, 'source content A');
        Storage::disk(EmployeeDocumentStorage::DISK)->put($path, 'different destination content B');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('source content A', Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->get($path));
        $this->assertSame('different destination content B', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
    }

    public function test_cleanup_deletes_only_a_verified_public_source(): void
    {
        $employee = Employee::factory()->create();
        $path = "certificates/{$employee->id}/cleanup_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::LEGACY_DISK)->put($path, 'cleanup content');

        $this->createCertificate($employee->id, [
            'document_path' => $path,
        ]);

        $this->artisan('security:migrate-employee-documents-private', [
            '--execute' => true,
            '--cleanup' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertTrue(Storage::disk(EmployeeDocumentStorage::DISK)->exists($path));
        $this->assertSame('cleanup content', Storage::disk(EmployeeDocumentStorage::DISK)->get($path));
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
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certAPath, 'png image bytes');
        $certA = $this->createCertificate($employeeA->id, [
            'document_path' => $certAPath,
        ]);

        $certBPath = "certificates/{$employeeB->id}/foreign_cert.pdf";
        Storage::disk(EmployeeDocumentStorage::DISK)->put($certBPath, 'pdf bytes');
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
        $this->assertSame('png image bytes', $responseA->streamedContent());

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
}
