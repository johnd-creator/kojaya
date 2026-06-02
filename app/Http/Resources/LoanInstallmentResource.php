<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'installment_no' => $this->installment_no,
            'due_date' => $this->due_date?->toDateString(),
            'principal_amount' => (float) $this->principal_amount,
            'interest_amount' => (float) $this->interest_amount,
            'fee_amount' => (float) $this->fee_amount,
            'penalty_amount' => (float) $this->penalty_amount,
            'amount_due' => (float) $this->amount_due,
            'amount_paid' => (float) $this->amount_paid,
            'paid_at' => $this->paid_at?->toDateString(),
            'status' => $this->status?->value ?? $this->status,
        ];
    }
}
