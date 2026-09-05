<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GeneratePosReportPdf;
use App\Models\BackgroundJob;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\PosProductAccessService;
use App\Services\Cooperative\PosSalesReportService;
use App\Services\Export\PosReportCsvExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosReportController extends Controller
{
    public function __construct(
        private PosSalesReportService $service,
        private PosProductAccessService $productAccess,
        private OrganizationScopedQueryService $scopeService,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $visibility = $this->scopeService->visibilityFor($user);

        $from = request()->input('from', now()->startOfMonth()->toDateString());
        $to = request()->input('to', now()->toDateString());
        $filters = $this->filters();

        return Inertia::render('Cooperative/Pos/Reports/Index', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'analytics' => Inertia::defer(fn (): array => [
                'summary' => $this->service->summaryForPeriod($user, $from, $to, $filters),
                'payment_reconciliation' => $this->service->paymentReconciliation($user, $from, $to, $filters),
                'daily_trend' => $this->service->dailyTrend($user, $from, $to, $filters),
                'top_products' => $this->service->productSalesForPeriod($user, $from, $to, $filters)->take(20)->values()->all(),
                'top_members' => $this->service->topMembers($user, $from, $to, $filters),
                'cashier_performance' => $this->service->cashierPerformance($user, $from, $to, $filters),
            ], 'analytics'),
            'products' => $this->productAccess->scopeVisibleTo(PosProduct::query(), $user)
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => PosCategory::query()
                ->when(! $visibility->global, fn ($q) => $q->whereHas('products', fn ($pq) => $pq->where('organization_id', $visibility->organizationId)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'cashiers' => User::query()
                ->when(! $visibility->global, fn ($q) => $q->where('organization_id', $visibility->organizationId))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $user = request()->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $this->scopeService->visibilityFor($user);

        $from = request()->input('from', now()->startOfMonth()->toDateString());
        $to = request()->input('to', now()->toDateString());
        $filters = $this->filters();
        $exporter = new PosReportCsvExport;

        return $exporter->stream($this->service, $user, $from, $to, $filters);
    }

    public function enqueuePdf(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->can('view_pos_reports'), 403);

        $this->scopeService->visibilityFor($user);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'filters' => ['nullable', 'array'],
            'filters.pos_product_id' => ['nullable', 'integer'],
            'filters.category_id' => ['nullable', 'integer'],
            'filters.cashier_id' => ['nullable', 'integer'],
            'filters.cooperative_member_id' => ['nullable', 'integer'],
            'filters.payment_method' => ['nullable', 'string', 'max:40'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        $filters = array_filter($validated['filters'] ?? [], fn ($v) => $v !== null && $v !== '');

        $job = BackgroundJob::query()->create([
            'user_id' => $user->id,
            'type' => 'pos.report.pdf',
            'status' => BackgroundJobStatus::Pending,
            'progress' => 0,
            'metadata' => [
                'from' => $from,
                'to' => $to,
                'filters' => $filters,
            ],
        ]);

        GeneratePosReportPdf::dispatch($job->id);

        return response()->json([
            'job_id' => $job->uuid,
            'status' => $job->status->value,
            'progress' => $job->progress,
        ], 202);
    }

    public function pdfStatus(Request $request, BackgroundJob $job): JsonResponse
    {
        abort_unless($job->isOwnedBy($request->user()->id), 404);

        return response()->json([
            'job_id' => $job->uuid,
            'status' => $job->status->value,
            'progress' => $job->progress,
            'error_message' => $job->error_message,
            'file_size' => $job->file_size,
            'original_name' => $job->original_name,
            'started_at' => optional($job->started_at)->toIso8601String(),
            'finished_at' => optional($job->finished_at)->toIso8601String(),
            'download_url' => $job->status->isDownloadable()
                ? route('cooperative.pos.reports.export.pdf.download', $job)
                : null,
        ]);
    }

    public function pdfDownload(Request $request, BackgroundJob $job): StreamedResponse|RedirectResponse
    {
        abort_unless($job->isOwnedBy($request->user()->id), 404);

        if ($job->status !== BackgroundJobStatus::Completed) {
            abort(409, 'File belum siap diunduh.');
        }

        abort_unless($job->file_path, 404);

        $disk = Storage::disk($job->disk);
        abort_unless($disk->exists($job->file_path), 404);

        return $disk->download($job->file_path, $job->original_name ?? basename($job->file_path), [
            'Content-Type' => $job->mime_type ?? 'application/pdf',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return array_filter([
            'pos_product_id' => request()->input('pos_product_id'),
            'category_id' => request()->input('category_id'),
            'cashier_id' => request()->input('cashier_id'),
            'cooperative_member_id' => request()->input('cooperative_member_id'),
            'payment_method' => request()->input('payment_method'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
