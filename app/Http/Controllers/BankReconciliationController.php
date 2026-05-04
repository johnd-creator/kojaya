<?php

namespace App\Http\Controllers;

use App\Models\BankTransferBatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankReconciliationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Finance/BankReconciliation/Index', [
            'batches' => BankTransferBatch::query()
                ->withCount('items')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(BankTransferBatch $batch): Response
    {
        $batch->load(['items.invoice.client']);

        return Inertia::render('Finance/BankReconciliation/Show', [
            'batch' => $batch,
            'stats' => [
                'items_count' => $batch->items->count(),
                'matched_items' => $batch->items->filter(fn ($item): bool => (bool) $item->invoice_id)->count(),
                'pending_items' => $batch->items->where('status', 'PENDING')->count(),
                'total_amount' => (float) $batch->items->sum('amount'),
            ],
        ]);
    }
}
