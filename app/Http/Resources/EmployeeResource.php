<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'hire_date' => $this->hire_date?->toDateString(),
            'organization_id' => $this->organization_id,
            'department' => $this->whenLoaded('department'),
            'position' => $this->whenLoaded('position'),
        ];
    }
}
