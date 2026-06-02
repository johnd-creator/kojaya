<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->cooperative_member_id,
            'loan_type_id' => $this->loan_type_id,
            'principal_amount' => (float) $this->principal_amount,
            'interest_rate' => (float) $this->interest_rate,
            'admin_fee' => (float) $this->admin_fee,
            'late_fee_per_day' => (float) $this->late_fee_per_day,
            'term_months' => $this->term_months,
            'installment_amount' => (float) $this->installment_amount,
            'total_interest_amount' => (float) $this->total_interest_amount,
            'total_amount' => (float) $this->total_amount,
            'outstanding_amount' => (float) $this->outstanding_amount,
            'applied_at' => $this->applied_at?->toDateString(),
            'first_due_date' => $this->first_due_date?->toDateString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'disbursed_at' => $this->disbursed_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'status' => $this->status?->value ?? $this->status,
            'reference_no' => $this->reference_no,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'member' => new MemberSelfServiceResource($this->whenLoaded('member')),
            'loan_type' => $this->whenLoaded('loanType', fn () => [
                'id' => $this->loanType?->id,
                'code' => $this->loanType?->code,
                'name' => $this->loanType?->name,
                'description' => $this->loanType?->description,
            ]),
            'installments' => LoanInstallmentResource::collection($this->whenLoaded('installments')),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at?->toDateString(),
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'reference_no' => $payment->reference_no,
            ])),
            'approval_logs' => $this->whenLoaded('approvalLogs', fn () => $this->approvalLogs->map(fn ($log): array => [
                'id' => $log->id,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ])),
        ];
    }
}
