<?php

namespace App\Services\Export;

use App\Models\User;
use App\Services\Cooperative\PosSalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosReportPdfExport
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function stream(PosSalesReportService $service, User $actor, string $from, string $to, array $filters = []): StreamedResponse
    {
        $fileName = "laporan-pos-{$from}-sd-{$to}.pdf";
        $summary = $service->summaryForPeriod($actor, $from, $to, $filters);
        $paymentReconciliation = $service->paymentReconciliation($actor, $from, $to, $filters);
        $topProducts = $service->productSalesForPeriod($actor, $from, $to, $filters)->take(15)->values()->all();
        $dailyTrend = $service->dailyTrend($actor, $from, $to, $filters);

        $pdf = Pdf::loadView('cooperative.pos.report_pdf', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'paymentReconciliation' => $paymentReconciliation,
            'topProducts' => $topProducts,
            'dailyTrend' => $dailyTrend,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
