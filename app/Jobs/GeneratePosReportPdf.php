<?php

namespace App\Jobs;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Models\BackgroundJob;
use App\Services\AuditLogService;
use App\Services\Cooperative\PosSalesReportService;
use App\Support\AuditContext;
use App\Support\ReportAuthorizationScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GeneratePosReportPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public readonly int $backgroundJobId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("pos-report-pdf-{$this->backgroundJobId}"))
                ->expireAfter(240)
                ->dontRelease(),
        ];
    }

    public function handle(
        PosSalesReportService $service,
        ?AuditLogService $audit = null,
        ?OrganizationScopedQueryService $scopeService = null,
    ): void {
        $audit ??= app(AuditLogService::class);
        $scopeService ??= app(OrganizationScopedQueryService::class);
        $job = $this->claimJob();

        if (! $job) {
            return;
        }

        $relativePath = null;

        try {
            $job->updateProgress(10);

            if ($job->type !== 'pos.report.pdf') {
                throw new AuthorizationException("Invalid job type [{$job->type}] for POS report PDF generation.");
            }

            $user = \App\Models\User::query()->find($job->user_id);
            if (! $user) {
                throw new AuthorizationException('Owning user not found for background job.');
            }

            if (! $user->can('view_pos_reports')) {
                throw new AuthorizationException('Owning user lacks view_pos_reports permission.');
            }

            $currentVisibility = $scopeService->visibilityFor($user);

            $metadata = $job->metadata ?? [];
            if (! is_array($metadata) || ! isset($metadata['report_scope'])) {
                throw new AuthorizationException('Background job is missing trusted report_scope metadata.');
            }

            $storedScope = ReportAuthorizationScope::fromArray($metadata['report_scope']);
            $effectiveVisibility = $storedScope->intersect($currentVisibility);

            $service->setScopeCeiling($storedScope);

            $from = (string) ($metadata['from'] ?? now()->startOfMonth()->toDateString());
            $to = (string) ($metadata['to'] ?? now()->toDateString());
            $filters = is_array($metadata['filters'] ?? null) ? $metadata['filters'] : [];
            unset(
                $filters['organization_id'],
                $filters['report_scope'],
                $filters['authorization_scope'],
                $filters['tenant_scope'],
            );

            $job->updateProgress(25);

            $summary = $service->summaryForPeriod($user, $from, $to, $filters);
            $paymentReconciliation = $service->paymentReconciliation($user, $from, $to, $filters);
            $topProducts = $service->productSalesForPeriod($user, $from, $to, $filters)->take(15)->values()->all();
            $dailyTrend = $service->dailyTrend($user, $from, $to, $filters);

            $job->updateProgress(60);

            $pdf = Pdf::loadView('cooperative.pos.report_pdf', [
                'from' => $from,
                'to' => $to,
                'summary' => $summary,
                'paymentReconciliation' => $paymentReconciliation,
                'topProducts' => $topProducts,
                'dailyTrend' => $dailyTrend,
            ])->setPaper('a4', 'portrait');

            $job->updateProgress(85);

            $fileName = "laporan-pos-{$from}-sd-{$to}-{$job->uuid}.pdf";
            $relativePath = "reports/{$job->uuid}/{$fileName}";

            Storage::disk($job->disk)->put($relativePath, $pdf->output());

            $size = Storage::disk($job->disk)->size($relativePath);

            $job->markCompleted(
                filePath: $relativePath,
                originalName: $fileName,
                mimeType: 'application/pdf',
                fileSize: $size,
            );

            $audit->log('pos.report.pdf.completed', 'cooperative.pos', $job, [
                'new' => [
                    'job_id' => $job->getKey(),
                    'status' => BackgroundJobStatus::Completed->value,
                    'file_size' => $size,
                ],
                'reason' => 'POS report PDF queue job completed.',
            ], AuditContext::forQueue($effectiveVisibility->organizationId));
        } catch (Throwable $exception) {
            if ($relativePath !== null) {
                Storage::disk($job->disk)->delete($relativePath);
            }

            Log::error('GeneratePosReportPdf failed', [
                'background_job_id' => $job->id,
                'uuid' => $job->uuid,
                'error' => $exception->getMessage(),
            ]);
            $job->markFailed($exception->getMessage());

            try {
                $audit->log('pos.report.pdf.failed', 'cooperative.pos', $job, [
                    'new' => [
                        'job_id' => $job->getKey(),
                        'status' => BackgroundJobStatus::Failed->value,
                    ],
                    'reason' => 'POS report PDF queue job failed.',
                ], AuditContext::forQueue($job->user?->organization_id));
            } catch (Throwable $auditException) {
                Log::critical('POS report queue failure audit could not be persisted.', [
                    'background_job_id' => $job->getKey(),
                    'exception_class' => $auditException::class,
                ]);
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $job = BackgroundJob::query()->find($this->backgroundJobId);
        if (! $job) {
            return;
        }

        $status = $job->status;
        if ($status !== BackgroundJobStatus::Failed) {
            $job->markFailed($exception?->getMessage() ?? 'Unknown error');
        }
    }

    private function claimJob(): ?BackgroundJob
    {
        return DB::transaction(function (): ?BackgroundJob {
            $job = BackgroundJob::query()
                ->with('user')
                ->whereKey($this->backgroundJobId)
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            if ($job->status === BackgroundJobStatus::Completed) {
                return null;
            }

            $job->markProcessing();

            return $job;
        });
    }
}
