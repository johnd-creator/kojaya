<?php

namespace App\Services\Cooperative;

use App\Models\MemberPaymentIntent;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberOrderReservationService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function reserve(array $items): array
    {
        return DB::transaction(function () use ($items): array {
            $location = app(PosInventoryService::class)->resolveLocationFor();
            app(PosInventoryService::class)->syncDefaultLocationStocks($location->id);

            $reservedItems = [];
            foreach ($items as $item) {
                $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                $quantity = (int) $item['quantity'];
                $stock = PosInventoryStock::query()
                    ->where('pos_product_id', $product->id)
                    ->where('pos_inventory_location_id', $location->id)
                    ->lockForUpdate()
                    ->first();

                $available = (int) ($stock?->quantity ?? 0) - (int) ($stock?->reserved ?? 0);
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak cukup (tersedia {$available}).",
                    ]);
                }

                $stock?->increment('reserved', $quantity);
                $reservedItems[] = [
                    ...$item,
                    'reservation_location_id' => $location->id,
                ];
            }

            return $reservedItems;
        });
    }

    public function consume(MemberPaymentIntent $intent): void
    {
        $this->adjust($intent, 'reservation_consumed_at');
    }

    public function release(MemberPaymentIntent $intent): void
    {
        $this->adjust($intent, 'reservation_released_at');
    }

    private function adjust(MemberPaymentIntent $intent, string $stateKey): void
    {
        $metadata = $intent->metadata ?? [];
        if (isset($metadata['reservation_consumed_at']) || isset($metadata['reservation_released_at'])) {
            return;
        }

        $items = $metadata['items'] ?? [];
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['reservation_location_id'], $item['pos_product_id'])) {
                continue;
            }

            $stock = PosInventoryStock::query()
                ->where('pos_product_id', $item['pos_product_id'])
                ->where('pos_inventory_location_id', $item['reservation_location_id'])
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->forceFill([
                    'reserved' => max((int) $stock->reserved - (int) ($item['quantity'] ?? 0), 0),
                ])->save();
            }
        }

        $metadata[$stateKey] = now()->toISOString();
        $intent->forceFill(['metadata' => $metadata])->save();
    }
}
