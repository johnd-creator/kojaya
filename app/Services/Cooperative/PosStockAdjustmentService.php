<?php

namespace App\Services\Cooperative;

use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosStockAdjustmentService
{
    public function __construct(private readonly PosProductAccessService $productAccess) {}

    public function adjust(PosProduct $product, string $movementType, int $quantity, ?string $notes = null, ?User $user = null): PosStockMovement
    {
        if ($user !== null) {
            $this->productAccess->assertCanOperate($user, $product);
        }

        return DB::transaction(function () use ($product, $movementType, $quantity, $notes): PosStockMovement {
            $product = PosProduct::query()->lockForUpdate()->findOrFail($product->id);
            $stockBefore = $product->stock;
            $delta = $movementType === 'ADJUSTMENT_OUT' ? -$quantity : $quantity;
            $stockAfter = $stockBefore + $delta;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock adjustment cannot make stock negative.',
                ]);
            }

            $product->forceFill(['stock' => $stockAfter])->save();

            return PosStockMovement::query()->create([
                'pos_product_id' => $product->id,
                'movement_type' => $movementType,
                'quantity' => $delta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $notes,
            ]);
        });
    }
}
