<?php

namespace Tests\Feature\Cooperative;

use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Jobs\GeneratePosReportPdf;
use App\Models\BackgroundJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Plan07BackgroundJobExportTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_enqueue_creates_pending_job_and_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->cashier();
        $payload = [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ];

        $response = $this->actingAs($user)
            ->postJson('/cooperative/pos/reports/export.pdf', $payload);

        $response->assertStatus(202)
            ->assertJsonStructure(['job_id', 'status', 'progress']);

        $this->assertSame(BackgroundJobStatus::Pending->value, $response->json('status'));
        $this->assertSame(0, $response->json('progress'));
        $this->assertNotNull($response->json('job_id'));

        $bgJob = BackgroundJob::query()->first();
        $this->assertNotNull($bgJob);
        $this->assertSame($user->id, $bgJob->user_id);
        $this->assertSame('pos.report.pdf', $bgJob->type);
        $this->assertSame('2026-06-01', $bgJob->metadata['from']);
        $this->assertSame('2026-06-30', $bgJob->metadata['to']);

        Queue::assertPushed(GeneratePosReportPdf::class, fn ($queued) => $queued->backgroundJobId === $bgJob->id);
    }

    public function test_enqueue_response_includes_job_id_that_resolves_to_status(): void
    {
        Queue::fake();

        $user = $this->cashier();

        $enqueueResponse = $this->actingAs($user)
            ->postJson('/cooperative/pos/reports/export.pdf', [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
            ])
            ->assertStatus(202);

        $jobId = $enqueueResponse->json('job_id');
        $this->assertNotEmpty($jobId);

        $statusResponse = $this->actingAs($user)
            ->getJson("/cooperative/pos/reports/export.pdf/jobs/{$jobId}/status")
            ->assertOk();

        $this->assertSame($jobId, $statusResponse->json('job_id'));
        $this->assertSame(BackgroundJobStatus::Pending->value, $statusResponse->json('status'));
        $this->assertSame(0, $statusResponse->json('progress'));
        $this->assertNull($statusResponse->json('download_url'));
    }

    public function test_enqueue_validates_date_range(): void
    {
        $user = $this->cashier();

        $this->actingAs($user)
            ->postJson('/cooperative/pos/reports/export.pdf', [
                'from' => '2026-06-30',
                'to' => '2026-06-01',
            ])
            ->assertStatus(422);
    }

    public function test_enqueue_requires_view_pos_reports_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('access_cooperative_pos');

        $this->actingAs($user)
            ->postJson('/cooperative/pos/reports/export.pdf', [])
            ->assertStatus(403);
    }

    public function test_status_returns_pending_for_just_enqueued_job(): void
    {
        Queue::fake();

        $user = $this->cashier();
        $job = BackgroundJob::factory()->create([
            'user_id' => $user->id,
            'type' => 'pos.report.pdf',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/status");

        $response->assertOk()
            ->assertJson([
                'job_id' => $job->uuid,
                'status' => BackgroundJobStatus::Pending->value,
                'progress' => 0,
                'download_url' => null,
            ]);
    }

    public function test_status_returns_completed_with_download_url(): void
    {
        $user = $this->cashier();
        $job = BackgroundJob::factory()
            ->completed('reports/'.$user->id.'/laporan.pdf')
            ->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/status");

        $response->assertOk()
            ->assertJson([
                'job_id' => $job->uuid,
                'status' => BackgroundJobStatus::Completed->value,
                'progress' => 100,
            ]);

        $this->assertNotNull($response->json('download_url'));
    }

    public function test_status_returns_failed_with_error_message(): void
    {
        $user = $this->cashier();
        $job = BackgroundJob::factory()
            ->failed('DomPDF error: missing image')
            ->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/status");

        $response->assertOk()
            ->assertJson([
                'status' => BackgroundJobStatus::Failed->value,
                'error_message' => 'DomPDF error: missing image',
                'download_url' => null,
            ]);
    }

    public function test_status_returns_404_for_other_users_job(): void
    {
        $owner = $this->cashier();
        $intruder = $this->cashier('intruder');
        $job = BackgroundJob::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->getJson("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/status")
            ->assertNotFound();
    }

    public function test_download_returns_404_for_other_users_job(): void
    {
        Storage::disk('local')->put('reports/intruder.pdf', '%PDF-1.4 fake');

        $owner = $this->cashier();
        $intruder = $this->cashier('intruder');
        $job = BackgroundJob::factory()
            ->completed('reports/intruder.pdf')
            ->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/download")
            ->assertNotFound();
    }

    public function test_download_rejects_non_completed_jobs(): void
    {
        $user = $this->cashier();
        $job = BackgroundJob::factory()->create([
            'user_id' => $user->id,
            'status' => BackgroundJobStatus::Processing,
        ]);

        $this->actingAs($user)
            ->get("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/download")
            ->assertStatus(409);
    }

    public function test_download_streams_file_when_completed_and_owned(): void
    {
        $user = $this->cashier();
        $relativePath = "reports/{$user->id}/laporan.pdf";
        $fakePdf = "%PDF-1.4\n%fake-content-for-test\n%%EOF";
        Storage::disk('local')->put($relativePath, $fakePdf);

        $job = BackgroundJob::factory()
            ->completed($relativePath)
            ->create([
                'user_id' => $user->id,
                'original_name' => 'laporan-pos-juni-2026.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => strlen($fakePdf),
            ]);

        $response = $this->actingAs($user)
            ->get("/cooperative/pos/reports/export.pdf/jobs/{$job->uuid}/download");

        $response->assertOk();
        $this->assertSame(
            'attachment; filename=laporan-pos-juni-2026.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_job_marks_processing_then_completed_when_run_synchronously(): void
    {
        $user = $this->cashier();

        $job = BackgroundJob::factory()->create([
            'user_id' => $user->id,
            'metadata' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
                'filters' => [],
            ],
        ]);

        (new GeneratePosReportPdf($job->id))->handle(app(\App\Services\Cooperative\PosSalesReportService::class));

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);
        $this->assertSame(100, $job->progress);
        $this->assertNotNull($job->file_path);
        $this->assertSame('application/pdf', $job->mime_type);
        Storage::disk('local')->assertExists($job->file_path);
    }

    public function test_job_marks_failed_when_exception_thrown(): void
    {
        $user = $this->cashier();
        $job = BackgroundJob::factory()->create(['user_id' => $user->id]);

        $service = new class extends \App\Services\Cooperative\PosSalesReportService
        {
            public function __construct() {}

            public function summaryForPeriod(string $from, string $to, array $filters = []): array
            {
                throw new \RuntimeException('forced failure for test');
            }
        };

        try {
            (new GeneratePosReportPdf($job->id))->handle($service);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException) {
            // expected
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertSame('forced failure for test', $job->error_message);
    }

    public function test_running_job_twice_is_idempotent_and_does_not_reprocess(): void
    {
        $user = $this->cashier();
        $job = BackgroundJob::factory()->create([
            'user_id' => $user->id,
            'metadata' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
                'filters' => [],
            ],
        ]);

        $first = new GeneratePosReportPdf($job->id);
        $first->handle(app(\App\Services\Cooperative\PosSalesReportService::class));
        $firstFilePath = $job->fresh()->file_path;

        $second = new GeneratePosReportPdf($job->id);
        $second->handle(app(\App\Services\Cooperative\PosSalesReportService::class));

        $job->refresh();
        $this->assertSame($firstFilePath, $job->file_path);
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);
    }

    private function cashier(?string $name = null): User
    {
        $user = User::factory()->create(['name' => $name ?? 'cashier']);
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports']);

        return $user;
    }
}
