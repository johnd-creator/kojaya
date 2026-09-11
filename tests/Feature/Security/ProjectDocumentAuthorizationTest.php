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

    protected User $readOnlyUserA;

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

        $this->readOnlyUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->readOnlyUserA->givePermissionTo('view_project_unit');
        // readOnlyUserA has view_project_unit, but NOT manage_project

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

    /** 26 migration does not report public_removed when public delete fails */
    public function test_26_migration_does_not_report_public_removed_when_public_delete_fails(): void
    {
        $legacyPath = 'project-documents/doc26.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Doc 26',
        ]);
        Storage::disk('public')->put($legacyPath, 'Secret 26');

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($legacyPath)->andReturn(false);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*public_removed\s*\|\s*0\s*\|/', $output);
        $this->assertStringContainsString('remaining_referenced_public = 1', $output);
    }

    /** 27 migration exits FAILURE when public delete returns false */
    public function test_27_migration_exits_failure_when_public_delete_returns_false(): void
    {
        $legacyPath = 'project-documents/doc27.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Doc 27',
        ]);
        Storage::disk('public')->put($legacyPath, 'Secret 27');

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($legacyPath)->andReturn(false);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
    }

    /** 28 migration exits FAILURE when public file remains after delete attempt */
    public function test_28_migration_exits_failure_when_public_file_remains_after_delete_attempt(): void
    {
        $legacyPath = 'project-documents/doc28.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Doc 28',
        ]);
        Storage::disk('public')->put($legacyPath, 'Secret 28');

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($legacyPath)->andReturn(true);
        $mockPublic->shouldReceive('exists')->with($legacyPath)->andReturn(true);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*public_removed\s*\|\s*0\s*\|/', $output);
        $this->assertStringContainsString('remaining_referenced_public = 1', $output);
    }

    /** 29 both-present identical files remain classified incomplete if public delete fails */
    public function test_29_both_present_identical_files_remain_classified_incomplete_if_public_delete_fails(): void
    {
        $legacyPath = 'project-documents/doc29.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Doc 29',
        ]);

        $content = 'Identical Content 29';
        Storage::disk('public')->put($legacyPath, $content);
        Storage::disk('project_documents')->put($legacyPath, $content);

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($legacyPath)->andReturn(false);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*failed\s*\|\s*1\s*\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s*public_removed\s*\|\s*0\s*\|/', $output);
        $this->assertStringContainsString('remaining_referenced_public = 1', $output);
    }

    /** 30 verified private copy remains intact when public cleanup fails */
    public function test_30_verified_private_copy_remains_intact_when_public_cleanup_fails(): void
    {
        $legacyPath = 'project-documents/doc30.pdf';
        ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Doc 30',
        ]);

        $content = 'Valuable Content 30';
        Storage::disk('public')->put($legacyPath, $content);

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($legacyPath)->andReturn(false);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        Storage::disk('project_documents')->assertExists($legacyPath);
        $this->assertSame($content, Storage::disk('project_documents')->get($legacyPath));
        Storage::disk('public')->assertExists($legacyPath);
    }

    /** 31 destroy does not delete DB row if private file deletion fails */
    public function test_31_destroy_does_not_delete_db_row_if_private_file_deletion_fails(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc31.pdf',
            'name' => 'Doc 31',
        ]);
        Storage::disk('project_documents')->put($doc->file_path, 'Content 31');

        $mockPrivate = \Mockery::mock(Storage::disk('project_documents'))->makePartial();
        $mockPrivate->shouldReceive('delete')->with($doc->file_path)->andReturn(false);
        Storage::set('project_documents', $mockPrivate);

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $response->assertStatus(500);
        $this->assertDatabaseHas('project_documents', ['id' => $doc->id]);
    }

    /** 32 destroy does not delete DB row if legacy public file deletion fails */
    public function test_32_destroy_does_not_delete_db_row_if_legacy_public_file_deletion_fails(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc32.pdf',
            'name' => 'Doc 32',
        ]);
        Storage::disk('public')->put($doc->file_path, 'Legacy Content 32');

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($doc->file_path)->andReturn(false);
        Storage::set('public', $mockPublic);

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $response->assertStatus(500);
        $this->assertDatabaseHas('project_documents', ['id' => $doc->id]);
    }

    /** 33 failed destroy does not return successful redirect/message */
    public function test_33_failed_destroy_does_not_return_successful_redirect_message(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc33.pdf',
            'name' => 'Doc 33',
        ]);
        Storage::disk('project_documents')->put($doc->file_path, 'Content 33');

        $mockPrivate = \Mockery::mock(Storage::disk('project_documents'))->makePartial();
        $mockPrivate->shouldReceive('delete')->with($doc->file_path)->andReturn(false);
        Storage::set('project_documents', $mockPrivate);

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $this->assertNotSame(302, $response->getStatusCode());
        $response->assertStatus(500);
        $response->assertSessionMissing('success');
    }

    /** 34 successful destroy proves private file absent before DB row removal */
    public function test_34_successful_destroy_proves_private_file_absent_before_db_row_removal(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc34.pdf',
            'name' => 'Doc 34',
        ]);
        Storage::disk('project_documents')->put($doc->file_path, 'Content 34');

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document deleted successfully.');
        Storage::disk('project_documents')->assertMissing($doc->file_path);
        $this->assertDatabaseMissing('project_documents', ['id' => $doc->id]);
    }

    /** 35 successful legacy destroy proves public file absent before DB row removal */
    public function test_35_successful_legacy_destroy_proves_public_file_absent_before_db_row_removal(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc35.pdf',
            'name' => 'Doc 35',
        ]);
        Storage::disk('public')->put($doc->file_path, 'Legacy Content 35');

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document deleted successfully.');
        Storage::disk('public')->assertMissing($doc->file_path);
        $this->assertDatabaseMissing('project_documents', ['id' => $doc->id]);
    }

    /** 36 migration final verification reports remaining_referenced_public = 0 on clean success */
    public function test_36_migration_final_verification_reports_remaining_referenced_public_zero_on_clean_success(): void
    {
        $doc1 = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc36_1.pdf',
            'name' => 'Doc 36 1',
        ]);
        $doc2 = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc36_2.pdf',
            'name' => 'Doc 36 2',
        ]);

        Storage::disk('public')->put($doc1->file_path, 'Content 36 1');
        Storage::disk('public')->put($doc2->file_path, 'Content 36 2');

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('remaining_referenced_public = 0', $output);
        Storage::disk('public')->assertMissing($doc1->file_path);
        Storage::disk('public')->assertMissing($doc2->file_path);
        Storage::disk('project_documents')->assertExists($doc1->file_path);
        Storage::disk('project_documents')->assertExists($doc2->file_path);
    }

    /** 37 migration final verification fails when one referenced public file remains */
    public function test_37_migration_final_verification_fails_when_one_referenced_public_file_remains(): void
    {
        $doc1 = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc37_1.pdf',
            'name' => 'Doc 37 1',
        ]);
        $doc2 = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc37_2.pdf',
            'name' => 'Doc 37 2',
        ]);

        Storage::disk('public')->put($doc1->file_path, 'Content 37 1');
        Storage::disk('public')->put($doc2->file_path, 'Content 37 2');

        // Delete succeeds for doc1, but fails for doc2
        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('delete')->with($doc2->file_path)->andReturn(false);
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('remaining_referenced_public = 1', $output);
    }

    /** 38 dry-run never represents simulated cleanup as physically completed */
    public function test_38_dry_run_never_represents_simulated_cleanup_as_physically_completed(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc38.pdf',
            'name' => 'Doc 38',
        ]);
        Storage::disk('public')->put($doc->file_path, 'Content 38');

        $exitCode = Artisan::call('project-documents:migrate-private', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();

        // Must report simulated metrics
        $this->assertMatchesRegularExpression('/\|\s*would_migrate\s*\|\s*1\s*\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s*would_remove_public\s*\|\s*1\s*\|/', $output);

        // Must NOT represent simulated cleanup as physically completed
        $this->assertMatchesRegularExpression('/\|\s*migrated\s*\|\s*0\s*\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s*public_removed\s*\|\s*0\s*\|/', $output);

        // Physical state must remain untouched
        Storage::disk('public')->assertExists($doc->file_path);
        Storage::disk('project_documents')->assertMissing($doc->file_path);
    }

    /** 39 read-only same-org user with view_project_unit CAN list Project A documents */
    public function test_39_read_only_same_org_user_with_view_project_unit_can_list_project_a_documents(): void
    {
        $response = $this->actingAs($this->readOnlyUserA)
            ->get(route('documents.index', $this->projectA));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ProjectDocuments/Index')
            ->has('documents', 1)
            ->where('documents.0.id', $this->documentA->id)
        );
    }

    /** 40 read-only same-org user CAN download authorized Project A document */
    public function test_40_read_only_same_org_user_can_download_authorized_project_a_document(): void
    {
        $response = $this->actingAs($this->readOnlyUserA)
            ->get(route('projects.documents.download', [$this->projectA, $this->documentA]));

        $response->assertOk();
        $this->assertSame('Content A', $response->streamedContent());
    }

    /** 41 read-only same-org user CANNOT upload Project A document */
    public function test_41_read_only_same_org_user_cannot_upload_project_a_document(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->readOnlyUserA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'ReadOnly Upload Attempt',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    /** 42 rejected read-only upload creates zero DB row */
    public function test_42_rejected_read_only_upload_creates_zero_db_row(): void
    {
        $initialCount = ProjectDocument::count();
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($this->readOnlyUserA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Should Not Exist',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $this->assertSame($initialCount, ProjectDocument::count());
        $this->assertDatabaseMissing('project_documents', [
            'name' => 'Should Not Exist',
        ]);
    }

    /** 43 rejected read-only upload creates zero storage files */
    public function test_43_rejected_read_only_upload_creates_zero_storage_files(): void
    {
        $initialFiles = Storage::disk('project_documents')->allFiles();
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $this->actingAs($this->readOnlyUserA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Should Not Exist On Disk',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $this->assertSame($initialFiles, Storage::disk('project_documents')->allFiles());
    }

    /** 44 read-only same-org user CANNOT delete Project A document */
    public function test_44_read_only_same_org_user_cannot_delete_project_a_document(): void
    {
        $response = $this->actingAs($this->readOnlyUserA)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentA]));

        $response->assertForbidden();
    }

    /** 45 rejected read-only delete preserves DB row */
    public function test_45_rejected_read_only_delete_preserves_db_row(): void
    {
        $this->actingAs($this->readOnlyUserA)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentA]));

        $this->assertDatabaseHas('project_documents', [
            'id' => $this->documentA->id,
        ]);
    }

    /** 46 rejected read-only delete preserves private file */
    public function test_46_rejected_read_only_delete_preserves_private_file(): void
    {
        $this->actingAs($this->readOnlyUserA)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentA]));

        Storage::disk('project_documents')->assertExists($this->documentA->file_path);
    }

    /** 47 rejected read-only delete preserves legacy public file where present */
    public function test_47_rejected_read_only_delete_preserves_legacy_public_file_where_present(): void
    {
        $legacyPath = 'project-documents/legacy_ro.pdf';
        $legacyDoc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => $legacyPath,
            'name' => 'Legacy RO Doc',
        ]);
        Storage::disk('public')->put($legacyPath, 'Public Secret');

        $response = $this->actingAs($this->readOnlyUserA)
            ->delete(route('documents.destroy', [$this->projectA, $legacyDoc]));

        $response->assertForbidden();
        Storage::disk('public')->assertExists($legacyPath);
        $this->assertDatabaseHas('project_documents', ['id' => $legacyDoc->id]);
    }

    /** 48 manage_project same-org actor CAN upload */
    public function test_48_manage_project_same_org_actor_can_upload(): void
    {
        $file = UploadedFile::fake()->create('manager_doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Manager Upload',
                'type' => 'SIKA',
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document uploaded successfully.');
        $this->assertDatabaseHas('project_documents', [
            'project_id' => $this->projectA->id,
            'name' => 'Manager Upload',
        ]);
    }

    /** 49 manage_project same-org actor CAN delete */
    public function test_49_manage_project_same_org_actor_can_delete(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/manager_del.pdf',
            'name' => 'To Delete',
        ]);
        Storage::disk('project_documents')->put($doc->file_path, 'Content');

        $response = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectA, $doc]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document deleted successfully.');
        $this->assertDatabaseMissing('project_documents', ['id' => $doc->id]);
        Storage::disk('project_documents')->assertMissing($doc->file_path);
    }

    /** 50 manage_project actor from Org A CANNOT mutate Project B */
    public function test_50_manage_project_actor_from_org_a_cannot_mutate_project_b(): void
    {
        $file = UploadedFile::fake()->create('cross_org.pdf', 100, 'application/pdf');

        // Store attempt on Project B
        $responseUpload = $this->actingAs($this->userA)
            ->post(route('documents.store', $this->projectB), [
                'name' => 'Cross Org Mutation',
                'type' => 'SIKA',
                'file' => $file,
            ]);
        $responseUpload->assertForbidden();

        // Destroy attempt on Document B
        $responseDelete = $this->actingAs($this->userA)
            ->delete(route('documents.destroy', [$this->projectB, $this->documentB]));
        $responseDelete->assertForbidden();
    }

    /** 51 NULL-org actor with manage_project still fails closed */
    public function test_51_null_org_actor_with_manage_project_still_fails_closed(): void
    {
        $file = UploadedFile::fake()->create('null_org.pdf', 100, 'application/pdf');

        // Upload attempt
        $responseUpload = $this->actingAs($this->nullOrgUser)
            ->post(route('documents.store', $this->projectA), [
                'name' => 'Null Org Upload',
                'type' => 'SIKA',
                'file' => $file,
            ]);
        $responseUpload->assertForbidden();

        // Destroy attempt
        $responseDelete = $this->actingAs($this->nullOrgUser)
            ->delete(route('documents.destroy', [$this->projectA, $this->documentA]));
        $responseDelete->assertForbidden();
    }

    /** 54 public exists() throws -> migration FAILURE */
    public function test_54_public_exists_throws_migration_failure(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc54.pdf',
            'name' => 'Doc 54',
        ]);

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('exists')->with($doc->file_path)->andThrow(new \RuntimeException('Disk IO error'));
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
    }

    /** 55 public exists() throws -> not classified missing_source */
    public function test_55_public_exists_throws_not_classified_missing_source(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc55.pdf',
            'name' => 'Doc 55',
        ]);

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('exists')->with($doc->file_path)->andThrow(new \RuntimeException('Disk IO error'));
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*missing_source\s*\|\s*0\s*\|/', $output);
        $this->assertMatchesRegularExpression('/\|\s*failed\s*\|\s*1\s*\|/', $output);
    }

    /** 56 dry-run public exists() throws -> FAILURE / UNKNOWN reported */
    public function test_56_dry_run_public_exists_throws_failure_unknown_reported(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc56.pdf',
            'name' => 'Doc 56',
        ]);

        $mockPublic = \Mockery::mock(Storage::disk('public'))->makePartial();
        $mockPublic->shouldReceive('exists')->with($doc->file_path)->andThrow(new \RuntimeException('Disk IO error'));
        Storage::set('public', $mockPublic);

        $exitCode = Artisan::call('project-documents:migrate-private', ['--dry-run' => true]);

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*failed\s*\|\s*1\s*\|/', $output);
    }

    /** 57 private exists() throws -> fail closed rather than treating as confirmed absent */
    public function test_57_private_exists_throws_fail_closed_rather_than_treating_as_confirmed_absent(): void
    {
        $doc = ProjectDocument::factory()->create([
            'project_id' => $this->projectA->id,
            'file_path' => 'project-documents/doc57.pdf',
            'name' => 'Doc 57',
        ]);
        Storage::disk('public')->put($doc->file_path, 'Source Content');

        $mockPrivate = \Mockery::mock(Storage::disk('project_documents'))->makePartial();
        $mockPrivate->shouldReceive('exists')->with($doc->file_path)->andThrow(new \RuntimeException('Private disk error'));
        Storage::set('project_documents', $mockPrivate);

        $exitCode = Artisan::call('project-documents:migrate-private');

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/\|\s*failed\s*\|\s*1\s*\|/', $output);
        // Public file must not be removed
        Storage::disk('public')->assertExists($doc->file_path);
    }
}
