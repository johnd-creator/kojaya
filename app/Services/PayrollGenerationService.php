<?php

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\OvertimePayment;
use App\Models\Payroll;
use App\Models\PayrollComponent;
use Illuminate\Support\Facades\DB;

class PayrollGenerationService
{
    public function __construct(
        private readonly Pph21TerService $pph21Service,
        private readonly BpjsCalculationService $bpjsService,
        private readonly OvertimeCalculationService $overtimeService,
    ) {}

    /**
     * @return array{generated: int, skipped: int}
     */
    public function generateForOrganization(string $organizationId, string $period): array
    {
        $generated = 0;
        $skipped = 0;

        DB::transaction(function () use ($organizationId, $period, &$generated, &$skipped): void {
            Employee::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'ACTIVE')
                ->orderBy('id')
                ->chunkById(100, function ($employees) use ($period, &$generated, &$skipped): void {
                    foreach ($employees as $employee) {
                        if ($this->payrollExists($employee, $period)) {
                            $skipped++;

                            continue;
                        }

                        $this->generateForEmployee($employee, $period);
                        $generated++;
                    }
                });
        });

        return [
            'generated' => $generated,
            'skipped' => $skipped,
        ];
    }

    private function payrollExists(Employee $employee, string $period): bool
    {
        return Payroll::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->exists();
    }

    private function generateForEmployee(Employee $employee, string $period): Payroll
    {
        $basicSalary = (float) ($employee->basic_salary ?? 0);

        $pph21Result = $this->pph21Service->calculate($employee, $basicSalary, 0, $period);
        $bpjsResult = $this->bpjsService->calculate($basicSalary);

        $hourlyRate = $this->overtimeService->calculateHourlyRate($employee);
        $overtimeResult = $this->overtimeService->calculateTotalOvertimeForPeriod($employee->id, $period, $hourlyRate);

        $totalBpjsEmployee = $bpjsResult['total_employee_deduction'];
        $taxAmount = $pph21Result['monthly_tax'];
        $overtimeAmount = $overtimeResult['total_amount'];
        $totalAllowance = $overtimeAmount;
        $netSalary = $basicSalary + $totalAllowance - $totalBpjsEmployee - $taxAmount;

        $payroll = Payroll::query()->create([
            'employee_id' => $employee->id,
            'organization_id' => $employee->organization_id,
            'period' => $period,
            'basic_salary' => $basicSalary,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalBpjsEmployee,
            'tax_amount' => $taxAmount,
            'bpjs_amount' => $totalBpjsEmployee,
            'net_salary' => $netSalary,
            'status' => PayrollStatus::Draft->value,
            'pph21_calculation_breakdown' => $pph21Result,
            'bpjs_kesehatan_amount' => $bpjsResult['bpjs_kesehatan']['employee'],
            'bpjs_jht_amount' => $bpjsResult['bpjs_jht']['employee'],
            'bpjs_jp_amount' => $bpjsResult['bpjs_jp']['employee'],
            'bpjs_jkk_amount' => $bpjsResult['bpjs_jkk']['amount'],
            'bpjs_jkm_amount' => $bpjsResult['bpjs_jkm']['amount'],
            'bpjs_calculation_breakdown' => $bpjsResult,
        ]);

        $this->createComponents($payroll, $basicSalary, $bpjsResult, $taxAmount, $overtimeAmount, $overtimeResult);
        $this->createOvertimePayments($payroll, $overtimeAmount, $overtimeResult);

        return $payroll;
    }

    private function createComponents(Payroll $payroll, float $basicSalary, array $bpjsResult, float $taxAmount, float $overtimeAmount, array $overtimeResult): void
    {
        PayrollComponent::query()->create([
            'payroll_id' => $payroll->id,
            'type' => 'EARNING',
            'description' => 'Gaji Pokok',
            'amount' => $basicSalary,
        ]);

        if ($overtimeAmount > 0) {
            PayrollComponent::query()->create([
                'payroll_id' => $payroll->id,
                'type' => 'EARNING',
                'description' => 'Lembur ('.$overtimeResult['total_hours'].' jam)',
                'amount' => $overtimeAmount,
            ]);
        }

        foreach ($this->deductionComponents($payroll, $bpjsResult, $taxAmount) as $component) {
            PayrollComponent::query()->create($component);
        }
    }

    /**
     * @return list<array{payroll_id: int, type: string, description: string, amount: float|int}>
     */
    private function deductionComponents(Payroll $payroll, array $bpjsResult, float $taxAmount): array
    {
        return [
            ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'BPJS Kesehatan (1%)', 'amount' => -$bpjsResult['bpjs_kesehatan']['employee']],
            ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'JHT (2%)', 'amount' => -$bpjsResult['bpjs_jht']['employee']],
            ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'JP (1%)', 'amount' => -$bpjsResult['bpjs_jp']['employee']],
            ['payroll_id' => $payroll->id, 'type' => 'TAX', 'description' => 'PPh 21 TER', 'amount' => -$taxAmount],
        ];
    }

    private function createOvertimePayments(Payroll $payroll, float $overtimeAmount, array $overtimeResult): void
    {
        if ($overtimeAmount <= 0) {
            return;
        }

        foreach ($overtimeResult['breakdown'] as $otBreakdown) {
            OvertimePayment::query()->create([
                'payroll_id' => $payroll->id,
                'overtime_request_id' => $otBreakdown['request_id'] ?? null,
                'hours' => $otBreakdown['hours'],
                'hourly_rate' => $otBreakdown['hourly_rate'],
                'multiplier' => $otBreakdown['multiplier'],
                'amount' => $otBreakdown['amount'],
            ]);
        }
    }
}
