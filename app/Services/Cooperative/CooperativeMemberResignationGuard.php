<?php

namespace App\Services\Cooperative;

use App\Enums\LoanStatus;
use App\Models\CooperativeMember;
use Illuminate\Validation\ValidationException;

class CooperativeMemberResignationGuard
{
    public function assertCanResign(CooperativeMember $member): void
    {
        $messages = [];

        if ($this->hasUnsettledLoan($member)) {
            $messages[] = 'Anggota masih memiliki pinjaman berjalan atau saldo pinjaman belum lunas.';
        }

        if ($this->hasPendingRedemption($member)) {
            $messages[] = 'Anggota masih memiliki penukaran reward yang belum selesai dikirim.';
        }

        if ($this->hasUnpaidInvoice($member)) {
            $messages[] = 'Anggota masih memiliki tagihan iuran yang belum lunas.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages([
                'member' => $messages,
            ]);
        }
    }

    private function hasUnsettledLoan(CooperativeMember $member): bool
    {
        return $member->loans()
            ->whereIn('status', [
                LoanStatus::Applied->value,
                LoanStatus::Approved->value,
                LoanStatus::Active->value,
                LoanStatus::Defaulted->value,
            ])
            ->where('outstanding_amount', '>', 0)
            ->exists();
    }

    private function hasPendingRedemption(CooperativeMember $member): bool
    {
        return $member->rewardRedemptions()
            ->whereIn('status', ['PENDING', 'PROCESSING', 'SHIPPED'])
            ->exists();
    }

    private function hasUnpaidInvoice(CooperativeMember $member): bool
    {
        return $member->invoices()
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->exists();
    }
}
