<?php

namespace App\Services\Cooperative;

use App\Models\PosCashierShift;
use App\Models\PosInventoryLocation;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use App\Models\PosStockCount;
use App\Models\PosStockCountItem;
use App\Models\PosStockMovement;
use App\Models\PosStockReceipt;
use App\Models\PosStockReceiptItem;
use App\Models\PosStockTransfer;
use App\Models\PosStockTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosInventoryService
{
    public function ensureDefaultLocation(): PosInventoryLocation
    {
        $default = PosInventoryLocation::query()->where('is_default', true)->first();

        if ($default) {
            return $default;
        }

        $default = PosInventoryLocation::query()->create([
            'code' => 'MAIN',
            'name' => 'Toko Utama',
            'location_type' => 'STORE',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->syncDefaultLocationStocks($default->id);

        return $default;
    }

    public function syncDefaultLocationStocks(?int $defaultLocationId = null): int
    {
        $defaultLocationId ??= $this->ensureDefaultLocation()->id;

        $existing = PosInventoryStock::query()
            ->where('pos_inventory_location_id', $defaultLocationId)
            ->pluck('pos_product_id')
            ->all();

        $products = PosProduct::query()
            ->whereNotIn('id', $existing)
            ->where('stock', '>', 0)
            ->get(['id', 'stock']);

        $synced = 0;
        foreach ($products as $product) {
            PosInventoryStock::query()->create([
                'pos_product_id' => $product->id,
                'pos_inventory_location_id' => $defaultLocationId,
                'quantity' => (int) $product->stock,
                'reserved' => 0,
            ]);
            $synced++;
        }

        return $synced;
    }

    public function syncProductStockFromLocations(PosProduct $product): int
    {
        $total = (int) PosInventoryStock::query()
            ->where('pos_product_id', $product->id)
            ->sum('quantity');

        $product->forceFill(['stock' => $total])->save();

        return $total;
    }

    public function resolveLocationFor(?int $shiftId = null): PosInventoryLocation
    {
        if ($shiftId !== null) {
            $shift = PosCashierShift::query()->find($shiftId);
            if ($shift && $shift->pos_inventory_location_id) {
                $location = PosInventoryLocation::query()->find($shift->pos_inventory_location_id);
                if ($location) {
                    return $location;
                }
            }
        }

        return $this->ensureDefaultLocation();
    }

    public function getStockAt(PosProduct $product, int $locationId): int
    {
        $stock = PosInventoryStock::query()
            ->where('pos_product_id', $product->id)
            ->where('pos_inventory_location_id', $locationId)
            ->first();

        return (int) ($stock?->quantity ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createReceipt(array $data, ?User $receiver = null): PosStockReceipt
    {
        return DB::transaction(function () use ($data, $receiver): PosStockReceipt {
            $location = PosInventoryLocation::query()->findOrFail($data['pos_inventory_location_id']);
            $total = 0.0;

            $receipt = PosStockReceipt::query()->create([
                'receipt_no' => $this->nextReceiptNo(),
                'pos_supplier_id' => $data['pos_supplier_id'] ?? null,
                'pos_inventory_location_id' => $location->id,
                'received_by' => $receiver?->id,
                'reference_no' => $data['reference_no'] ?? null,
                'received_at' => $data['received_at'] ?? now()->toDateString(),
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'status' => PosStockReceipt::STATUS_POSTED,
            ]);

            foreach ($data['items'] as $item) {
                $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);
                $total += $lineTotal;

                PosStockReceiptItem::query()->create([
                    'pos_stock_receipt_id' => $receipt->id,
                    'pos_product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'batch_no' => $item['batch_no'] ?? null,
                    'expired_at' => $item['expired_at'] ?? null,
                ]);

                $this->addStock($product, $location, $qty, PosStockReceipt::class, $receipt->id, "Receipt {$receipt->receipt_no}", $receipt->receipt_no);
            }

            $receipt->forceFill(['total_amount' => round($total, 2)])->save();

            return $receipt->load(['items.product', 'supplier', 'location']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data, ?User $user = null): PosStockTransfer
    {
        return DB::transaction(function () use ($data, $user): PosStockTransfer {
            $fromId = (int) $data['from_location_id'];
            $toId = (int) $data['to_location_id'];

            if ($fromId === $toId) {
                throw ValidationException::withMessages([
                    'to_location_id' => 'Lokasi tujuan tidak boleh sama dengan lokasi asal.',
                ]);
            }

            $transfer = PosStockTransfer::query()->create([
                'transfer_no' => $this->nextTransferNo(),
                'from_location_id' => $fromId,
                'to_location_id' => $toId,
                'requested_by' => $user?->id,
                'transferred_at' => $data['transferred_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => PosStockTransfer::STATUS_POSTED,
            ]);

            $from = PosInventoryLocation::query()->lockForUpdate()->findOrFail($fromId);
            $to = PosInventoryLocation::query()->lockForUpdate()->findOrFail($toId);

            foreach ($data['items'] as $item) {
                $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                $qty = (int) $item['quantity'];

                $available = $this->getStockAt($product, $from->id);
                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} di lokasi asal tidak cukup (tersisa {$available}).",
                    ]);
                }

                PosStockTransferItem::query()->create([
                    'pos_stock_transfer_id' => $transfer->id,
                    'pos_product_id' => $product->id,
                    'quantity' => $qty,
                ]);

                $this->deductStock($product, $from, $qty, PosStockTransfer::class, $transfer->id, "Transfer keluar {$transfer->transfer_no}", $transfer->transfer_no);
                $this->addStock($product, $to, $qty, PosStockTransfer::class, $transfer->id, "Transfer masuk {$transfer->transfer_no}", $transfer->transfer_no);
            }

            return $transfer->load(['items.product', 'fromLocation', 'toLocation']);
        });
    }

    /**
     * @param  array<int, array{pos_product_id:int, counted_qty:int, notes?:?string}>  $items
     */
    public function createCount(int $locationId, array $items, ?User $user = null): PosStockCount
    {
        return DB::transaction(function () use ($locationId, $items, $user): PosStockCount {
            $location = PosInventoryLocation::query()->lockForUpdate()->findOrFail($locationId);

            $count = PosStockCount::query()->create([
                'count_no' => $this->nextCountNo(),
                'pos_inventory_location_id' => $location->id,
                'requested_by' => $user?->id,
                'counted_at' => now()->toDateString(),
                'status' => PosStockCount::STATUS_DRAFT,
            ]);

            foreach ($items as $item) {
                $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                $systemQty = $this->getStockAt($product, $location->id);
                $countedQty = (int) $item['counted_qty'];

                PosStockCountItem::query()->create([
                    'pos_stock_count_id' => $count->id,
                    'pos_product_id' => $product->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'difference' => $countedQty - $systemQty,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $count->load(['items.product', 'location']);
        });
    }

    public function submitForReview(PosStockCount $count): PosStockCount
    {
        if ($count->status !== PosStockCount::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'count' => 'Stock opname tidak dalam status draft.',
            ]);
        }

        $count->forceFill(['status' => PosStockCount::STATUS_REVIEW])->save();

        return $count;
    }

    public function approveCount(PosStockCount $count, User $supervisor): PosStockCount
    {
        if ($count->status !== PosStockCount::STATUS_REVIEW) {
            throw ValidationException::withMessages([
                'count' => 'Stock opname belum direview.',
            ]);
        }

        return DB::transaction(function () use ($count, $supervisor): PosStockCount {
            $location = PosInventoryLocation::query()->lockForUpdate()->findOrFail($count->pos_inventory_location_id);
            $count->load('items');

            foreach ($count->items as $item) {
                $product = PosProduct::query()->lockForUpdate()->find($item->pos_product_id);
                if (! $product) {
                    continue;
                }
                $diff = (int) $item->difference;

                if ($diff === 0) {
                    continue;
                }

                if ($diff > 0) {
                    $this->addStock($product, $location, $diff, PosStockCount::class, $count->id, "Opname adjustment {$count->count_no}", $count->count_no);
                } else {
                    $this->deductStock($product, $location, abs($diff), PosStockCount::class, $count->id, "Opname adjustment {$count->count_no}", $count->count_no);
                }
            }

            $count->forceFill([
                'status' => PosStockCount::STATUS_APPROVED,
                'approved_by' => $supervisor->id,
            ])->save();

            return $count->refresh();
        });
    }

    public function sellStock(PosProduct $product, PosInventoryLocation $location, int $quantity, string $sourceType, int $sourceId, ?string $referenceNo = null, ?string $movementType = 'SALE'): PosStockMovement
    {
        $stock = $this->getStockAt($product, $location->id);
        if ($quantity > $stock) {
            throw ValidationException::withMessages([
                'stock' => "Stok {$product->name} di lokasi {$location->name} tidak cukup (tersisa {$stock}).",
            ]);
        }

        $this->deductStock($product, $location, $quantity, $sourceType, $sourceId, $this->buildMovementNote($movementType ?? 'SALE', $referenceNo), $referenceNo, $movementType ?? 'SALE');

        return PosStockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('pos_product_id', $product->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function restoreSaleStock(PosProduct $product, PosInventoryLocation $location, int $quantity, string $sourceType, int $sourceId, ?string $referenceNo = null, ?string $movementType = 'RETURN'): PosStockMovement
    {
        $this->addStock($product, $location, $quantity, $sourceType, $sourceId, $this->buildMovementNote($movementType ?? 'RETURN', $referenceNo), $referenceNo, $movementType ?? 'RETURN');

        return PosStockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('pos_product_id', $product->id)
            ->latest('id')
            ->firstOrFail();
    }

    private function buildMovementNote(string $type, ?string $referenceNo): string
    {
        return trim("{$type} ".($referenceNo ?? ''));
    }

    private function addStock(PosProduct $product, PosInventoryLocation $location, int $quantity, string $sourceType, int $sourceId, string $notes, ?string $referenceNo = null, ?string $movementType = null): void
    {
        $stock = PosInventoryStock::query()
            ->where('pos_product_id', $product->id)
            ->where('pos_inventory_location_id', $location->id)
            ->lockForUpdate()
            ->first();

        $stockBefore = (int) ($stock?->quantity ?? 0);
        $stockAfter = $stockBefore + $quantity;

        if ($stock) {
            $stock->forceFill(['quantity' => $stockAfter])->save();
        } else {
            PosInventoryStock::query()->create([
                'pos_product_id' => $product->id,
                'pos_inventory_location_id' => $location->id,
                'quantity' => $stockAfter,
            ]);
        }

        $product->increment('stock', $quantity);
        $product->refresh();

        PosStockMovement::query()->create([
            'pos_product_id' => $product->id,
            'pos_inventory_location_id' => $location->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'movement_type' => $movementType ?? $this->movementTypeFor($sourceType, 'in'),
            'reference_no' => $referenceNo,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
        ]);
    }

    private function deductStock(PosProduct $product, PosInventoryLocation $location, int $quantity, string $sourceType, int $sourceId, string $notes, ?string $referenceNo = null, ?string $movementType = null): void
    {
        $stock = PosInventoryStock::query()
            ->where('pos_product_id', $product->id)
            ->where('pos_inventory_location_id', $location->id)
            ->lockForUpdate()
            ->first();

        $stockBefore = (int) ($stock?->quantity ?? 0);
        $stockAfter = max($stockBefore - $quantity, 0);

        if ($stock) {
            $stock->forceFill(['quantity' => $stockAfter])->save();
        } else {
            PosInventoryStock::query()->create([
                'pos_product_id' => $product->id,
                'pos_inventory_location_id' => $location->id,
                'quantity' => 0,
            ]);
        }

        $product->decrement('stock', $quantity);
        $product->refresh();

        PosStockMovement::query()->create([
            'pos_product_id' => $product->id,
            'pos_inventory_location_id' => $location->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'movement_type' => $movementType ?? $this->movementTypeFor($sourceType, 'out'),
            'reference_no' => $referenceNo,
            'quantity' => -$quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
        ]);
    }

    private function movementTypeFor(string $sourceType, string $direction): string
    {
        return match (true) {
            $sourceType === PosStockReceipt::class => 'RECEIPT',
            $sourceType === PosStockTransfer::class => $direction === 'in' ? 'TRANSFER_IN' : 'TRANSFER_OUT',
            $sourceType === PosStockCount::class => 'ADJUSTMENT',
            default => 'ADJUSTMENT',
        };
    }

    private function nextReceiptNo(): string
    {
        return 'RCP-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function nextTransferNo(): string
    {
        return 'TRF-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function nextCountNo(): string
    {
        return 'OPC-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
