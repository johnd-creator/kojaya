<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ReceiveGoodsReceiveNote;
use App\Models\GoodsReceiveNote;
use App\Models\GoodsReceiveNoteItem;
use App\Models\PurchaseOrder;
use App\Services\Procurement\ProcurementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrnController extends Controller
{
    public function __construct(private readonly ProcurementService $procurement) {}

    public function index(Request $request)
    {
        if (! $request->user()->can('view_grn_all')) {
            abort(403, 'Unauthorized to view GRNs');
        }

        $receipts = GoodsReceiveNote::query()
            ->forUser()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (GoodsReceiveNote $grn) => [
                'id' => $grn->id,
                'grn_no' => $grn->grn_no,
                'status' => $grn->status,
                'received_at' => optional($grn->received_at)->toISOString(),
                'purchase_order_id' => $grn->purchase_order_id,
            ]);

        return Inertia::render('Procurement/Grn/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function show(GoodsReceiveNote $goodsReceiveNote)
    {
        $grn = $goodsReceiveNote->load('purchaseOrder.items');

        // Get all items received for this PO across all GRNs to calculate remaining qty
        $allReceived = GoodsReceiveNoteItem::query()
            ->whereHas('purchaseOrderItem', fn ($q) => $q->where('purchase_order_id', $grn->purchase_order_id))
            ->get();

        return Inertia::render('Procurement/Grn/Show', [
            'grn' => [
                'id' => $grn->id,
                'grn_no' => $grn->grn_no,
                'status' => $grn->status,
                'received_at' => optional($grn->received_at)->toISOString(),
                'purchase_order_id' => $grn->purchase_order_id,
            ],
            'poItems' => $grn->purchaseOrder->items->map(fn ($it) => [
                'id' => $it->id,
                'description' => $it->description,
                'qty' => (float) $it->qty,
            ])->all(),
            // Items received in THIS specific GRN (for display history of this doc)
            'currentGrnItems' => $grn->items->map(fn (GoodsReceiveNoteItem $it) => [
                'id' => $it->id,
                'purchase_order_item_id' => $it->purchase_order_item_id,
                'received_qty' => (float) $it->received_qty,
                'condition' => $it->condition,
                'created_at' => $it->created_at->toISOString(),
            ])->all(),
            // All items received for this PO (for calculating remaining)
            'allReceivedItems' => $allReceived->map(fn (GoodsReceiveNoteItem $it) => [
                'id' => $it->id,
                'purchase_order_item_id' => $it->purchase_order_item_id,
                'received_qty' => (float) $it->received_qty,
                'condition' => $it->condition,
                'created_at' => $it->created_at->toISOString(),
                'goods_receive_note_id' => $it->goods_receive_note_id,
            ])->all(),
        ]);
    }

    public function createFromPo(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! $request->user()->can('receive_grn')) {
            abort(403, 'Unauthorized to create GRN');
        }

        $grn = $this->procurement->createGrnFromPo($purchaseOrder);

        return redirect()->route('procurement.grns.show', $grn);
    }

    public function receive(ReceiveGoodsReceiveNote $request, GoodsReceiveNote $goodsReceiveNote)
    {
        if (! $request->user()->can('receive_grn')) {
            abort(403, 'Unauthorized to receive goods');
        }

        $result = $this->procurement->receiveGrn($goodsReceiveNote, $request->validated('items'));
        if (! $result['ok']) {
            return back()->withErrors(['grn' => 'Gagal receive GRN.']);
        }

        return back();
    }
}
