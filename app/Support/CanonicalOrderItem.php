<?php

namespace App\Support;

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
            foreach ($this->customization as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function amountMinor(): int
    {
        return (int) bcmul($this->lineTotal(), '100', 0);
    }

    /**
     * Canonicalize raw items: aggregate duplicates, sort by pos_product_id.
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

            if (! isset($aggregated[$productId])) {
                $unitPrice = self::normalizeDecimal($item['unit_price'] ?? $item['line_total'] ?? '0');
                $customization = null;
                if (isset($item['sugar_level']) || isset($item['ice_level']) || isset($item['cup_size'])) {
                    $customization = array_filter([
                        'sugar_level' => $item['sugar_level'] ?? null,
                        'ice_level' => $item['ice_level'] ?? null,
                        'cup_size' => $item['cup_size'] ?? null,
                    ], fn ($v): bool => $v !== null);
                }

                $aggregated[$productId] = [
                    'posProductId' => $productId,
                    'quantity' => 0,
                    'unitPrice' => $unitPrice,
                    'customization' => $customization,
                ];
            }

            $aggregated[$productId]['quantity'] += (int) ($item['quantity'] ?? 1);
        }

        $list = array_map(
            static fn (array $entry): self => new self(
                posProductId: $entry['posProductId'],
                quantity: $entry['quantity'],
                unitPrice: $entry['unitPrice'],
                customization: $entry['customization'],
            ),
            array_values($aggregated)
        );

        usort($list, static fn (self $a, self $b): int => $a->posProductId <=> $b->posProductId);

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

        if (is_int($value)) {
            return bcadd((string) $value, '0.00', 2);
        }

        if (is_float($value)) {
            return bcadd((string) round($value, 2), '0', 2);
        }

        $string = (string) $value;
        if (! is_numeric($string)) {
            return '0.00';
        }

        return bcadd($string, '0.00', 2);
    }
}
