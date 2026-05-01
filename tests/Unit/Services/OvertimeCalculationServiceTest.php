<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Services\OvertimeCalculationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class OvertimeCalculationServiceTest extends TestCase
{
    use DatabaseMigrations;

    private OvertimeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OvertimeCalculationService;
    }

    public function test_calculate_hourly_rate(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 4_400_000]);

        $hourlyRate = $this->service->calculateHourlyRate($employee);

        $expectedRate = 4_400_000 / 22 / 8;
        $this->assertEqualsWithDelta($expectedRate, $hourlyRate, 0.01);
        $this->assertEquals(25000, $hourlyRate);
    }

    public function test_calculate_overtime_payment_with_weekday_multiplier(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 4_400_000]);
        $rule = OvertimeRule::factory()->create(['multiplier' => 1.5]);

        $request = OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'total_hours' => 3,
        ]);

        $hourlyRate = $this->service->calculateHourlyRate($employee);
        $result = $this->service->calculateOvertimePayment($request, $hourlyRate);

        $this->assertEquals(3, $result['hours']);
        $this->assertEquals(25000, $result['hourly_rate']);
        $this->assertEquals(1.5, $result['multiplier']);
        $this->assertEquals(37500, $result['overtime_rate']);
        $this->assertEquals(112500, $result['amount']);
    }

    public function test_calculate_overtime_payment_with_holiday_multiplier(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 4_400_000]);
        $rule = OvertimeRule::factory()->create(['multiplier' => 2.0]);

        $request = OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'total_hours' => 5,
        ]);

        $hourlyRate = $this->service->calculateHourlyRate($employee);
        $result = $this->service->calculateOvertimePayment($request, $hourlyRate);

        $this->assertEquals(2.0, $result['multiplier']);
        $this->assertEquals(50000, $result['overtime_rate']);
        $this->assertEquals(250000, $result['amount']);
    }

    public function test_get_monthly_overtime_for_employee(): void
    {
        $employee = Employee::factory()->create();
        $rule = OvertimeRule::factory()->create();

        OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => '2026-03-15',
            'status' => 'APPROVED',
        ]);

        OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => '2026-03-20',
            'status' => 'APPROVED',
        ]);

        OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => '2026-03-25',
            'status' => 'PENDING',
        ]);

        $result = $this->service->getMonthlyOvertimeForEmployee($employee->id, '2026-03');

        $this->assertCount(2, $result);
    }

    public function test_calculate_total_overtime_for_period(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 4_400_000]);
        $rule = OvertimeRule::factory()->create(['multiplier' => 1.5, 'name' => 'Weekday OT']);

        OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => '2026-03-15',
            'total_hours' => 2,
            'status' => 'APPROVED',
        ]);

        OvertimeRequest::factory()->create([
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => '2026-03-20',
            'total_hours' => 3,
            'status' => 'APPROVED',
        ]);

        $hourlyRate = $this->service->calculateHourlyRate($employee);
        $result = $this->service->calculateTotalOvertimeForPeriod($employee->id, '2026-03', $hourlyRate);

        $this->assertEquals(5, $result['total_hours']);
        $this->assertEquals(187500, $result['total_amount']);
        $this->assertCount(2, $result['breakdown']);
        $this->assertEquals(2, $result['request_count']);
    }

    public function test_zero_overtime_when_no_requests(): void
    {
        $employee = Employee::factory()->create(['basic_salary' => 4_400_000]);

        $hourlyRate = $this->service->calculateHourlyRate($employee);
        $result = $this->service->calculateTotalOvertimeForPeriod($employee->id, '2026-03', $hourlyRate);

        $this->assertEquals(0, $result['total_hours']);
        $this->assertEquals(0, $result['total_amount']);
        $this->assertCount(0, $result['breakdown']);
        $this->assertEquals(0, $result['request_count']);
    }
}
