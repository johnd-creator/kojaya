<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalCheckupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'checkup_date' => $this->checkup_date->toDateString(),
            'next_checkup_date' => $this->next_checkup_date?->toDateString(),
            'result' => $this->result->value,
            'result_label' => $this->result->label(),
            'result_color' => $this->result->color(),
            'fit_to_work' => $this->fit_to_work,
            'notes' => $this->notes,
            'document_path' => $this->document_path,
            'document_url' => $this->document_path ? Storage::disk('public')->url($this->document_path) : null,
            'doctor_name' => $this->doctor_name,
            'clinic_name' => $this->clinic_name,
            'is_due' => $this->isDue(),
            'is_overdue' => $this->isOverdue(),
            'days_until_due' => $this->getDaysUntilDue(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
