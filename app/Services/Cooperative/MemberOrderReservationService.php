<?php

namespace App\Services\Cooperative;

use App\Models\MemberPaymentIntent;
use App\Models\PosInventoryStock;
use App\Models\PosProduct;
use App\Services\AuditLogService;
use App\Support\CanonicalOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberOrderReservationService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * Reserve stock for canonical items.
     *
     * Items must already be canonicalised (one row per pos_product_id,
     * sorted ascending). The same canonical list is returned with
     * reservation_location_id attached.
     *
     * @param  list<CanonicalOrderItem>  $canonicalItems
     * @return list<CanonicalOrderItem>
     */
    public function reserve(array $canonicalItems): array
    {
        return DB::transaction(function () use ($canonicalItems): array {
            $location = app(PosInventoryService::class)->resolveLocationFor();
            app(PosInventoryService::class)->syncDefaultLocationStocks($location->id);

            $reserved = [];

            foreach ($canonicalItems as $item) {
                $product = PosProduct::query()->lockForUpdate()->findOrFail($item->posProductId);
                $quantity = $item->quantity;
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

                $reserved[] = new CanonicalOrderItem(
                    posProductId: $item->posProductId,
                    quantity: $item->quantity,
                    unitPrice: $item->unitPrice,
                    reservationLocationId: (string) $location->id,
                    customization: $item->customization,
                );
            }

            return $reserved;
        });
    }

    public function consume(MemberPaymentIntent $intent): void
    {
        $this->adjust($intent, MemberPaymentIntent::RESERVATION_CONSUMED, 'reservation_consumed_at');
    }

    public function release(MemberPaymentIntent $intent): void
    {
        $this->adjust($intent, MemberPaymentIntent::RESERVATION_RELEASED, 'reservation_released_at');
    }

    public function expire(MemberPaymentIntent $intent): bool
    {
        return DB::transaction(function () use ($intent): bool {
            $locked = $this->lockIntent($intent);

            if (! $this->isExpirable($locked)) {
                return false;
            }

            $this->releaseReservedStock($locked);
            $this->markState($locked, MemberPaymentIntent::RESERVATION_EXPIRED, 'reservation_expired_at');

            return true;
        });
    }

    private function adjust(MemberPaymentIntent $intent, string $state, string $stateKey): void
    {
        DB::transaction(function () use ($intent, $state, $stateKey): void {
            $locked = $this->lockIntent($intent);
            if ($this->reservationState($locked) !== MemberPaymentIntent::RESERVATION_RESERVED) {
                return;
            }

            $this->releaseReservedStock($locked);
            $this->markState($locked, $state, $stateKey);
        });
    }

    private function lockIntent(MemberPaymentIntent $intent): MemberPaymentIntent
    {
        return MemberPaymentIntent::query()->lockForUpdate()->findOrFail($intent->id);
    }

    private function isExpirable(MemberPaymentIntent $intent): bool
    {
        return $this->reservationState($intent) === MemberPaymentIntent::RESERVATION_RESERVED
            && $intent->settled_at === null
            && strtoupper((string) $intent->gateway_status) !== 'PAID'
            && $intent->expires_at?->isPast() === true;
    }

    private function reservationState(MemberPaymentIntent $intent): ?string
    {
        if ($intent->reservation_status) {
            return $intent->reservation_status;
        }

        $metadata = $intent->metadata ?? [];
        if (isset($metadata['reservation_consumed_at'])) {
            return MemberPaymentIntent::RESERVATION_CONSUMED;
        }

        if (isset($metadata['reservation_released_at'])) {
            return MemberPaymentIntent::RESERVATION_RELEASED;
        }

        return is_array($metadata['items'] ?? null) && $metadata['items'] !== []
            ? MemberPaymentIntent::RESERVATION_RESERVED
            : null;
    }

    /**
     * Release reserved stock using canonical metadata from DB.
     * Items are sorted by pos_product_id to prevent deadlocks.
     */
    private function releaseReservedStock(MemberPaymentIntent $intent): void
    {
        $metadata = $intent->metadata ?? [];

        $items = $metadata['items'] ?? [];
        if (! is_array($items)) {
            return;
        }

        // Aggregate metadata items by pos_product_id to prevent
        // double-decrement when metadata contains duplicate rows.
        $byProductId = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['reservation_location_id'], $item['pos_product_id'])) {
                continue;
            }

            $productId = (int) $item['pos_product_id'];
            if (! isset($byProductId[$productId])) {
                $byProductId[$productId] = [
                    'pos_product_id' => $productId,
                    'reservation_location_id' => $item['reservation_location_id'],
                    'quantity' => 0,
                ];
            }

            $byProductId[$productId]['quantity'] += (int) ($item['quantity'] ?? 0);
        }

        $sortable = array_values($byProductId);
        usort($sortable, static fn (array $a, array $b): int => (int) $a['pos_product_id'] <=> (int) $b['pos_product_id']);

        foreach ($sortable as $item) {
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
    }

    private function markState(MemberPaymentIntent $intent, string $state, string $stateKey): void
    {
        $metadata = $intent->metadata ?? [];
        $metadata[$stateKey] = now()->toISOString();
        $metadata['reservation_state'] = $state;

        $attributes = [
            'metadata' => $metadata,
            'reservation_status' => $state,
        ];

        if ($state === MemberPaymentIntent::RESERVATION_EXPIRED) {
            $attributes['gateway_status'] = 'EXPIRED';
        }

        $intent->forceFill($attributes)->save();
        $this->auditLogService->log(
            'reservation.'.strtolower($state),
            'member_payment_intent',
            $intent,
            ['reason' => 'Member order reservation state transition.'],
        );
    }
}
