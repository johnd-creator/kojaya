<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TaxDetail;
use Illuminate\Support\Facades\Http;

class PayrollCalculatorService
{
    public function __construct(
        protected Pph21TerService $pph21Service,
        protected BpjsCalculationService $bpjsService
    ) {}

    public function calculate(Employee $employee, float $basicSalary, float $allowance = 0, ?string $period = null): array
    {
        $pph21Result = $this->pph21Service->calculate($employee, $basicSalary, $allowance);
        $bpjsResult = $this->bpjsService->calculate($basicSalary);

        return [
            'gross_income' => $pph21Result['monthly_gross'],
            'pph21' => [
                'monthly_amount' => $pph21Result['monthly_tax'],
                'annual_amount' => $pph21Result['annual_tax'],
                'breakdown' => $pph21Result['breakdown'],
                'ptkp_status' => $pph21Result['ptkp_status'],
                'has_npwp' => $pph21Result['has_npwp'],
            ],
            'bpjs' => [
                'kesehatan' => $bpjsResult['bpjs_kesehatan']['employee'],
                'jht' => $bpjsResult['bpjs_jht']['employee'],
                'jp' => $bpjsResult['bpjs_jp']['employee'],
                'total_employee' => $bpjsResult['total_employee_deduction'],
                'total_employer' => $bpjsResult['total_employer_contribution'],
                'breakdown' => $bpjsResult['breakdown'],
            ],
            'total_deduction' => $pph21Result['monthly_tax'] + $bpjsResult['total_employee_deduction'],
            'net_salary' => $pph21Result['monthly_gross'] - ($pph21Result['monthly_tax'] + $bpjsResult['total_employee_deduction']),
        ];
    }

    public function calculateAndSave(Employee $employee, float $basicSalary, float $allowance, string $period, ?int $payrollId = null): TaxDetail
    {
        $result = $this->calculate($employee, $basicSalary, $allowance, $period);

        return TaxDetail::create([
            'employee_id' => $employee->id,
            'payroll_id' => $payrollId,
            'period' => $period,
            'calculation_type' => 'MONTHLY',
            'gross_income' => $result['gross_income'],
            'biaya_jabatan' => $result['pph21']['breakdown'][0]['taxable_amount'] ?? 0,
            'netto' => $result['pph21']['monthly_amount'] * 12,
            'ptkp_status' => $result['pph21']['ptkp_status'],
            'ptkp_amount' => 0,
            'pkp' => 0,
            'pph21_annual' => $result['pph21']['annual_amount'],
            'pph21_monthly' => $result['pph21']['monthly_amount'],
            'npwp_available' => $result['pph21']['has_npwp'],
            'no_npwp_surcharge_percent' => 0,
            'final_pph21_amount' => $result['pph21']['monthly_amount'],
            'bpjs_kesehatan_amount' => $result['bpjs']['kesehatan'],
            'bpjs_jht_amount' => $result['bpjs']['jht'],
            'bpjs_jp_amount' => $result['bpjs']['jp'],
            'bpjs_jkk_amount' => 0,
            'bpjs_jkm_amount' => 0,
            'total_bpjs' => $result['bpjs']['total_employee'],
            'calculation_breakdown' => $result,
            'calculation_source' => 'INTERNAL',
        ]);
    }

    public function calculateFromExternalService(Employee $employee, float $basicSalary, float $allowance, string $period): ?array
    {
        $externalUrl = config('services.tax_calculator.url');

        if (! $externalUrl) {
            return null;
        }

        try {
            $response = Http::timeout(10)->post($externalUrl.'/api/calculate', [
                'employee' => [
                    'phtkp_status' => $employee->phtkp_status,
                    'is_npwp_available' => $employee->is_npwp_available,
                    'npwp_number' => $employee->npwp_number,
                ],
                'salary' => [
                    'basic' => $basicSalary,
                    'allowance' => $allowance,
                ],
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            report($e);
        }

        return null;
    }

    public function calculateFromExternalAndSave(Employee $employee, float $basicSalary, float $allowance, string $period, ?int $payrollId = null): ?TaxDetail
    {
        $result = $this->calculateFromExternalService($employee, $basicSalary, $allowance, $period);

        if (! $result) {
            return null;
        }

        $taxDetail = TaxDetail::create([
            'employee_id' => $employee->id,
            'payroll_id' => $payrollId,
            'period' => $period,
            'calculation_type' => 'MONTHLY',
            'gross_income' => $result['gross_income'] ?? $basicSalary + $allowance,
            'biaya_jabatan' => $result['biaya_jabatan'] ?? 0,
            'netto' => $result['netto'] ?? 0,
            'ptkp_status' => $result['ptkp_status'] ?? $employee->phtkp_status,
            'ptkp_amount' => $result['ptkp_amount'] ?? 0,
            'pkp' => $result['pkp'] ?? 0,
            'pph21_annual' => $result['annual_tax'] ?? 0,
            'pph21_monthly' => $result['monthly_tax'] ?? 0,
            'npwp_available' => $result['has_npwp'] ?? true,
            'no_npwp_surcharge_percent' => $result['no_npwp_surcharge'] ?? 0,
            'final_pph21_amount' => $result['monthly_tax'] ?? 0,
            'bpjs_kesehatan_amount' => $result['bpjs_kesehatan'] ?? 0,
            'bpjs_jht_amount' => $result['bpjs_jht'] ?? 0,
            'bpjs_jp_amount' => $result['bpjs_jp'] ?? 0,
            'bpjs_jkk_amount' => $result['bpjs_jkk'] ?? 0,
            'bpjs_jkm_amount' => $result['bpjs_jkm'] ?? 0,
            'total_bpjs' => $result['total_bpjs_employee'] ?? 0,
            'calculation_breakdown' => $result,
            'external_service_ref' => $result['reference_id'] ?? null,
            'calculation_source' => 'EXTERNAL',
        ]);

        return $taxDetail;
    }
}
