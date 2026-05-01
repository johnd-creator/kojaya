<?php

namespace App\Services;

use App\Models\EfakturBatch;

class EfakturBatchExportService
{
    public function generateCsv(EfakturBatch $batch): string
    {
        $batch->load('items.invoice.client');
        $service = new EFakturExportService;

        $rows = [];
        foreach ($batch->items as $item) {
            $invoice = $item->invoice;
            $csv = $service->generateCsv($invoice);
            $lines = array_filter(array_map('trim', explode("\n", $csv)));
            if (count($lines) >= 2) {
                $header = $lines[0];
                $data = $lines[1];
                if (empty($rows)) {
                    $rows[] = $header;
                }
                $rows[] = $data;
            }
        }

        return implode("\n", $rows)."\n";
    }
}
