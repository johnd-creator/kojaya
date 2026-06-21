<?php

namespace App\Jobs;

use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Models\BackgroundJob;
use App\Services\Cooperative\PosSalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function handle(PosSalesReportService $service): void
    {
        $job = $this->claimJob();

        if (! $job) {
            return;
        }

        try {
            $job->updateProgress(10);

            $metadata = $job->metadata ?? [];
            $from = (string) ($metadata['from'] ?? now()->startOfMonth()->toDateString());
            $to = (string) ($metadata['to'] ?? now()->toDateString());
            $filters = is_array($metadata['filters'] ?? null) ? $metadata['filters'] : [];

            $job->updateProgress(25);

            $summary = $service->summaryForPeriod($from, $to, $filters);
            $paymentReconciliation = $service->paymentReconciliation($from, $to, $filters);
            $topProducts = $service->productSalesForPeriod($from, $to, $filters)->take(15)->values()->all();
            $dailyTrend = $service->dailyTrend($from, $to, $filters);

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
        } catch (Throwable $exception) {
            Log::error('GeneratePosReportPdf failed', [
                'background_job_id' => $job->id,
                'uuid' => $job->uuid,
                'error' => $exception->getMessage(),
            ]);
            $job->markFailed($exception->getMessage());

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
