<?php

namespace App\Support\Money;

/**
 * Deterministic integer minor-unit representation of monetary amounts.
 *
 * All arithmetic and comparison is performed in integer minor units
 * (e.g. cents for IDR, sen for MYR). No floating-point operations are
 * used internally.
 */
final class MinorAmount
{
    /**
     * Convert a decimal value (int, float, or numeric string) to
     * integer minor units.
     *
     * Examples:
     *   10000      → 1000000
     *   10000.0    → 1000000
     *   "10000.00" → 1000000
     *   "10000.01" → 1000001
     *
     * @throws \InvalidArgumentException for negative, non-numeric, or unparseable input
     */
    public static function fromDecimal(int|float|string $value): int
    {
        $normalized = self::normalizeToFixedScale($value);

        if (bccomp($normalized, '0', 2) < 0) {
            throw new \InvalidArgumentException("Negative amount is not allowed: {$normalized}");
        }

        return (int) bcmul($normalized, '100', 0);
    }

    /**
     * Convert integer minor units back to a decimal string with 2 fixed
     * decimal places.
     */
    public static function toDecimalString(int $minor): string
    {
        return bcdiv((string) $minor, '100', 2);
    }

    /**
     * Check whether two monetary values are exactly equal in minor units.
     */
    public static function equals(int|float|string $left, int|float|string $right): bool
    {
        return self::fromDecimal($left) === self::fromDecimal($right);
    }

    /**
     * Check whether $left is greater than $right in minor units.
     */
    public static function greaterThan(int|float|string $left, int|float|string $right): bool
    {
        return self::fromDecimal($left) > self::fromDecimal($right);
    }

    /**
     * Normalize any numeric representation to a decimal string with
     * exactly 2 decimal places.
     *
     * Leading zeros in the integer part are preserved (e.g. "0.50" stays "0.50").
     *
     * @throws \InvalidArgumentException for non-numeric input
     */
    public static function normalizeToFixedScale(mixed $value): string
    {
        if (is_int($value)) {
            return bcadd((string) $value, '0.00', 2);
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new \InvalidArgumentException('Invalid float value: '.var_export($value, true));
            }

            if ($value < 0) {
                throw new \InvalidArgumentException('Negative amount is not allowed: '.var_export($value, true));
            }

            // Float input is accepted only as a legacy boundary and normalized
            // immediately to a deterministic fixed-scale decimal string.
            return number_format($value, 2, '.', '');
        }

        $string = (string) $value;
        $string = trim($string);

        if ($string === '') {
            throw new \InvalidArgumentException('Invalid numeric value: '.var_export($value, true));
        }

        if (str_starts_with($string, '-')) {
            throw new \InvalidArgumentException('Negative amount is not allowed: '.$string);
        }

        if (! preg_match('/^\d+(?:\.(\d+))?$/', $string, $matches)) {
            throw new \InvalidArgumentException('Invalid numeric value: '.var_export($value, true));
        }

        $fraction = $matches[1] ?? '';

        if (strlen($fraction) > 2) {
            throw new \InvalidArgumentException('Amount must not have more than 2 decimal places: '.$string);
        }

        [$integerPart, $decimalPart] = array_pad(explode('.', $string, 2), 2, '');
        $integerPart = ltrim($integerPart, '0');
        $integerPart = $integerPart === '' ? '0' : $integerPart;

        return $integerPart.'.'.str_pad($decimalPart, 2, '0');
    }

    /**
     * Compare $left and $right using integer minor units.
     *
     * Returns 0 if equal, -1 if $left < $right, 1 if $left > $right.
     */
    public static function compare(int|float|string $left, int|float|string $right): int
    {
        $leftMinor = self::fromDecimal($left);
        $rightMinor = self::fromDecimal($right);

        return $leftMinor <=> $rightMinor;
    }
}
