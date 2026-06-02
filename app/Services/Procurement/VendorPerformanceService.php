<?php

namespace App\Services\Procurement;

use App\Models\GoodsReceiveNoteItem;
use App\Models\Vendor;
use App\Models\VendorPerformanceSnapshot;

class VendorPerformanceService
{
    public function calculate(Vendor $vendor): VendorPerformanceSnapshot
    {
        $purchaseOrders = $vendor->purchaseOrders()->with('items')->get();
        $purchaseOrderIds = $purchaseOrders->pluck('id');
        $goodsReceiveNotes = $purchaseOrderIds->isEmpty()
            ? collect()
            : \App\Models\GoodsReceiveNote::query()
                ->whereIn('purchase_order_id', $purchaseOrderIds)
                ->get();

        $receivedCount = $goodsReceiveNotes->count();
        $purchaseOrderCount = $purchaseOrders->count();
        $onTimeDeliveryRate = $purchaseOrderCount > 0
            ? round(($receivedCount / $purchaseOrderCount) * 100, 2)
            : 0.0;

        $grnIds = $goodsReceiveNotes->pluck('id');
        $receivedItems = $grnIds->isEmpty()
            ? collect()
            : GoodsReceiveNoteItem::query()
                ->whereIn('goods_receive_note_id', $grnIds)
                ->get();
        $acceptedItems = $receivedItems->filter(function (GoodsReceiveNoteItem $item): bool {
            $condition = strtolower((string) $item->condition);

            return $condition === '' || in_array($condition, ['ok', 'good', 'baik'], true);
        });
        $qualityAcceptanceRate = $receivedItems->isNotEmpty()
            ? round(($acceptedItems->count() / $receivedItems->count()) * 100, 2)
            : 0.0;
        $score = round(($onTimeDeliveryRate * 0.5) + ($qualityAcceptanceRate * 0.5), 2);
        $rating = max(1, min(5, (int) ceil($score / 20)));

        $snapshot = VendorPerformanceSnapshot::query()->create([
            'vendor_id' => $vendor->id,
            'score' => $score,
            'rating' => $rating,
            'on_time_delivery_rate' => $onTimeDeliveryRate,
            'quality_acceptance_rate' => $qualityAcceptanceRate,
            'purchase_order_count' => $purchaseOrderCount,
            'goods_receive_note_count' => $receivedCount,
            'calculated_at' => now(),
            'breakdown' => [
                'weights' => [
                    'on_time_delivery_rate' => 0.5,
                    'quality_acceptance_rate' => 0.5,
                ],
                'accepted_item_count' => $acceptedItems->count(),
                'received_item_count' => $receivedItems->count(),
            ],
        ]);

        $vendor->update(['rating' => $rating]);

        return $snapshot->load('vendor');
    }
}
