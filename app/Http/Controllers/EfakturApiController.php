<?php

namespace App\Http\Controllers;

use App\Models\EfakturSubmission;
use App\Models\Invoice;
use App\Services\DjpEfakturApiService;

class EfakturApiController extends Controller
{
    public function submit(Invoice $invoice, DjpEfakturApiService $service)
    {
        $this->authorizePermission('manage_efaktur');

        $payload = $service->submit($invoice);

        $submission = EfakturSubmission::create([
            'invoice_id' => $invoice->id,
            'provider' => 'DJP',
            'status' => 'SUBMITTED',
            'request_payload' => $payload['payload'],
            'response_payload' => $payload,
        ]);

        return response()->json([
            'success' => true,
            'submission' => $submission,
        ]);
    }

    public function status(EfakturSubmission $submission, DjpEfakturApiService $service)
    {
        $this->authorizePermission('manage_efaktur');

        $result = $service->checkStatus($submission);
        $submission->status = $result['status'];
        $submission->response_payload = $result;
        $submission->save();

        return response()->json([
            'success' => true,
            'submission' => $submission,
        ]);
    }
}
