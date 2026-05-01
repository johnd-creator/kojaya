<?php

namespace App\Reports\Compliance;

use App\Models\EmployeeCertificate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CertificateComplianceReport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $this->sanitizeFilters([
            'status' => null,
            'organization_id' => null,
            'expiry_days' => 90,
        ]);
    }

    public function title(): string
    {
        return 'Certificate Compliance Report';
    }

    public function collection()
    {
        $query = EmployeeCertificate::with('employee');

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['organization_id'])) {
            $query->whereHas('employee', function ($q) {
                return $q->where('organization_id', $this->filters['organization_id']);
            });
        }

        return $query->orderBy('expiry_date')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Employee ID',
            'Certificate Type',
            'Certificate Number',
            'Issue Date',
            'Expiry Date',
            'Days Until Expiry',
            'Status',
            'Issuing Authority',
            'Department',
        ];
    }

    public function map($certificate): array
    {
        $daysUntilExpiry = null;
        $status = 'Valid';

        if ($certificate->expiry_date) {
            $daysUntilExpiry = now()->diffInDays($certificate->expiry_date);

            if ($daysUntilExpiry < 0) {
                $status = 'Expired';
            } elseif ($daysUntilExpiry <= 30) {
                $status = 'Expiring Soon';
            } else {
                $status = 'Valid';
            }
        }

        return [
            $certificate->employee->name ?? 'N/A',
            $certificate->employee->employee_number ?? 'N/A',
            $certificate->certificate_type,
            $certificate->certificate_number,
            $certificate->issue_date->format('d M Y'),
            $certificate->expiry_date?->format('d M Y') ?? 'N/A',
            $daysUntilExpiry,
            $status,
            $certificate->issuing_authority ?? 'N/A',
            $certificate->employee->department ?? 'N/A',
        ];
    }
}
