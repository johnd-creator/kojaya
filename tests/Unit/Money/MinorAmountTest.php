<?php

namespace Tests\Unit\Money;

use App\Support\Money\MinorAmount;
use PHPUnit\Framework\TestCase;

class MinorAmountTest extends TestCase
{
    public function test_10000_from_decimal_produces_1000000(): void
    {
        $this->assertSame(1000000, MinorAmount::fromDecimal(10000));
        $this->assertSame(1000000, MinorAmount::fromDecimal(10000.0));
        $this->assertSame(1000000, MinorAmount::fromDecimal('10000.00'));
    }

    public function test_10000_01_differs_by_one_minor_unit(): void
    {
        $this->assertNotSame(
            MinorAmount::fromDecimal('10000.00'),
            MinorAmount::fromDecimal('10000.01')
        );
    }

    public function test_negative_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MinorAmount::fromDecimal(-1);
    }

    public function test_invalid_decimal_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MinorAmount::fromDecimal('not-a-number');
    }

    public function test_empty_string_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MinorAmount::fromDecimal('');
    }

    public function test_more_than_two_decimals_truncates_to_two(): void
    {
        // bcadd with scale 2 truncates (not rounds): 100.999 → 100.99
        $this->assertSame(10099, MinorAmount::fromDecimal('100.999'));
        // 100.999999 truncates same way
        $this->assertSame(10099, MinorAmount::fromDecimal('100.999999'));
    }

    public function test_leading_zero_preserved_in_to_decimal_string(): void
    {
        $this->assertSame('0.50', MinorAmount::toDecimalString(50));
        $this->assertSame('1.05', MinorAmount::toDecimalString(105));
    }

    public function test_float_representation_0_1_plus_0_2(): void
    {
        $this->assertTrue(MinorAmount::equals(0.1 + 0.2, '0.30'));
    }

    public function test_different_but_similar_formats_are_equal(): void
    {
        $this->assertTrue(MinorAmount::equals(10000, '10000.00'));
        $this->assertTrue(MinorAmount::equals(10000.0, '10000'));
    }

    public function test_different_by_one_minor_unit_are_not_equal(): void
    {
        $this->assertFalse(MinorAmount::equals('10000.00', '10000.01'));
    }

    public function test_greater_than(): void
    {
        $this->assertTrue(MinorAmount::greaterThan('100.01', '100.00'));
        $this->assertFalse(MinorAmount::greaterThan('100.00', '100.01'));
    }

    public function test_compare(): void
    {
        $this->assertSame(0, MinorAmount::compare('100.00', '100.00'));
        $this->assertSame(-1, MinorAmount::compare('99.99', '100.00'));
        $this->assertSame(1, MinorAmount::compare('100.01', '100.00'));
    }

    public function test_to_decimal_string_round_trip(): void
    {
        $minor = MinorAmount::fromDecimal('12345.67');

        $this->assertSame(1234567, $minor);
        $this->assertSame('12345.67', MinorAmount::toDecimalString($minor));
    }
}
