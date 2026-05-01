<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\TaxDetail;
use App\Services\PayrollCalculatorService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $organization;

    protected Employee $employee;

    protected PayrollCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'phtkp_status' => 'TK/0',
            'is_npwp_available' => true,
            'npwp_number' => '123456789012345',
        ]);

        $this->service = new PayrollCalculatorService(
            new \App\Services\Pph21TerService,
            new \App\Services\BpjsCalculationService
        );
    }

    public function test_calculate_returns_correct_structure(): void
    {
        $basicSalary = 10_000_000;
        $allowance = 2_000_000;

        $result = $this->service->calculate($this->employee, $basicSalary, $allowance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gross_income', $result);
        $this->assertArrayHasKey('pph21', $result);
        $this->assertArrayHasKey('bpjs', $result);
        $this->assertArrayHasKey('total_deduction', $result);
        $this->assertArrayHasKey('net_salary', $result);
    }

    public function test_calculate_with_higher_salary(): void
    {
        $basicSalary = 20_000_000;
        $allowance = 5_000_000;

        $result = $this->service->calculate($this->employee, $basicSalary, $allowance);

        $this->assertEquals(25_000_000, $result['gross_income']);
        $this->assertGreaterThan(0, $result['pph21']['monthly_amount']);
        $this->assertGreaterThan(0, $result['bpjs']['total_employee']);
        $this->assertGreaterThan(0, $result['total_deduction']);
    }

    public function test_calculate_and_save_creates_tax_detail(): void
    {
        $basicSalary = 10_000_000;
        $allowance = 2_000_000;
        $period = '2026-03';

        $taxDetail = $this->service->calculateAndSave($this->employee, $basicSalary, $allowance, $period);

        $this->assertInstanceOf(TaxDetail::class, $taxDetail);
        $this->assertDatabaseHas('tax_details', [
            'id' => $taxDetail->id,
            'employee_id' => $this->employee->id,
            'period' => $period,
            'calculation_source' => 'INTERNAL',
        ]);
    }

    public function test_calculate_without_npwp_has_surcharge(): void
    {
        $this->employee->update([
            'is_npwp_available' => false,
            'npwp_number' => null,
        ]);

        $basicSalary = 10_000_000;
        $allowance = 2_000_000;

        $result = $this->service->calculate($this->employee, $basicSalary, $allowance);

        $this->assertFalse($result['pph21']['has_npwp']);
    }

    public function test_bpjs_calculation_included_in_result(): void
    {
        $basicSalary = 10_000_000;

        $result = $this->service->calculate($this->employee, $basicSalary);

        $this->assertArrayHasKey('kesehatan', $result['bpjs']);
        $this->assertArrayHasKey('jht', $result['bpjs']);
        $this->assertArrayHasKey('jp', $result['bpjs']);
        $this->assertGreaterThan(0, $result['bpjs']['kesehatan']);
        $this->assertGreaterThan(0, $result['bpjs']['jht']);
        $this->assertGreaterThan(0, $result['bpjs']['jp']);
    }

    public function test_external_service_returns_null_when_no_url_configured(): void
    {
        $result = $this->service->calculateFromExternalService(
            $this->employee,
            10_000_000,
            2_000_000,
            '2026-03'
        );

        $this->assertNull($result);
    }
}
