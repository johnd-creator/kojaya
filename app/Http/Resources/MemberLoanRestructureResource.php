<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberLoanRestructureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'proposed_principal_amount' => (float) $this->proposed_principal_amount,
            'proposed_interest_rate' => (float) $this->proposed_interest_rate,
            'proposed_term_months' => $this->proposed_term_months,
            'proposed_first_due_date' => $this->proposed_first_due_date?->toDateString(),
            'admin_notes' => $this->admin_notes,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
