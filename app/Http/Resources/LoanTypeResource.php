<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'interest_rate' => (float) $this->interest_rate,
            'admin_fee' => (float) $this->admin_fee,
            'late_fee_per_day' => (float) $this->late_fee_per_day,
            'min_amount' => (float) $this->min_amount,
            'max_amount' => (float) $this->max_amount,
            'min_term_months' => $this->min_term_months,
            'max_term_months' => $this->max_term_months,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
