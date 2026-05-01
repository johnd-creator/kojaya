<?php

namespace App\Services;

use App\Models\Invoice;

class BankStatementReconciler
{
    public function reconcileCsv(string $csv): int
    {
        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        if (count($lines) < 2) {
            return 0;
        }
        $header = array_map('strtoupper', str_getcsv(array_shift($lines)));
        $refIndex = array_search('REFERENCE', $header);
        $amountIndex = array_search('AMOUNT', $header);

        $matched = 0;
        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $ref = $cols[$refIndex] ?? null;
            $amount = (float) trim((string) ($cols[$amountIndex] ?? 0));

            if ($ref && str_starts_with($ref, 'INV-')) {
                $invoiceId = substr($ref, 4);
                $invoice = Invoice::find($invoiceId);
                $invoiceTotal = $invoice ? round((float) $invoice->total_amount, 2) : null;
                $statementAmount = round($amount, 2);
                if ($invoice && $invoiceTotal !== null && abs($invoiceTotal - $statementAmount) < 0.01) {
                    $invoice->status = 'PAID';
                    $invoice->save();
                    $matched++;
                }
            }
        }

        return $matched;
    }
}
