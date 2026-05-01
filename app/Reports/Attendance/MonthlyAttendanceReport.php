<?php

namespace App\Reports\Attendance;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyAttendanceReport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $this->sanitizeFilters([
            'month' => now()->format('Y-m'),
            'organization_id' => null,
            'department' => null,
        ]);
    }

    public function title(): string
    {
        return 'Monthly Attendance Report';
    }

    public function collection()
    {
        $query = Attendance::with('employee');

        if (isset($this->filters['month'])) {
            $query->whereMonth('date', substr($this->filters['month'], 5, 2))
                ->whereYear('date', substr($this->filters['month'], 0, 4));
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

        return $query->orderBy('date')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Employee',
            'Employee ID',
            'Check In',
            'Check Out',
            'Work Hours',
            'Status',
            'Department',
        ];
    }

    public function map($attendance): array
    {
        $checkIn = $attendance->check_in?->format('H:i');
        $checkOut = $attendance->check_out?->format('H:i');

        $workHours = null;
        if ($checkIn && $checkOut) {
            $start = \Carbon\Carbon::parse($attendance->check_in);
            $end = \Carbon\Carbon::parse($attendance->check_out);
            $workHours = $start->diffInHours($end);
        }

        return [
            $attendance->date->format('d M Y'),
            $attendance->employee->name ?? 'N/A',
            $attendance->employee->employee_number ?? 'N/A',
            $checkIn,
            $checkOut,
            $workHours,
            $attendance->status ?? 'Present',
            $attendance->employee->department ?? 'N/A',
        ];
    }
}
