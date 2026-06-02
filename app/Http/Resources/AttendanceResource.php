<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'organization_id' => $this->organization_id,
            'date' => $this->date?->toDateString(),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'clock_in_latitude' => $this->clock_in_latitude,
            'clock_in_longitude' => $this->clock_in_longitude,
            'clock_out_latitude' => $this->clock_out_latitude,
            'clock_out_longitude' => $this->clock_out_longitude,
            'status' => $this->status,
            'mobile_audit' => $this->mobile_audit,
        ];
    }
}
