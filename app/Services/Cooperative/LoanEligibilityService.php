<?php

namespace App\Services\Cooperative;

use App\Enums\LoanStatus;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\LoanType;
use Illuminate\Validation\ValidationException;

class LoanEligibilityService
{
    public function assertEligible(CooperativeMember $member, LoanType $loanType, float $principalAmount): void
    {
        $rules = $loanType->eligibility_rules ?? [];

        if (($rules['require_active_member'] ?? true) && $member->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'loan' => 'Pengajuan pinjaman hanya dapat dilakukan oleh anggota aktif.',
            ]);
        }

        $minimumMembershipMonths = (int) ($rules['min_membership_months'] ?? 0);
        if ($minimumMembershipMonths > 0 && $this->membershipMonths($member) < $minimumMembershipMonths) {
            throw ValidationException::withMessages([
                'loan' => "Masa keanggotaan belum memenuhi syarat minimal {$minimumMembershipMonths} bulan.",
            ]);
        }

        $maximumOutstandingLoans = (int) ($rules['max_outstanding_loans'] ?? 0);
        if ($this->outstandingLoanCount($member) > $maximumOutstandingLoans) {
            throw ValidationException::withMessages([
                'loan' => 'Anggota masih memiliki pinjaman berjalan.',
            ]);
        }

        $maximumLoanToSavingRatio = $rules['max_loan_to_saving_ratio'] ?? null;
        if ($maximumLoanToSavingRatio !== null) {
            $savingBalance = $this->savingBalance($member);
            $maximumPrincipal = round($savingBalance * (float) $maximumLoanToSavingRatio, 2);

            if ($principalAmount > $maximumPrincipal) {
                throw ValidationException::withMessages([
                    'loan' => 'Nilai pinjaman melebihi batas rasio simpanan anggota.',
                ]);
            }
        }
    }

    private function membershipMonths(CooperativeMember $member): int
    {
        if (! $member->joined_at) {
            return 0;
        }

        return $member->joined_at->copy()->startOfMonth()->diffInMonths(now()->startOfMonth()) + 1;
    }

    private function outstandingLoanCount(CooperativeMember $member): int
    {
        return $member->loans()
            ->whereIn('status', [
                LoanStatus::Applied->value,
                LoanStatus::ManagerApproved->value,
                LoanStatus::Approved->value,
                LoanStatus::Active->value,
                LoanStatus::Defaulted->value,
            ])
            ->where('outstanding_amount', '>', 0)
            ->count();
    }

    private function savingBalance(CooperativeMember $member): float
    {
        return (float) CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
            ->value('balance');
    }
}
