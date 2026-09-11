<?php

namespace App\Http\Controllers;

use App\Enums\PermissionEnum;
use App\Models\BankTransferBatch;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankReconciliationController extends Controller
{
    public function index(Request $request, OrganizationScopeService $scopeService): Response
    {
        $this->authorizePermission('manage_bank_reconciliation');

        $batches = $scopeService->scopeVisibleTo(
            BankTransferBatch::query(),
            $request->user(),
            PermissionEnum::BANK_BATCH_VIEW_ALL->value
        )
            ->withCount('items')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Finance/BankReconciliation/Index', [
            'batches' => $batches,
        ]);
    }

    public function show(Request $request, string $batch, OrganizationScopeService $scopeService): Response
    {
        $this->authorizePermission('manage_bank_reconciliation');

        /** @var BankTransferBatch $batchModel */
        $batchModel = $scopeService->resolveVisible(
            BankTransferBatch::class,
            $request->user(),
            $batch,
            PermissionEnum::BANK_BATCH_VIEW_ALL->value
        );

        $this->authorize('viewForReconciliation', $batchModel);

        // Filter eager loaded invoice by batch's organization to prevent leaking corrupt legacy relations
        $batchModel->load([
            'items.invoice' => function ($query) use ($batchModel) {
                $query->where('organization_id', $batchModel->organization_id)->with('client');
            },
        ]);

        return Inertia::render('Finance/BankReconciliation/Show', [
            'batch' => $batchModel,
            'stats' => [
                'items_count' => $batchModel->items->count(),
                'matched_items' => $batchModel->items->filter(fn ($item): bool => (bool) $item->invoice_id)->count(),
                'pending_items' => $batchModel->items->where('status', 'PENDING')->count(),
                'total_amount' => (float) $batchModel->items->sum('amount'),
            ],
        ]);
    }
}
