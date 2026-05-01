<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Services\Procurement\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly ProcurementService $procurement) {}

    public function index(Request $request)
    {
        if (! $request->user()->can('view_po_all')) {
            abort(403, 'Unauthorized to view Purchase Orders');
        }

        $orders = PurchaseOrder::query()
            ->forUser()
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'po_no' => $po->po_no,
                'status' => $po->status,
                'total_amount' => (float) $po->total_amount,
                'issued_at' => optional($po->issued_at)->toISOString(),
                'items_count' => $po->items_count,
            ]);

        return Inertia::render('Procurement/PurchaseOrders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $po = $purchaseOrder->load('items', 'purchaseRequest');

        return Inertia::render('Procurement/PurchaseOrders/Show', [
            'po' => [
                'id' => $po->id,
                'po_no' => $po->po_no,
                'status' => $po->status,
                'total_amount' => (float) $po->total_amount,
                'issued_at' => optional($po->issued_at)->toISOString(),
                'purchase_request_id' => $po->purchase_request_id,
                'items' => $po->items->map(fn (PurchaseOrderItem $it) => [
                    'id' => $it->id,
                    'description' => $it->description,
                    'qty' => (float) $it->qty,
                    'price' => (float) $it->price,
                    'amount' => (float) $it->amount,
                ])->all(),
            ],
        ]);
    }

    public function createFromPr(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if (! $request->user()->can('create_po')) {
            abort(403, 'Unauthorized to create PO');
        }
        
        if ($purchaseRequest->status !== 'APPROVED') {
            return back()->withErrors(['po' => 'PR belum approved.']);
        }

        $po = $this->procurement->createPoFromPr($purchaseRequest->load('items'));

        return redirect()->route('procurement.pos.show', $po);
    }
}
