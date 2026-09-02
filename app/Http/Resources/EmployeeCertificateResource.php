<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'certificate_type' => $this->certificate_type->value,
            'certificate_type_label' => $this->certificate_type->label(),
            'certificate_number' => $this->certificate_number,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'issuing_authority' => $this->issuing_authority,
            'document_path' => $this->document_path,
            'document_url' => null,
            'has_document' => ! empty($this->document_path),
            'document_download_url' => $this->document_path ? route('api.employees.certificates.document', [
                'employeeId' => $this->employee_id,
                'id' => $this->id,
            ]) : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'is_expiring' => $this->isExpiring(),
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
