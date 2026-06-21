<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\PosSalesReportService;
use App\Services\Export\PosReportCsvExport;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosReportController extends Controller
{
    public function __construct(private PosSalesReportService $service) {}

    public function index(): Response
    {
        $from = request()->input('from', now()->startOfMonth()->toDateString());
        $to = request()->input('to', now()->toDateString());
        $filters = $this->filters();

        return Inertia::render('Cooperative/Pos/Reports/Index', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'analytics' => Inertia::defer(fn (): array => [
                'summary' => $this->service->summaryForPeriod($from, $to, $filters),
                'payment_reconciliation' => $this->service->paymentReconciliation($from, $to, $filters),
                'daily_trend' => $this->service->dailyTrend($from, $to, $filters),
                'top_products' => $this->service->productSalesForPeriod($from, $to, $filters)->take(20)->values()->all(),
                'top_members' => $this->service->topMembers($from, $to, $filters),
                'cashier_performance' => $this->service->cashierPerformance($from, $to, $filters),
            ], 'analytics'),
            'products' => PosProduct::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => PosCategory::query()->orderBy('name')->get(['id', 'name']),
            'cashiers' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $from = request()->input('from', now()->startOfMonth()->toDateString());
        $to = request()->input('to', now()->toDateString());
        $filters = $this->filters();
        $exporter = new PosReportCsvExport;

        return $exporter->stream($this->service, $from, $to, $filters);
    }

    public function exportPdf(): StreamedResponse
    {
        $from = request()->input('from', now()->startOfMonth()->toDateString());
        $to = request()->input('to', now()->toDateString());
        $filters = $this->filters();
        $exporter = new \App\Services\Export\PosReportPdfExport;

        return $exporter->stream($this->service, $from, $to, $filters);
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
