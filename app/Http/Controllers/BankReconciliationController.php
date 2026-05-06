<?php

namespace App\Http\Controllers;

use App\Models\BankTransferBatch;
use Inertia\Inertia;
use Inertia\Response;

class BankReconciliationController extends Controller
{
    public function index(): Response
    {
        $this->authorizePermission('manage_bank_reconciliation');

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
        $this->authorizePermission('manage_bank_reconciliation');

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
