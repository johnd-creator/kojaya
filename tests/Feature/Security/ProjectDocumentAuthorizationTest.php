<?php

namespace Tests\Feature\Security;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected User $unauthorizedUserA;

    protected User $nullOrgUser;

    protected Project $projectA;

    protected Project $projectB;

    protected ProjectDocument $documentA;

    protected ProjectDocument $documentB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('project_documents');

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'view_project_all', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_project_unit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_project', 'guard_name' => 'web']);

        // Set up Organization A and actors
        $this->orgA = Organization::factory()->create();
        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA->givePermissionTo(['view_project_unit', 'manage_project']);

        $this->unauthorizedUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        // unauthorizedUserA has NO project permissions

        // Set up Organization B and actors
        $this->orgB = Organization::factory()->create();
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->userB->givePermissionTo(['view_project_unit', 'manage_project']);

        // NULL-org actor
        $this->nullOrgUser = User::factory()->create(['organization_id' => null]);
        $this->nullOrgUser->givePermissionTo(['view_project_unit', 'manage_project']);

        // Set up Projects
        $this->projectA = Project::factory()->create(['organization_id' => $this->orgA->id]);
        $this->projectB = Project::factory()->create(['organization_id' => $this->orgB->id]);

        // Set up Documents with files on private storage
        $this->documentA = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/'.Str::uuid().'.pdf',
            'name' => 'Document A',
        ]);
        Storage::disk('project_documents')->put($this->documentA->file_path, 'Content A');

        $this->documentB = ProjectDocument::factory()->create([
            'project_id' => $this->projectB->id,
            'file_path' => 'project-documents/'.Str::uuid().'.pdf',
            'name' => 'Document B',
        ]);
        Storage::disk('project_documents')->put($this->documentB->file_path, 'Content B');
    }

    /** 01 authorized User A can list Project A documents */
    public function test_01_authorized_user_a_can_list_project_a_documents(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('documents.index', $this->projectA));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ProjectDocuments/Index')
            ->has('documents', 1)
            ->where('documents.0.id', $this->documentA->id)
            ->where('documents.0.name', 'Document A')
        );
    }

    /** 02 User A cannot list Project B documents */
    public function test_02_user_a_cannot_list_project_b_documents(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('documents.index', $this->projectB));

        $response->assertForbidden();
    }

    /** 03 same-org actor without required project permission cannot list documents */
    public function test_03_same_org_actor_without_required_project_permission_cannot_list_documents(): void
    {
        $response = $this->actingAs($this->unauthorizedUserA)
            ->get(route('documents.index', $this->projectA));

        $response->assertForbidden();
    }

    /** 04 NULL-org actor fails closed */
    public function test_04_null_org_actor_fails_closed(): void
    {
        $response = $this->actingAs($this->nullOrgUser)
            ->get(route('documents.index', $this->projectA));

        $response->assertForbidden();
    }

    /** 05 User A cannot upload into Project B */
    public function test_05_user_a_cannot_upload_into_project_b(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectB), [
                'name' => 'Unauthorized Upload',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    /** 06 unauthorized upload creates zero DB rows */
    public function test_06_unauthorized_upload_creates_zero_db_rows(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectB), [
                'name' => 'Unauthorized Upload',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $this->assertDatabaseMissing('project_documents', [
            'name' => 'Unauthorized Upload',
        ]);
    }

    /** 07 unauthorized upload creates zero private/public files */
    public function test_07_unauthorized_upload_creates_zero_private_public_files(): void
    {
        $initialPrivateFiles = Storage::disk('project_documents')->allFiles();
        $initialPublicFiles = Storage::disk('public')->allFiles();

        $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/pdf');

        $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectB), [
                'name' => 'Malicious File',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $this->assertSame($initialPrivateFiles, Storage::disk('project_documents')->allFiles());
        $this->assertSame($initialPublicFiles, Storage::disk('public')->allFiles());
    }

    /** 08 authorized upload stores file on project_documents private disk */
    public function test_08_authorized_upload_stores_file_on_project_documents_private_disk(): void
    {
        $file = UploadedFile::fake()->create('safety_permit.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Safety Permit',
                'type' => 'PERMIT',
                'file' => $file,
            ]);

        $response->assertRedirect();

        $document = ProjectDocument::where('project_id', $this->projectA->id)
            ->where('name', 'Safety Permit')
            ->first();

        $this->assertNotNull($document);
        $this->assertNotNull($document->file_path);
        Storage::disk('project_documents')->assertExists($document->file_path);
    }

    /** 09 authorized upload does NOT store file on public disk */
    public function test_09_authorized_upload_does_not_store_file_on_public_disk(): void
    {
        $file = UploadedFile::fake()->create('drawing.png', 200, 'image/png');

        $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Architectural Drawing',
                'type' => 'DRAWING',
                'file' => $file,
            ]);

        $document = ProjectDocument::where('project_id', $this->projectA->id)
            ->where('name', 'Architectural Drawing')
            ->first();

        $this->assertNotNull($document);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    /** 10 index payload does not expose raw private file_path */
    public function test_10_index_payload_does_not_expose_raw_private_file_path(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('documents.index', $this->projectA));

        $response->assertOk();

        // Check Inertia documents prop
        $page = $response->viewData('page');
        $this->assertNotNull($page);
        $documents = $page['props']['documents'];
        $this->assertNotEmpty($documents);

        foreach ($documents as $doc) {
            $this->assertArrayNotHasKey('file_path', $doc, 'Payload must not contain raw file_path.');
            $this->assertArrayHasKey('download_url', $doc, 'Payload must contain download_url.');
            $this->assertStringContainsString('/download', $doc['download_url']);
        }

        // Verify the entire response content does not leak internal storage paths
        $content = $response->getContent();
        $this->assertStringNotContainsString('storage/app/private', $content);
        $this->assertStringNotContainsString($this->documentA->file_path, $content);
    }

    /** 11 authorized User A can download Document A */
    public function test_11_authorized_user_a_can_download_document_a(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('projects.documents.download', [$this->projectA, $this->documentA]));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /** 12 User A cannot download Document B */
    public function test_12_user_a_cannot_download_document_b(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('projects.documents.download', [$this->projectB, $this->documentB]));

        $response->assertForbidden();
    }

    /** 13 Project A + Document B download mismatch fails closed */
    public function test_13_project_a_and_document_b_download_mismatch_fails_closed(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('projects.documents.download', [$this->projectA, $this->documentB]));

        $response->assertNotFound();
    }

    /** 14 download failure leaks no foreign metadata/path */
    public function test_14_download_failure_leaks_no_foreign_metadata_path(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('projects.documents.download', [$this->projectA, $this->documentB]));

        $response->assertNotFound();
        $content = $response->getContent();
        $this->assertStringNotContainsString('Document B', $content);
        $this->assertStringNotContainsString($this->documentB->file_path, $content);
    }

    /** 15 authorized delete removes own Document A and its private file */
    public function test_15_authorized_delete_removes_own_document_a_and_its_private_file(): void
    {
        $filePath = $this->documentA->file_path;
        Storage::disk('project_documents')->assertExists($filePath);

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentA]));

        $response->assertRedirect();
        $this->assertModelMissing($this->documentA);
        Storage::disk('project_documents')->assertMissing($filePath);
    }

    /** 16 User A cannot delete Document B */
    public function test_16_user_a_cannot_delete_document_b(): void
    {
        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectB, $this->documentB]));

        $response->assertForbidden();
        $this->assertModelExists($this->documentB);
    }

    /** 17 Project A + Document B delete mismatch returns safe denial/404 */
    public function test_17_project_a_and_document_b_delete_mismatch_returns_safe_denial_404(): void
    {
        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentB]));

        $response->assertNotFound();
        $this->assertModelExists($this->documentB);
    }

    /** 18 rejected foreign delete leaves DB row unchanged */
    public function test_18_rejected_foreign_delete_leaves_db_row_unchanged(): void
    {
        $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectB, $this->documentB]));

        $this->assertDatabaseHas('project_documents', [
            'id' => $this->documentB->id,
            'project_id' => $this->projectB->id,
            'name' => 'Document B',
        ]);
    }

    /** 19 rejected foreign delete leaves file unchanged */
    public function test_19_rejected_foreign_delete_leaves_file_unchanged(): void
    {
        $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectB, $this->documentB]));

        Storage::disk('project_documents')->assertExists($this->documentB->file_path);
    }

    /** 20 nonexistent Project vs foreign Project does not produce material unauthorized data leakage */
    public function test_20_nonexistent_project_vs_foreign_project_does_not_produce_material_unauthorized_data_leakage(): void
    {
        $fakeUuid = (string) Str::uuid();

        // Non-existent project
        $nonExistentResponse = $this->actingAs($this->userA)
            ->get("/projects/{$fakeUuid}/documents");
        $nonExistentResponse->assertNotFound();

        // Foreign project
        $foreignResponse = $this->actingAs($this->userA)
            ->get(route('documents.index', $this->projectB));
        $foreignResponse->assertForbidden();

        // Neither response leaks document names or paths
        $this->assertStringNotContainsString('Document B', $nonExistentResponse->getContent());
        $this->assertStringNotContainsString('Document B', $foreignResponse->getContent());
    }

    /** 21 ownership fields in upload payload cannot forge project/organization authority */
    public function test_21_ownership_fields_in_upload_payload_cannot_forge_project_organization_authority(): void
    {
        $file = UploadedFile::fake()->create('forged.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Forged Document',
                'type' => 'SIKA',
                'file' => $file,
                'project_id' => $this->projectB->id,
                'organization_id' => $this->orgB->id,
            ]);

        // Prohibited fields must cause validation failure
        $response->assertSessionHasErrors(['project_id', 'organization_id']);

        // Ensure no document was created with forged ownership
        $this->assertDatabaseMissing('project_documents', [
            'name' => 'Forged Document',
        ]);
    }

    /** 22 legacy public-file migration moves a valid referenced file to private storage */
    public function test_22_legacy_public_file_migration_moves_a_valid_referenced_file_to_private_storage(): void
    {
        $legacyPath = 'project-documents/legacy_test_doc.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Legacy Document',
        ]);

        Storage::disk('public')->put($legacyPath, 'Legacy Secret Content');
        Storage::disk('project_documents')->assertMissing($legacyPath);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(0, $exitCode);
        Storage::disk('project_documents')->assertExists($legacyPath);
        Storage::disk('public')->assertMissing($legacyPath);
        $this->assertSame('Legacy Secret Content', Storage::disk('project_documents')->get($legacyPath));
    }

    /** 23 migration verifies destination before removing public source */
    public function test_23_migration_verifies_destination_before_removing_public_source(): void
    {
        $legacyPath = 'project-documents/legacy_dry_run.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Dry Run Doc',
        ]);

        Storage::disk('public')->put($legacyPath, 'Original Source');

        // Dry-run must NOT remove public file or create private file
        $exitCode = Artisan::call('project-documents:migrate-private', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        Storage::disk('public')->assertExists($legacyPath);
        Storage::disk('project_documents')->assertMissing($legacyPath);
    }

    /** 24 migration can be re-run safely/idempotently */
    public function test_24_migration_can_be_re_run_safely_idempotently(): void
    {
        $legacyPath = 'project-documents/idempotent_doc.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Idempotent Doc',
        ]);

        Storage::disk('public')->put($legacyPath, 'Important Data');

        // First run
        $code1 = Artisan::call('project-documents:migrate-private');
        $this->assertSame(0, $code1);
        Storage::disk('project_documents')->assertExists($legacyPath);
        Storage::disk('public')->assertMissing($legacyPath);

        // Second run must succeed and report already_private
        $code2 = Artisan::call('project-documents:migrate-private');
        $this->assertSame(0, $code2);
        Storage::disk('project_documents')->assertExists($legacyPath);
        Storage::disk('public')->assertMissing($legacyPath);
    }

    /** 25 failed migration does not destroy the only valid source file */
    public function test_25_failed_migration_does_not_destroy_the_only_valid_source_file(): void
    {
        $mismatchPath = 'project-documents/mismatch_integrity.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $mismatchPath,
            'name' => 'Mismatch Doc',
        ]);

        // Put source on public disk
        Storage::disk('public')->put($mismatchPath, 'Original Public Source');

        // Put corrupted/different content on private disk
        Storage::disk('project_documents')->put($mismatchPath, 'Corrupted Private File');

        $exitCode = Artisan::call('project-documents:migrate-private');

        // Migration detects integrity mismatch between public and private, flags failed, and does NOT delete public source
        $this->assertNotSame(0, $exitCode);
        Storage::disk('public')->assertExists($mismatchPath);
        $this->assertSame('Original Public Source', Storage::disk('public')->get($mismatchPath));
    }
}
