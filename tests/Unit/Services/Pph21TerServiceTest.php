<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Services\Pph21TerService;
use Tests\TestCase;

class Pph21TerServiceTest extends TestCase
{
    private Pph21TerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Pph21TerService;
    }

    public function test_single_employee_no_dependents_with_npwp(): void
    {
        $employee = Employee::factory()->make([
            'phtkp_status' => 'TK/0',
            'is_npwp_available' => true,
            'npwp_number' => '123456789012345',
        ]);

        $result = $this->service->calculate($employee, 10_000_000, 0);

        $this->assertEquals(10_000_000, $result['monthly_gross']);
        $this->assertEquals(120_000_000, $result['annual_gross']);
        $this->assertEquals(6_000_000, $result['biaya_jabatan']);
        $this->assertEquals(54_000_000, $result['ptkp_amount']);
        $this->assertEquals(60_000_000, $result['pkp']);
        $this->assertTrue($result['has_npwp']);
        $this->assertEquals(0, $result['no_npwp_surcharge']);
    }

    public function test_single_employee_no_npwp_has_20_percent_surcharge(): void
    {
        $employee = Employee::factory()->make([
            'phtkp_status' => 'TK/0',
            'is_npwp_available' => false,
            'npwp_number' => null,
        ]);

        $result = $this->service->calculate($employee, 10_000_000, 0);
        $resultWithNpwp = $this->service->calculate(
            Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]),
            10_000_000,
            0
        );

        $this->assertFalse($result['has_npwp']);
        $this->assertEquals(20, $result['no_npwp_surcharge']);
        $this->assertEquals($resultWithNpwp['monthly_tax'] * 1.20, $result['monthly_tax']);
    }

    public function test_married_employee_with_3_children_k3(): void
    {
        $employee = Employee::factory()->make([
            'phtkp_status' => 'K/3',
            'is_npwp_available' => true,
        ]);

        $result = $this->service->calculate($employee, 10_000_000, 0);

        $this->assertEquals('K/3', $result['ptkp_status']);
        $this->assertEquals(72_000_000, $result['ptkp_amount']);
        $this->assertEquals(42_000_000, $result['pkp']);
    }

    public function test_progressive_tax_layer_1_5_percent(): void
    {
        $employee = Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]);

        $result = $this->service->calculate($employee, 5_000_000, 0);

        $this->assertEquals(60_000_000, $result['annual_gross']);
        $this->assertEquals(54_000_000, $result['ptkp_amount']);
        $this->assertEquals(3_000_000, $result['pkp']);
        $this->assertEquals(12_500, $result['monthly_tax']);
    }

    public function test_progressive_tax_layer_2_15_percent(): void
    {
        $employee = Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]);

        $result = $this->service->calculate($employee, 15_000_000, 0);

        $this->assertEquals(180_000_000, $result['annual_gross']);
        $this->assertEquals(54_000_000, $result['ptkp_amount']);
        $this->assertEquals(120_000_000, $result['pkp']);

        $expectedAnnualTax = (60_000_000 * 0.05) + (60_000_000 * 0.15);
        $this->assertEqualsWithDelta($expectedAnnualTax / 12, $result['monthly_tax'], 0.01);
    }

    public function test_biaya_jabatan_max_6_million_per_year(): void
    {
        $employee = Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]);

        $lowSalary = $this->service->calculate($employee, 5_000_000, 0);
        $highSalary = $this->service->calculate($employee, 50_000_000, 0);

        $this->assertEquals(3_000_000, $lowSalary['biaya_jabatan']);
        $this->assertEquals(6_000_000, $highSalary['biaya_jabatan']);
    }

    public function test_ptkp_amounts_for_all_statuses(): void
    {
        $cases = [
            'TK/0' => 54_000_000,
            'TK/1' => 58_500_000,
            'TK/2' => 63_000_000,
            'TK/3' => 67_500_000,
            'K/0' => 58_500_000,
            'K/1' => 63_000_000,
            'K/2' => 67_500_000,
            'K/3' => 72_000_000,
        ];

        foreach ($cases as $status => $expectedPtkp) {
            $employee = Employee::factory()->make([
                'phtkp_status' => $status,
                'is_npwp_available' => true,
            ]);

            $result = $this->service->calculate($employee, 10_000_000, 0);
            $this->assertEquals($expectedPtkp, $result['ptkp_amount'], "PTKP for {$status}");
        }
    }

    public function test_tax_breakdown_contains_progressive_layers(): void
    {
        $employee = Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]);

        $result = $this->service->calculate($employee, 20_000_000, 0);

        $this->assertIsArray($result['breakdown']);
        $this->assertGreaterThan(0, count($result['breakdown']));

        $firstLayer = $result['breakdown'][0];
        $this->assertArrayHasKey('layer', $firstLayer);
        $this->assertArrayHasKey('taxable_amount', $firstLayer);
        $this->assertArrayHasKey('rate', $firstLayer);
        $this->assertArrayHasKey('tax_amount', $firstLayer);
    }

    public function test_zero_tax_when_below_ptkp(): void
    {
        $employee = Employee::factory()->make([
            'phtkp_status' => 'K/3',
            'is_npwp_available' => true,
        ]);

        $result = $this->service->calculate($employee, 3_000_000, 0);

        $this->assertEquals(0, $result['pkp']);
        $this->assertEquals(0, $result['annual_tax']);
        $this->assertEquals(0, $result['monthly_tax']);
    }

    public function test_with_monthly_allowance(): void
    {
        $employee = Employee::factory()->make(['phtkp_status' => 'TK/0', 'is_npwp_available' => true]);

        $result = $this->service->calculate($employee, 10_000_000, 5_000_000);

        $this->assertEquals(15_000_000, $result['monthly_gross']);
        $this->assertEquals(180_000_000, $result['annual_gross']);
    }
}
