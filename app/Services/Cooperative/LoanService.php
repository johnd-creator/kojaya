<?php

namespace App\Services\Cooperative;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\ApprovalLog;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoanService implements LoanServiceContract
{
    public function __construct(
        private readonly LoanCalculatorService $calculator,
        private readonly LoanEligibilityService $eligibility,
        private readonly CooperativePeriodLockService $periodLockService,
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(array $data, ?User $actor = null): Loan
    {
        return DB::transaction(function () use ($data, $actor): Loan {
            $loanType = LoanType::query()->findOrFail($data['loan_type_id']);
            $member = CooperativeMember::query()->findOrFail($data['cooperative_member_id']);

            $this->eligibility->assertEligible($member, $loanType, (float) $data['principal_amount']);

            $calculation = $this->calculator->calculate(
                $loanType,
                (float) $data['principal_amount'],
                (int) $data['term_months'],
                (string) $data['first_due_date'],
            );

            $loan = Loan::query()->create([
                'cooperative_member_id' => $data['cooperative_member_id'],
                'organization_id' => $data['organization_id'],
                'loan_type_id' => $loanType->id,
                'user_id' => $actor?->id ?? $data['user_id'] ?? null,
                'principal_amount' => $calculation['principal_amount'],
                'interest_rate' => $calculation['interest_rate'],
                'admin_fee' => $calculation['admin_fee'],
                'late_fee_per_day' => $calculation['late_fee_per_day'],
                'term_months' => $calculation['term_months'],
                'installment_amount' => $calculation['installment_amount'],
                'total_interest_amount' => $calculation['total_interest_amount'],
                'total_amount' => $calculation['total_amount'],
                'outstanding_amount' => $calculation['total_amount'],
                'applied_at' => $data['applied_at'] ?? now()->toDateString(),
                'first_due_date' => $calculation['first_due_date'],
                'status' => LoanStatus::Applied,
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($calculation['schedule'] as $installment) {
                $loan->installments()->create([
                    ...$installment,
                    'status' => InstallmentStatus::Pending,
                ]);
            }

            $this->logApproval($loan, null, LoanStatus::Applied->value, $actor, 'Pengajuan pinjaman dibuat.');
            DB::afterCommit(fn () => $this->notificationDispatcher->loanApplied($loan, $actor));

            return $loan->load(['member', 'loanType', 'installments']);
        });
    }

    public function managerReview(Loan $loan, ?User $actor = null, ?string $note = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $note): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if ($loan->status !== LoanStatus::Applied) {
                return $loan;
            }

            $this->assertActorIsNotLoanCreator($loan, $actor, 'manager_reviewed_by');

            $loan->forceFill([
                'status' => LoanStatus::ManagerApproved,
                'manager_reviewed_at' => now(),
                'manager_reviewed_by' => $actor?->id,
            ])->save();

            $this->logApproval($loan, LoanStatus::Applied->value, LoanStatus::ManagerApproved->value, $actor, $note);
            DB::afterCommit(fn () => $this->notificationDispatcher->loanManagerReviewed($loan, $actor));

            return $loan->refresh();
        });
    }

    public function approve(Loan $loan, ?User $actor = null, ?string $note = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $note): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if ($loan->status !== LoanStatus::ManagerApproved) {
                return $loan;
            }

            $this->assertActorIsNotLoanCreator($loan, $actor, 'approved_by');

            if ($actor && $loan->manager_reviewed_by && (int) $actor->id === (int) $loan->manager_reviewed_by) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'approved_by' => 'Reviewer manajer tidak dapat menjadi final approver pinjaman yang sama.',
                ]);
            }

            $loan->forceFill([
                'status' => LoanStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $actor?->id,
            ])->save();

            $this->logApproval($loan, LoanStatus::ManagerApproved->value, LoanStatus::Approved->value, $actor, $note);
            $this->audit->log('loan.approved', 'cooperative.loan', $loan, [
                'new' => ['status' => LoanStatus::Approved->value],
                'reason' => $note ?? 'Loan final approval recorded.',
            ], AuditContext::forActor($actor));
            DB::afterCommit(fn () => $this->notificationDispatcher->loanApproved($loan, $actor));

            return $loan->refresh();
        });
    }

    public function reject(Loan $loan, ?User $actor = null, string $reason = ''): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $reason): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if (! in_array($loan->status, [LoanStatus::Applied, LoanStatus::ManagerApproved], true)) {
                return $loan;
            }

            $fromStatus = $loan->status->value;

            if ($actor && $loan->user_id && (int) $actor->id === (int) $loan->user_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'rejected_by' => 'Pembuat pengajuan pinjaman tidak dapat menolak pinjamannya sendiri.',
                ]);
            }

            $loan->forceFill([
                'status' => LoanStatus::Rejected,
                'rejected_at' => now(),
                'rejected_by' => $actor?->id,
                'rejection_reason' => $reason,
            ])->save();

            $this->logApproval($loan, $fromStatus, LoanStatus::Rejected->value, $actor, $reason);
            DB::afterCommit(fn () => $this->notificationDispatcher->loanRejected($loan, $actor));

            return $loan->refresh();
        });
    }

    public function disburse(Loan $loan, ?User $actor = null, ?string $referenceNo = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $referenceNo): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if (! in_array($loan->status, [LoanStatus::Approved, LoanStatus::Active], true)) {
                return $loan;
            }

            if ($loan->status === LoanStatus::Approved) {
                $this->periodLockService->assertUnlocked($loan->applied_at?->format('Y-m'));

                $loan->forceFill([
                    'status' => LoanStatus::Active,
                    'disbursed_at' => now(),
                    'disbursed_by' => $actor?->id,
                    'reference_no' => $referenceNo ?: $loan->reference_no,
                ])->save();

                CooperativeLedgerEntry::query()->firstOrCreate(
                    [
                        'source_type' => Loan::class,
                        'source_id' => $loan->id,
                        'entry_type' => 'LOAN_DISBURSEMENT',
                    ],
                    [
                        'cooperative_member_id' => $loan->cooperative_member_id,
                        'organization_id' => $loan->organization_id,
                        'cooperative_payment_id' => null,
                        'ledger_scope' => 'LOAN',
                        'debit' => $loan->principal_amount,
                        'credit' => 0,
                        'period' => $loan->applied_at?->format('Y-m'),
                        'description' => 'Pencairan pinjaman koperasi',
                        'posted_at' => now()->toDateString(),
                    ],
                );

                $this->logApproval($loan, LoanStatus::Approved->value, LoanStatus::Active->value, $actor, 'Pinjaman dicairkan.');
                $this->audit->log('loan.disbursed', 'cooperative.loan', $loan, [
                    'new' => [
                        'status' => LoanStatus::Active->value,
                        'reference_no' => $loan->reference_no,
                    ],
                    'reason' => 'Loan disbursement recorded.',
                ], AuditContext::forActor($actor));
                DB::afterCommit(fn () => $this->notificationDispatcher->loanDisbursed($loan, $actor));
            }

            return $loan->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(Loan $loan, array $data, ?User $actor = null): LoanPayment
    {
        return DB::transaction(function () use ($loan, $data, $actor): LoanPayment {
            $loan = Loan::query()->lockForUpdate()->with('installments')->findOrFail($loan->id);

            $this->periodLockService->assertUnlocked(substr((string) $data['paid_at'], 0, 7));

            $remainingPayment = round((float) $data['amount'], 2);
            $principalPaid = 0.0;
            $interestPaid = 0.0;
            $feePaid = 0.0;
            $penaltyPaid = 0.0;
            $lastInstallmentId = null;

            $installments = $loan->installments()
                ->orderBy('installment_no')
                ->lockForUpdate()
                ->get();

            foreach ($installments as $installment) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $this->refreshPenalty($loan, $installment, (string) $data['paid_at']);

                $remainingDue = round((float) $installment->amount_due - (float) $installment->amount_paid, 2);
                if ($remainingDue <= 0) {
                    continue;
                }

                $allocation = min($remainingPayment, $remainingDue);
                $ratio = $remainingDue > 0 ? $allocation / $remainingDue : 0;

                $principalSegment = round(((float) $installment->principal_amount - min((float) $installment->amount_paid, (float) $installment->principal_amount)) * $ratio, 2);
                $interestBaseRemaining = max(0, (float) $installment->interest_amount - max(0, (float) $installment->amount_paid - (float) $installment->principal_amount));
                $interestSegment = min($allocation - $principalSegment, round($interestBaseRemaining * $ratio, 2));
                $feeRemaining = max(0, (float) $installment->fee_amount - max(0, (float) $installment->amount_paid - (float) $installment->principal_amount - (float) $installment->interest_amount));
                $feeSegment = min(max(0, $allocation - $principalSegment - $interestSegment), round($feeRemaining * $ratio, 2));
                $penaltySegment = round(max(0, $allocation - $principalSegment - $interestSegment - $feeSegment), 2);

                $installment->forceFill([
                    'amount_paid' => round((float) $installment->amount_paid + $allocation, 2),
                    'paid_at' => $data['paid_at'],
                    'status' => round((float) $installment->amount_paid + $allocation, 2) >= round((float) $installment->amount_due, 2)
                        ? InstallmentStatus::Paid
                        : InstallmentStatus::Partial,
                ])->save();

                $remainingPayment = round($remainingPayment - $allocation, 2);
                $principalPaid = round($principalPaid + $principalSegment, 2);
                $interestPaid = round($interestPaid + $interestSegment, 2);
                $feePaid = round($feePaid + $feeSegment, 2);
                $penaltyPaid = round($penaltyPaid + $penaltySegment, 2);
                $lastInstallmentId = $installment->id;
            }

            $payment = LoanPayment::query()->create([
                'loan_id' => $loan->id,
                'loan_installment_id' => $lastInstallmentId,
                'cooperative_member_id' => $loan->cooperative_member_id,
                'user_id' => $actor?->id ?? $data['user_id'] ?? null,
                'amount' => $data['amount'],
                'principal_amount' => $principalPaid,
                'interest_amount' => $interestPaid,
                'fee_amount' => $feePaid,
                'penalty_amount' => $penaltyPaid,
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'] ?? 'APPROVED',
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'approved_at' => now(),
                'approved_by' => $actor?->id,
            ]);

            $updatedOutstanding = max(0, round((float) $loan->outstanding_amount - (float) $data['amount'], 2));

            $loan->forceFill([
                'outstanding_amount' => $updatedOutstanding,
                'status' => $updatedOutstanding <= 0 ? LoanStatus::PaidOff : LoanStatus::Active,
            ])->save();

            CooperativeLedgerEntry::query()->create([
                'cooperative_member_id' => $loan->cooperative_member_id,
                'organization_id' => $loan->organization_id,
                'cooperative_payment_id' => null,
                'source_type' => LoanPayment::class,
                'source_id' => $payment->id,
                'entry_type' => 'LOAN_PAYMENT',
                'ledger_scope' => 'LOAN',
                'debit' => 0,
                'credit' => $data['amount'],
                'period' => substr((string) $data['paid_at'], 0, 7),
                'description' => 'Pembayaran angsuran pinjaman koperasi',
                'posted_at' => $data['paid_at'],
            ]);

            DB::afterCommit(fn () => $this->notificationDispatcher->loanPaymentRecorded($payment, $actor));

            return $payment;
        });
    }

    public function writeOff(Loan $loan, ?User $actor = null, ?string $note = null): Loan
    {
        $context = AuditContext::forActor($actor);
        $this->audit->log('loan.writeoff.requested', 'cooperative.loan', $loan, [
            'new' => [
                'loan_id' => $loan->getKey(),
                'requested_status' => LoanStatus::WrittenOff->value,
                'note_supplied' => is_string($note) && trim($note) !== '',
            ],
            'reason' => 'Loan write-off requested.',
        ], $context);

        try {
            $writtenOffLoan = DB::transaction(function () use ($loan, $actor, $note, $context): Loan {
                $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

                if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Defaulted], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Loan cannot be written off from its current status ['.$loan->status->value.'].',
                    ]);
                }

                $fromStatus = $loan->status->value;

                $loan->forceFill([
                    'status' => LoanStatus::WrittenOff,
                    'notes' => trim(($loan->notes ? $loan->notes."\n" : '').($note ?: 'Pinjaman dihapus buku.')),
                ])->save();

                $this->logApproval($loan, $fromStatus, LoanStatus::WrittenOff->value, $actor, $note);

                $this->audit->log('loan.writeoff.completed', 'cooperative.loan', $loan, [
                    'old' => [
                        'loan_id' => $loan->getKey(),
                        'organization_id' => $loan->organization_id,
                        'status' => $fromStatus,
                    ],
                    'new' => [
                        'loan_id' => $loan->getKey(),
                        'organization_id' => $loan->organization_id,
                        'status' => $loan->status->value,
                        'note_supplied' => is_string($note) && trim($note) !== '',
                        'completed_at' => now()->toDateTimeString(),
                    ],
                    'reason' => 'Loan write-off completed.',
                ], $context);

                DB::afterCommit(fn () => $this->notificationDispatcher->loanWrittenOff($loan, $actor));

                return $loan->refresh();
            });
        } catch (ValidationException $exception) {
            try {
                $this->audit->log('loan.writeoff.failed', 'cooperative.loan', $loan, [
                    'new' => [
                        'loan_id' => $loan->getKey(),
                        'requested_status' => LoanStatus::WrittenOff->value,
                        'note_supplied' => is_string($note) && trim($note) !== '',
                    ],
                    'reason' => 'Loan write-off rejected by the current state.',
                ], $context);
            } catch (Throwable $auditException) {
                Log::critical('Unable to persist loan write-off rejection audit.', [
                    'loan_id' => $loan->getKey(),
                    'exception_class' => $auditException::class,
                ]);
            }

            throw $exception;
        } catch (Throwable $exception) {
            Log::critical('Mandatory loan write-off audit failed; transaction rolled back.', [
                'loan_id' => $loan->getKey(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }

        return $writtenOffLoan;
    }

    private function refreshPenalty(Loan $loan, LoanInstallment $installment, string $paidAt): void
    {
        if ($installment->status === InstallmentStatus::Paid) {
            return;
        }

        $daysLate = Carbon::parse($paidAt)->startOfDay()->diffInDays($installment->due_date->startOfDay(), false) * -1;
        if ($daysLate <= 0) {
            if ($installment->due_date->isPast()) {
                $installment->forceFill([
                    'status' => InstallmentStatus::Overdue,
                ])->save();
            }

            return;
        }

        $penaltyAmount = round($daysLate * (float) $loan->late_fee_per_day, 2);

        if ((float) $installment->penalty_amount !== $penaltyAmount) {
            $installment->forceFill([
                'penalty_amount' => $penaltyAmount,
                'amount_due' => round((float) $installment->principal_amount + (float) $installment->interest_amount + (float) $installment->fee_amount + $penaltyAmount, 2),
                'status' => InstallmentStatus::Overdue,
            ])->save();
        }
    }

    private function logApproval(Loan $loan, ?string $fromStatus, string $toStatus, ?User $actor = null, ?string $note = null): void
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

    private function assertActorIsNotLoanCreator(Loan $loan, ?User $actor, string $errorKey): void
    {
        if ($actor && $loan->user_id && (int) $actor->id === (int) $loan->user_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $errorKey => 'Pembuat pengajuan pinjaman tidak dapat menyetujui pinjamannya sendiri.',
            ]);
        }
    }
}
