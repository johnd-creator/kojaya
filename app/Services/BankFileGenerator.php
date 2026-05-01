<?php

namespace App\Services;

use App\Models\BankTransferBatch;

class BankFileGenerator
{
    public function generateCsv(BankTransferBatch $batch): string
    {
        $rows = [];
        foreach ($batch->items as $item) {
            $rows[] = [
                'ACCOUNT_NUMBER' => $batch->account_number,
                'BENEFICIARY_NAME' => $item->beneficiary_name,
                'BENEFICIARY_ACCOUNT' => $item->beneficiary_account,
                'AMOUNT' => number_format((float) $item->amount, 2, '.', ''),
                'CURRENCY' => $item->currency,
                'REFERENCE' => $item->reference ?? '',
            ];
        }
        if (empty($rows)) {
            return '';
        }
        $header = array_keys($rows[0]);
        $csv = implode(',', $header)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map([$this, 'escape'], $row))."\n";
        }

        return $csv;
    }

    private function escape($value): string
    {
        $v = (string) $value;
        if (str_contains($v, ',') || str_contains($v, '"') || str_contains($v, "\n")) {
            $v = '"'.str_replace('"', '""', $v).'"';
        }

        return $v;
    }
}
