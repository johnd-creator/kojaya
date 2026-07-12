<?php

namespace App\Services\Cooperative;

use App\Models\Loan;

class LoanPageDataService
{
    /**
     * @return array<string, mixed>
     */
    public function list(Loan $loan): array
    {
        return [
            ...$this->base($loan),
            'member' => $this->memberSummary($loan),
            'loan_type' => $this->loanTypeSummary($loan),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Loan $loan): array
    {
        return [
            ...$this->list($loan),
            'installments' => $loan->relationLoaded('installments')
                ? $loan->installments->map(fn ($installment): array => [
                    'id' => $installment->id,
                    'installment_no' => $installment->installment_no,
                    'due_date' => $installment->due_date?->toDateString(),
                    'principal_amount' => (float) $installment->principal_amount,
                    'interest_amount' => (float) $installment->interest_amount,
                    'fee_amount' => (float) $installment->fee_amount,
                    'penalty_amount' => (float) $installment->penalty_amount,
                    'amount_due' => (float) $installment->amount_due,
                    'amount_paid' => (float) $installment->amount_paid,
                    'paid_at' => $installment->paid_at?->toDateString(),
                    'status' => $installment->status?->value ?? $installment->status,
                ])->values()->all()
                : [],
            'payments' => $loan->relationLoaded('payments')
                ? $loan->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'paid_at' => $payment->paid_at?->toDateString(),
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'reference_no' => $payment->reference_no,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base(Loan $loan): array
    {
        $status = $loan->status?->value ?? $loan->status;

        return [
            'id' => $loan->id,
            'member_id' => $loan->cooperative_member_id,
            'loan_type_id' => $loan->loan_type_id,
            'principal_amount' => (float) $loan->principal_amount,
            'interest_rate' => (float) $loan->interest_rate,
            'admin_fee' => (float) $loan->admin_fee,
            'late_fee_per_day' => (float) $loan->late_fee_per_day,
            'term_months' => $loan->term_months,
            'installment_amount' => (float) $loan->installment_amount,
            'total_interest_amount' => (float) $loan->total_interest_amount,
            'total_amount' => (float) $loan->total_amount,
            'outstanding_amount' => (float) $loan->outstanding_amount,
            'applied_at' => $loan->applied_at?->toDateString(),
            'first_due_date' => $loan->first_due_date?->toDateString(),
            'manager_reviewed_at' => $loan->manager_reviewed_at?->toISOString(),
            'manager_reviewed_by' => $loan->manager_reviewed_by,
            'approved_at' => $loan->approved_at?->toISOString(),
            'approved_by' => $loan->approved_by,
            'disbursed_at' => $loan->disbursed_at?->toISOString(),
            'rejected_at' => $loan->rejected_at?->toISOString(),
            'status' => $status,
            'approval_stage' => match ($status) {
                'APPLIED' => 'MANAGER_REVIEW',
                'MANAGER_APPROVED' => 'PENGURUS_FINAL_APPROVAL',
                'APPROVED' => 'READY_FOR_DISBURSEMENT',
                'ACTIVE' => 'DISBURSED',
                default => null,
            },
            'reference_no' => $loan->reference_no,
            'purpose' => $loan->purpose,
            'notes' => $loan->notes,
            'rejection_reason' => $loan->rejection_reason,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function memberSummary(Loan $loan): ?array
    {
        $member = $loan->member;
        if (! $member) {
            return null;
        }

        return [
            'id' => $member->id,
            'member_no' => $member->member_no,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $this->mask($member->phone),
            'status' => $member->status,
            'organization' => $member->relationLoaded('organization') && $member->organization ? [
                'id' => $member->organization->id,
                'name' => $member->organization->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loanTypeSummary(Loan $loan): ?array
    {
        if (! $loan->relationLoaded('loanType') || ! $loan->loanType) {
            return null;
        }

        return [
            'id' => $loan->loanType->id,
            'code' => $loan->loanType->code,
            'name' => $loan->loanType->name,
            'description' => $loan->loanType->description,
        ];
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $visible = min(4, strlen($value));

        return str_repeat('*', max(strlen($value) - $visible, 0)).substr($value, -$visible);
    }
}
