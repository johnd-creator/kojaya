<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'organization_id' => $this->organization_id,
            'period' => $this->period,
            'basic_salary' => (float) $this->basic_salary,
            'total_allowance' => (float) $this->total_allowance,
            'total_deduction' => (float) $this->total_deduction,
            'tax_amount' => (float) $this->tax_amount,
            'bpjs_amount' => (float) $this->bpjs_amount,
            'net_salary' => (float) $this->net_salary,
            'status' => $this->status,
            'is_thr' => (bool) $this->is_thr,
            'thr_amount' => (float) $this->thr_amount,
        ];
    }
}
