<?php

namespace App\Reports\Payroll;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\ReportGeneratorService;

class PayslipReport extends ReportGeneratorService
{
    protected Employee $employee;

    protected Payroll $payroll;

    public function __construct(Employee $employee, Payroll $payroll)
    {
        $this->employee = $employee;
        $this->payroll = $payroll;
    }

    public function generate(): string
    {
        $data = [
            'employee' => [
                'name' => $this->employee->first_name.' '.$this->employee->last_name,
                'employee_number' => $this->employee->employee_number,
                'position' => $this->employee->position,
                'department' => $this->employee->department,
                'join_date' => $this->employee->hire_date?->format('d F Y'),
                'organization' => $this->employee->organization->name,
            ],
            'payroll' => [
                'period' => $this->payroll->period,
                'basic_salary' => number_format($this->payroll->basic_salary, 0, ',', '.'),
                'overtime_pay' => number_format($this->payroll->overtime_pay, 0, ',', '.'),
                'bonuses' => number_format($this->payroll->bonuses, 0, ',', '.'),
                'deductions' => number_format($this->payroll->deductions, 0, ',', '.'),
                'net_salary' => number_format($this->payroll->net_salary, 0, ',', '.'),
                'paid_at' => $this->payroll->paid_at?->format('d F Y H:i'),
            ],
            'earnings' => [
                'basic' => $this->payroll->basic_salary,
                'overtime' => $this->payroll->overtime_pay,
                'bonuses' => $this->payroll->bonuses,
                'total_earnings' => $this->payroll->basic_salary + $this->payroll->overtime_pay + $this->payroll->bonuses,
            ],
            'deductions' => [
                'total_deductions' => $this->payroll->deductions,
                'net_salary' => $this->payroll->net_salary,
            ],
            'generated_at' => now()->format('d F Y H:i:s'),
        ];

        $view = 'reports.payroll.payslip';

        ob_start();
        echo json_encode($data, JSON_PRETTY_PRINT);

        return ob_get_clean();
    }

    protected function validateFilters(array $rules): void
    {
        // No filters needed for payslip
    }

    public function getPdfFilename(): string
    {
        return "payslip_{$this->employee->employee_number}_{$this->payroll->period}.pdf";
    }
}
