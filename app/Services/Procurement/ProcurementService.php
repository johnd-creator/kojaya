<?php

namespace App\Services\Procurement;

use App\Models\ApprovalLog;
use App\Models\GoodsReceiveNote;
use App\Models\GoodsReceiveNoteItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use App\Models\SparePartStock;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    protected BudgetValidationService $budget;

    protected ApprovalService $approval;

    public function __construct(BudgetValidationService $budget, ApprovalService $approval)
    {
        $this->budget = $budget;
        $this->approval = $approval;
    }

    public function submitPr(PurchaseRequest $pr): array
    {
        $items = $pr->items->map(fn ($i) => ['gl_account' => $i->gl_account, 'amount' => (float) $i->amount])->all();
        $check = $this->budget->checkAvailability($items, $pr->organization_id, null, $pr->cost_center);
        if (! $check['ok']) {
            return ['ok' => false, 'error' => 'budget_insufficient', 'details' => $check['details']];
        }
        $commit = $this->budget->commit($items, $pr->organization_id, null, $pr->cost_center);
        if (! $commit['ok']) {
            return ['ok' => false, 'error' => 'budget_commit_failed'];
        }
        $pr->status = 'SUBMITTED';
        $pr->submitted_at = now();
        $pr->save();
        ApprovalLog::create([
            'subject_type' => 'PR',
            'subject_id' => $pr->id,
            'from_status' => 'DRAFT',
            'to_status' => 'SUBMITTED',
        ]);

        return ['ok' => true];
    }

    public function approvePr(PurchaseRequest $pr, $user, int $level): array
    {
        $req = $this->approval->requiredLevels((float) $pr->total_amount);
        if (! $this->approval->canApprove($user, $level)) {
            return ['ok' => false, 'error' => 'not_allowed'];
        }
        $from = $pr->status;
        $currentIdx = array_search($level, $req, true);
        if ($currentIdx === false) {
            return ['ok' => false, 'error' => 'invalid_level'];
        }
        if ($currentIdx === count($req) - 1) {
            $pr->status = 'APPROVED';
        } else {
            $pr->status = 'APPROVAL_L'.$req[$currentIdx + 1];
        }
        $pr->save();
        ApprovalLog::create([
            'subject_type' => 'PR',
            'subject_id' => $pr->id,
            'from_status' => $from,
            'to_status' => $pr->status,
            'approved_by' => $user->id ?? null,
        ]);

        return ['ok' => true];
    }

    public function createPoFromPr(PurchaseRequest $pr): PurchaseOrder
    {
        return DB::transaction(function () use ($pr) {
            // Default to first warehouse for now, or null
            $defaultWarehouse = Warehouse::where('organization_id', $pr->organization_id)->first();

            $po = PurchaseOrder::create([
                'organization_id' => $pr->organization_id,
                'unit_id' => $pr->unit_id,
                'purchase_request_id' => $pr->id,
                'warehouse_id' => $defaultWarehouse?->id,
                'status' => 'ISSUED',
                'total_amount' => $pr->total_amount,
                'issued_at' => now(),
            ]);
            foreach ($pr->items as $it) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'purchase_request_item_id' => $it->id,
                    'spare_part_id' => $it->spare_part_id,
                    'description' => $it->description,
                    'qty' => $it->qty,
                    'price' => $it->price,
                    'amount' => $it->amount,
                ]);
            }
            $pr->status = 'PO_CREATED';
            $pr->save();
            ApprovalLog::create([
                'subject_type' => 'PR',
                'subject_id' => $pr->id,
                'from_status' => 'APPROVED',
                'to_status' => 'PO_CREATED',
            ]);

            return $po;
        });
    }

    public function createGrnFromPo(PurchaseOrder $po): GoodsReceiveNote
    {
        return GoodsReceiveNote::create([
            'organization_id' => $po->organization_id,
            'unit_id' => $po->unit_id,
            'purchase_order_id' => $po->id,
            'warehouse_id' => $po->warehouse_id,
            'status' => 'DRAFT',
        ]);
    }

    public function receiveGrn(GoodsReceiveNote $grn, array $items): array
    {
        return DB::transaction(function () use ($grn, $items) {
            $po = $grn->purchaseOrder()->with('items')->lockForUpdate()->firstOrFail();

            foreach ($items as $row) {
                $qty = (float) $row['received_qty'];
                if ($qty <= 0) continue;

                GoodsReceiveNoteItem::create([
                    'goods_receive_note_id' => $grn->id,
                    'purchase_order_item_id' => $row['po_item_id'],
                    'received_qty' => $qty,
                    'condition' => $row['condition'] ?? null,
                ]);

                // Inventory Update Logic
                $poItem = $po->items->firstWhere('id', $row['po_item_id']);
                if ($poItem && $poItem->spare_part_id && $grn->warehouse_id) {
                    $stock = SparePartStock::firstOrNew([
                        'spare_part_id' => $poItem->spare_part_id,
                        'warehouse_id' => $grn->warehouse_id,
                    ]);
                    
                    // If new, set default bin location if needed, or leave null
                    // For now we just update quantity
                    $stock->quantity = ((float) $stock->quantity) + $qty;
                    $stock->save();
                }
            }


            $receivedByPoItem = GoodsReceiveNoteItem::query()
                ->whereIn('purchase_order_item_id', $po->items->pluck('id'))
                ->selectRaw('purchase_order_item_id, SUM(received_qty) as total_received')
                ->groupBy('purchase_order_item_id')
                ->pluck('total_received', 'purchase_order_item_id');

            $isFull = true;
            foreach ($po->items as $poItem) {
                $received = (float) ($receivedByPoItem[$poItem->id] ?? 0);
                if ($received < (float) $poItem->qty) {
                    $isFull = false;
                    break;
                }
            }

            $grn->status = $isFull ? 'RECEIVED_FULL' : 'RECEIVED_PARTIAL';
            $grn->received_at = now();
            $grn->save();

            $po->status = $isFull ? 'RECEIVED' : 'PARTIALLY_RECEIVED';
            $po->save();

            return ['ok' => true];
        });
    }
}
