<?php

namespace App\Reports\Compliance;

use App\Models\MedicalCheckup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class McuComplianceReport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $this->sanitizeFilters([
            'result' => null,
            'organization_id' => null,
            'due_days' => 30,
        ]);
    }

    public function title(): string
    {
        return 'MCU Compliance Report';
    }

    public function collection()
    {
        $query = MedicalCheckup::with('employee');

        if (isset($this->filters['result'])) {
            $query->where('result', $this->filters['result']);
        }

        if (isset($this->filters['organization_id'])) {
            $query->whereHas('employee', function ($q) {
                return $q->where('organization_id', $this->filters['organization_id']);
            });
        }

        return $query->orderBy('next_checkup_date')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Employee ID',
            'Checkup Date',
            'Next Checkup Date',
            'Days Until Due',
            'Result',
            'Fit to Work',
            'Doctor',
            'Clinic',
            'Department',
        ];
    }

    public function map($mcu): array
    {
        $daysUntilDue = null;

        if ($mcu->next_checkup_date) {
            $daysUntilDue = now()->diffInDays($mcu->next_checkup_date);
        }

        $resultLabel = match ($mcu->result) {
            'FIT' => 'Fit',
            'FIT_WITH_RESTRICTION' => 'Fit with Restriction',
            'UNFIT' => 'Unfit',
            default => $mcu->result,
        };

        return [
            $mcu->employee->name ?? 'N/A',
            $mcu->employee->employee_number ?? 'N/A',
            $mcu->checkup_date->format('d M Y'),
            $mcu->next_checkup_date?->format('d M Y') ?? 'Not Set',
            $daysUntilDue,
            $resultLabel,
            $mcu->fit_to_work ? 'Yes' : 'No',
            $mcu->doctor_name ?? 'N/A',
            $mcu->clinic_name ?? 'N/A',
            $mcu->employee->department ?? 'N/A',
        ];
    }
}
