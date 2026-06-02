<?php

namespace App\Services\Cooperative;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\ApprovalLog;
use App\Models\Loan;
use App\Models\LoanRestructure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRestructureService
{
    public function __construct(private readonly LoanCalculatorService $calculator) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(Loan $loan, array $data, ?User $actor = null): LoanRestructure
    {
        return DB::transaction(function () use ($loan, $data, $actor): LoanRestructure {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Defaulted], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Restrukturisasi hanya dapat diajukan untuk pinjaman aktif atau macet.',
                ]);
            }

            $existingPending = LoanRestructure::query()
                ->where('loan_id', $loan->id)
                ->where('status', 'PENDING')
                ->exists();

            if ($existingPending) {
                throw ValidationException::withMessages([
                    'loan' => 'Masih ada pengajuan restrukturisasi yang menunggu review.',
                ]);
            }

            $restructure = LoanRestructure::query()->create([
                'loan_id' => $loan->id,
                'cooperative_member_id' => $loan->cooperative_member_id,
                'requested_by' => $actor?->id,
                'status' => 'PENDING',
                'reason' => $data['reason'],
                'proposed_principal_amount' => $data['proposed_principal_amount'] ?? $loan->outstanding_amount,
                'proposed_interest_rate' => $data['proposed_interest_rate'] ?? $loan->interest_rate,
                'proposed_term_months' => $data['proposed_term_months'] ?? $loan->term_months,
                'proposed_first_due_date' => $data['proposed_first_due_date'] ?? now()->addMonth()->toDateString(),
            ]);

            $this->log($restructure, null, 'PENDING', $actor, 'Pengajuan restrukturisasi pinjaman dibuat.');

            return $restructure->load('loan');
        });
    }

    public function reject(LoanRestructure $restructure, ?User $actor = null, ?string $note = null): LoanRestructure
    {
        return DB::transaction(function () use ($restructure, $actor, $note): LoanRestructure {
            $restructure = LoanRestructure::query()->lockForUpdate()->findOrFail($restructure->id);

            if ($restructure->status !== 'PENDING') {
                return $restructure;
            }

            $restructure->forceFill([
                'status' => 'REJECTED',
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'admin_notes' => $note,
            ])->save();

            $this->log($restructure, 'PENDING', 'REJECTED', $actor, $note);

            return $restructure->refresh();
        });
    }

    public function approveAndApply(LoanRestructure $restructure, ?User $actor = null, ?string $note = null): LoanRestructure
    {
        return DB::transaction(function () use ($restructure, $actor, $note): LoanRestructure {
            $restructure = LoanRestructure::query()
                ->with('loan.loanType')
                ->lockForUpdate()
                ->findOrFail($restructure->id);

            if ($restructure->status !== 'PENDING') {
                return $restructure;
            }

            $loan = Loan::query()
                ->with('loanType')
                ->lockForUpdate()
                ->findOrFail($restructure->loan_id);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Defaulted], true)) {
                throw ValidationException::withMessages([
                    'loan' => 'Restrukturisasi hanya dapat diterapkan untuk pinjaman aktif atau macet.',
                ]);
            }

            $loanType = clone $loan->loanType;
            $loanType->forceFill([
                'interest_rate' => $restructure->proposed_interest_rate,
                'admin_fee' => 0,
            ]);

            $calculation = $this->calculator->calculate(
                $loanType,
                (float) $restructure->proposed_principal_amount,
                (int) $restructure->proposed_term_months,
                $restructure->proposed_first_due_date->toDateString(),
            );

            $lastPaidInstallmentNo = (int) $loan->installments()
                ->whereIn('status', [InstallmentStatus::Paid->value, InstallmentStatus::Partial->value])
                ->max('installment_no');

            $loan->installments()
                ->whereNotIn('status', [InstallmentStatus::Paid->value, InstallmentStatus::Partial->value])
                ->delete();

            foreach ($calculation['schedule'] as $offset => $installment) {
                $loan->installments()->create([
                    ...$installment,
                    'installment_no' => $lastPaidInstallmentNo + $offset + 1,
                    'status' => InstallmentStatus::Pending,
                ]);
            }

            $loan->forceFill([
                'principal_amount' => $calculation['principal_amount'],
                'interest_rate' => $calculation['interest_rate'],
                'admin_fee' => $calculation['admin_fee'],
                'term_months' => $calculation['term_months'],
                'installment_amount' => $calculation['installment_amount'],
                'total_interest_amount' => $calculation['total_interest_amount'],
                'total_amount' => $calculation['total_amount'],
                'outstanding_amount' => $calculation['total_amount'],
                'first_due_date' => $calculation['first_due_date'],
                'status' => LoanStatus::Active,
            ])->save();

            $restructure->forceFill([
                'status' => 'APPROVED',
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'admin_notes' => $note,
            ])->save();

            $this->log($restructure, 'PENDING', 'APPROVED', $actor, $note);
            $this->logLoan($loan, 'RESTRUCTURE_PENDING', LoanStatus::Active->value, $actor, 'Restrukturisasi pinjaman diterapkan.');

            return $restructure->refresh()->load('loan.installments');
        });
    }

    private function log(LoanRestructure $restructure, ?string $fromStatus, string $toStatus, ?User $actor = null, ?string $note = null): void
    {
        ApprovalLog::query()->create([
            'subject_type' => LoanRestructure::class,
            'subject_id' => (string) $restructure->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'approved_by' => $actor?->id,
            'note' => $note,
        ]);
    }

    private function logLoan(Loan $loan, ?string $fromStatus, string $toStatus, ?User $actor = null, ?string $note = null): void
    {
        ApprovalLog::query()->create([
            'subject_type' => Loan::class,
            'subject_id' => (string) $loan->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'approved_by' => $actor?->id,
            'note' => $note,
        ]);
    }
}
