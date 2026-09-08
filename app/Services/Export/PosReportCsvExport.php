<?php

namespace App\Services\Export;

use App\Models\User;
use App\Services\Cooperative\PosSalesReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosReportCsvExport
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function stream(PosSalesReportService $service, User $actor, string $from, string $to, array $filters = []): StreamedResponse
    {
        $fileName = "laporan-pos-{$from}-sd-{$to}.csv";

        return response()->streamDownload(function () use ($service, $actor, $from, $to, $filters): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Laporan POS', "Periode: {$from} sd {$to}"]);
            fputcsv($handle, []);

            fputcsv($handle, ['RINGKASAN']);
            $summary = $service->summaryForPeriod($actor, $from, $to, $filters);
            fputcsv($handle, ['Transaksi', $summary['transactions']]);
            fputcsv($handle, ['Void', $summary['voided_transactions']]);
            fputcsv($handle, ['Penjualan Kotor', $summary['gross_sales']]);
            fputcsv($handle, ['Total Diskon', $summary['total_discount']]);
            fputcsv($handle, ['Laba Kotor', $summary['gross_profit']]);
            fputcsv($handle, ['Retur (Count)', $summary['returns']['count']]);
            fputcsv($handle, ['Retur (Nilai)', $summary['returns']['total']]);
            fputcsv($handle, ['Penjualan Bersih', $summary['net_sales']]);
            fputcsv($handle, []);

            fputcsv($handle, ['REKONSILIASI PEMBAYARAN']);
            fputcsv($handle, ['Metode', 'Jumlah', 'Total']);
            foreach ($service->paymentReconciliation($actor, $from, $to, $filters) as $row) {
                fputcsv($handle, [$row['method'], $row['count'], $row['total']]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['PENJUALAN PRODUK']);
            fputcsv($handle, ['Produk', 'Kategori', 'Qty', 'Pendapatan', 'Laba Kotor', 'Margin %']);
            foreach ($service->productSalesForPeriod($actor, $from, $to, $filters) as $row) {
                fputcsv($handle, [
                    $row['product_name'],
                    $row['category'] ?? '-',
                    $row['quantity'],
                    $row['revenue'],
                    $row['gross_profit'],
                    $row['margin_percent'],
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['TREN HARIAN']);
            fputcsv($handle, ['Tanggal', 'Transaksi', 'Pendapatan']);
            foreach ($service->dailyTrend($actor, $from, $to, $filters) as $row) {
                fputcsv($handle, [$row['date'], $row['transactions'], $row['revenue']]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
