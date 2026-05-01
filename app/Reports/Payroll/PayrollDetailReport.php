<?php

namespace App\Reports\Payroll;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollDetailReport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Payroll Detail Report';
    }

    public function collection()
    {
        $query = Payroll::with(['employee', 'employee.organization']);

        if (isset($this->filters['period'])) {
            $query->where('period', $this->filters['period']);
        }

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

        return $query->orderBy('period', 'desc')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Organization',
            'Employee',
            'Employee ID',
            'Period',
            'Basic Salary',
            'Overtime Hours',
            'Overtime Pay',
            'Bonuses',
            'Deductions',
            'Net Salary',
            'Status',
            'Paid Date',
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->employee->organization->name ?? 'N/A',
            $payroll->employee->name ?? 'N/A',
            $payroll->employee->employee_number ?? 'N/A',
            $payroll->period,
            number_format($payroll->basic_salary, 0, ',', '.'),
            $payroll->overtime_hours ?? 0,
            number_format($payroll->overtime_pay, 0, ',', '.'),
            number_format($payroll->bonuses, 0, ',', '.'),
            number_format($payroll->deductions, 0, ',', '.'),
            number_format($payroll->net_salary, 0, ',', '.'),
            $payroll->status,
            $payroll->paid_at?->format('d M Y') ?? 'Pending',
        ];
    }
}
