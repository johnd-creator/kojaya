<?php

namespace App\Reports\Payroll;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollSummaryReport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Payroll::with('employee');

        if (isset($this->filters['period_from'])) {
            $query->where('period', '>=', $this->filters['period_from']);
        }

        if (isset($this->filters['period_to'])) {
            $query->where('period', '<=', $this->filters['period_to']);
        }

        if (isset($this->filters['organization_id'])) {
            $query->whereHas('employee', function ($q) {
                return $q->where('organization_id', $this->filters['organization_id']);
            });
        }

        if (isset($this->filters['department'])) {
            $query->whereHas('employee', function ($q) {
                return $q->where('department', $this->filters['department']);
            });
        }

        return $query->orderBy('period', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Period',
            'Basic Salary',
            'Overtime Pay',
            'Bonuses',
            'Deductions',
            'Net Salary',
            'Status',
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->employee->name ?? 'N/A',
            $payroll->period,
            number_format($payroll->basic_salary, 0, ',', '.'),
            number_format($payroll->overtime_pay, 0, ',', '.'),
            number_format($payroll->bonuses, 0, ',', '.'),
            number_format($payroll->deductions, 0, ',', '.'),
            number_format($payroll->net_salary, 0, ',', '.'),
            $payroll->status,
        ];
    }
}
