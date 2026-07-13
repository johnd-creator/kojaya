<?php

namespace App\Support;

use App\Support\Money\MinorAmount;

/**
 * Immutable canonical representation of a single order line after aggregation.
 *
 * Duplicate product IDs are merged into one entry with summed quantity.
 * The list of these objects is the single source of truth used for
 * fingerprinting, reservation, metadata storage, response formatting,
 * and settlement.
 *
 * @immutable
 */
final class CanonicalOrderItem
{
    public function __construct(
        public readonly int $posProductId,
        public readonly int $quantity,
        public readonly string $unitPrice,
        public readonly ?string $reservationLocationId = null,
        public readonly ?array $customization = null,
        public readonly ?array $productSnapshot = null,
    ) {}

    public function lineTotal(): string
    {
        return bcmul($this->unitPrice, (string) $this->quantity, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'pos_product_id' => $this->posProductId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal(),
        ];

        if ($this->reservationLocationId !== null) {
            $result['reservation_location_id'] = $this->reservationLocationId;
        }

        if ($this->customization !== null) {
            $result['customization'] = $this->customization;
        }

        if ($this->productSnapshot !== null) {
            $result['product'] = $this->productSnapshot;
        }

        return $result;
    }

    public function amountMinor(): int
    {
        return MinorAmount::fromDecimal($this->lineTotal());
    }

    /**
     * Stable fingerprint for a customization set. Keys are sorted so that
     * identical customizations always produce the same key regardless of
     * insertion order.
     *
     * @param  array<string, mixed>|null  $customization
     */
    public static function customizationKey(?array $customization): string
    {
        if ($customization === null || $customization === []) {
            return '';
        }

        $normalized = [];
        $keys = array_keys($customization);
        sort($keys);

        foreach ($keys as $key) {
            $normalized[$key] = self::normalizeCustomizationValue($customization[$key]);
        }

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param  mixed  $value
     */
    private static function normalizeCustomizationValue($value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(trim((string) $value));
    }

    /**
     * Aggregate key combining product ID, customization, and unit-price
     * snapshot. Two lines are only merged when all three match.
     */
    public function aggregateKey(): string
    {
        return sprintf(
            '%d:%s:%s',
            $this->posProductId,
            self::customizationKey($this->customization),
            $this->unitPrice,
        );
    }

    /**
     * Canonicalize raw items: aggregate duplicates by product + customization
     * + unit-price, sort by pos_product_id.
     *
     * Two lines are only merged when product, customization, and unit-price
     * are all identical. Different sugar_level or cup_size produce separate
     * canonical order lines.
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return list<self>
     */
    public static function canonicalise(array $rawItems): array
    {
        $aggregated = [];

        foreach ($rawItems as $item) {
            if (! is_array($item) || ! isset($item['pos_product_id'])) {
                continue;
            }

            $productId = (int) $item['pos_product_id'];
            $unitPrice = self::normalizeDecimal($item['unit_price'] ?? $item['line_total'] ?? '0');
            $customization = null;
            if (isset($item['sugar_level']) || isset($item['ice_level']) || isset($item['cup_size'])) {
                $customization = array_filter([
                    'sugar_level' => $item['sugar_level'] ?? null,
                    'ice_level' => $item['ice_level'] ?? null,
                    'cup_size' => $item['cup_size'] ?? null,
                ], fn ($v): bool => $v !== null);
            }

            $key = sprintf('%d:%s:%s', $productId, self::customizationKey($customization), $unitPrice);

            // Capture immutable product snapshot from first occurrence
            $productSnapshot = null;
            if (isset($item['product']) && is_array($item['product'])) {
                $productSnapshot = self::extractProductSnapshot($item['product']);
            }

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'posProductId' => $productId,
                    'quantity' => 0,
                    'unitPrice' => $unitPrice,
                    'customization' => $customization,
                    'productSnapshot' => $productSnapshot,
                ];
            }

            $aggregated[$key]['quantity'] += (int) ($item['quantity'] ?? 1);
        }

        $list = array_map(
            static fn (array $entry): self => new self(
                posProductId: $entry['posProductId'],
                quantity: $entry['quantity'],
                unitPrice: $entry['unitPrice'],
                customization: $entry['customization'],
                productSnapshot: $entry['productSnapshot'] ?? null,
            ),
            array_values($aggregated)
        );

        usort($list, static function (self $a, self $b): int {
            $cmp = $a->posProductId <=> $b->posProductId;
            if ($cmp !== 0) {
                return $cmp;
            }

            return self::customizationKey($a->customization) <=> self::customizationKey($b->customization);
        });

        return $list;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawItems
     */
    public static function totalAmountMinor(array $rawItems): int
    {
        return array_reduce(
            self::canonicalise($rawItems),
            static fn (int $carry, self $item): int => $carry + $item->amountMinor(),
            0
        );
    }

    public static function normalizeDecimal(mixed $value): string
    {
        if ($value === null) {
            return '0.00';
        }

        try {
            return MinorAmount::normalizeToFixedScale($value);
        } catch (\InvalidArgumentException) {
            return '0.00';
        }
    }

    /**
     * Extract an immutable product snapshot for API response/metadata.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private static function extractProductSnapshot(array $product): array
    {
        $snapshot = [];

        foreach (['id', 'name', 'sku', 'brand', 'variant', 'image_url', 'unit', 'category', 'price', 'description'] as $field) {
            if (isset($product[$field])) {
                $snapshot[$field] = $product[$field];
            }
        }

        return $snapshot;
    }
}
