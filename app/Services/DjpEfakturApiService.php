<?php

namespace App\Services;

use App\Models\EfakturSubmission;
use App\Models\Invoice;

class DjpEfakturApiService
{
    public function submit(Invoice $invoice): array
    {
        $payload = [
            'invoice_no' => $invoice->invoice_no,
            'date' => $invoice->invoice_date?->format('Y-m-d'),
            'client_tax_id' => preg_replace('/[^0-9]/', '', $invoice->client?->tax_id ?? ''),
            'dpp' => (float) $invoice->amount,
            'ppn' => (float) $invoice->tax_amount,
        ];

        return [
            'success' => true,
            'submission_id' => (string) \Illuminate\Support\Str::uuid(),
            'message' => 'Submitted',
            'payload' => $payload,
        ];
    }

    public function checkStatus(EfakturSubmission $submission): array
    {
        return [
            'success' => true,
            'status' => 'ACCEPTED',
            'submission_id' => $submission->id,
        ];
    }
}
