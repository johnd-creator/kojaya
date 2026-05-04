<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEfakturBatchRequest;
use App\Models\EfakturBatch;
use App\Models\EfakturBatchItem;
use App\Services\EfakturBatchExportService;
use Illuminate\Support\Facades\Auth;

class EfakturController extends Controller
{
    public function createBatch(CreateEfakturBatchRequest $request)
    {
        $validated = $request->validated();

        $batch = EfakturBatch::create([
            'organization_id' => Auth::user()->organization_id,
            'reference' => $validated['reference'] ?? null,
            'status' => 'DRAFT',
        ]);

        foreach ($validated['invoice_ids'] as $id) {
            EfakturBatchItem::create([
                'batch_id' => $batch->id,
                'invoice_id' => $id,
            ]);
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
        ]);
    }

    public function downloadCsv(EfakturBatch $batch, EfakturBatchExportService $service)
    {
        $csv = $service->generateCsv($batch);
        $filename = 'efaktur_batch_'.$batch->id.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
