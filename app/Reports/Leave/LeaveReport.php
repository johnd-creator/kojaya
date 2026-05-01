<?php

namespace App\Reports\Leave;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveReport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $this->sanitizeFilters([
            'year' => now()->year,
            'type' => null,
            'status' => null,
            'organization_id' => null,
        ]);
    }

    public function title(): string
    {
        $type = $this->filters['type'] ? ucfirst($this->filters['type']) : 'All';

        return "Leave Report - {$type}";
    }

    public function collection()
    {
        $query = Leave::with('employee.employee');

        if (isset($this->filters['year'])) {
            $query->whereYear('start_date', $this->filters['year']);
        }

        if (isset($this->filters['type'])) {
            $query->where('leave_type', $this->filters['type']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['organization_id'])) {
            $query->whereHas('employee', function ($q) {
                return $q->where('organization_id', $this->filters['organization_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Employee ID',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days',
            'Reason',
            'Status',
            'Department',
        ];
    }

    public function map($leave): array
    {
        return [
            $leave->employee->name ?? 'N/A',
            $leave->employee->employee_number ?? 'N/A',
            $leave->leave_type,
            $leave->start_date->format('d M Y'),
            $leave->end_date->format('d M Y'),
            $leave->days,
            $leave->reason,
            $leave->status,
            $leave->employee->department ?? 'N/A',
        ];
    }
}
