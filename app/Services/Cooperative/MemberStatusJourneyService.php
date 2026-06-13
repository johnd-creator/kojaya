<?php

namespace App\Services\Cooperative;

use App\Enums\LoanStatus;
use App\Models\CooperativeMember;

class MemberStatusJourneyService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(CooperativeMember $member): array
    {
        return [
            'payment' => $this->paymentJourney($member),
            'loan' => $this->loanJourney($member),
            'reward' => $this->rewardJourney($member),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentJourney(CooperativeMember $member): array
    {
        $invoice = $member->invoices()->with(['payments.receipt', 'contributionType'])->latest('period')->first();
        $payment = $invoice?->payments->sortByDesc('created_at')->first()
            ?: $member->payments()->with('receipt')->latest('created_at')->first();

        return [
            'title' => 'Pembayaran simpanan',
            'current_status' => $payment?->status ?? $invoice?->status ?? 'BELUM_ADA_TAGIHAN',
            'reference' => $payment?->reference_no ?: $invoice?->period,
            'amount' => $payment ? (float) $payment->amount : ($invoice ? (float) $invoice->amount : 0),
            'steps' => [
                $this->step('Tagihan dibuat', $invoice !== null, $invoice?->created_at?->toIso8601String()),
                $this->step('Bukti pembayaran dikirim', $payment !== null, $payment?->created_at?->toIso8601String()),
                $this->step('Diverifikasi pengurus', $payment?->status === 'APPROVED', $payment?->approved_at?->toIso8601String()),
                $this->step('Kwitansi tersedia', $payment?->receipt !== null, $payment?->receipt?->issued_at?->toIso8601String()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingManualPaymentJourney(CooperativeMember $member): ?array
    {
        $payment = $member->payments()
            ->with(['invoice.contributionType', 'receipt'])
            ->where('status', 'PENDING')
            ->whereNotNull('proof_path')
            ->latest('created_at')
            ->first();

        if (! $payment) {
            return null;
        }

        $invoice = $payment->invoice;
        $reference = $payment->reference_no
            ?: $invoice?->contributionType?->name.' '.$invoice?->period;

        return [
            'title' => 'Pembayaran simpanan manual',
            'current_status' => $payment->status,
            'reference' => trim((string) $reference) ?: null,
            'amount' => (float) $payment->amount,
            'steps' => [
                $this->step('Tagihan dibuat', $invoice !== null, $invoice?->created_at?->toIso8601String()),
                $this->step('Bukti pembayaran dikirim', true, $payment->created_at?->toIso8601String()),
                $this->step('Diverifikasi pengurus', false),
                $this->step('Kwitansi tersedia', false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loanJourney(CooperativeMember $member): array
    {
        $loan = $member->loans()->with('loanType')->latest('created_at')->first();

        return [
            'title' => 'Pengajuan pinjaman',
            'current_status' => $loan?->status?->value ?? 'BELUM_ADA_PENGAJUAN',
            'reference' => $loan?->reference_no,
            'amount' => $loan ? (float) $loan->principal_amount : 0,
            'steps' => [
                $this->step('Pengajuan dikirim', $loan !== null, $loan?->applied_at?->toDateString()),
                $this->step('Direview koperasi', $loan !== null && ! in_array($loan->status, [LoanStatus::Applied], true)),
                $this->step('Disetujui', $loan !== null && in_array($loan->status, [LoanStatus::Approved, LoanStatus::Active, LoanStatus::PaidOff], true), $loan?->approved_at?->toIso8601String()),
                $this->step('Dana dicairkan', $loan !== null && in_array($loan->status, [LoanStatus::Active, LoanStatus::PaidOff], true), $loan?->disbursed_at?->toIso8601String()),
                $this->step('Lunas', $loan?->status === LoanStatus::PaidOff),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rewardJourney(CooperativeMember $member): array
    {
        $redemption = $member->rewardRedemptions()->with('reward')->latest('created_at')->first();

        return [
            'title' => 'Penukaran reward',
            'current_status' => $redemption?->status ?? 'BELUM_ADA_PENUKARAN',
            'reference' => $redemption?->reward?->name,
            'amount' => $redemption?->points_used ?? 0,
            'steps' => [
                $this->step('Reward dipilih', $redemption !== null, $redemption?->redeemed_at?->toIso8601String()),
                $this->step('Diproses koperasi', $redemption !== null && in_array($redemption->status, ['PROCESSING', 'COMPLETED'], true), $redemption?->processed_at?->toIso8601String()),
                $this->step('Selesai', $redemption?->status === 'COMPLETED'),
            ],
        ];
    }

    /**
     * @return array{label:string,completed:bool,completed_at:?string}
     */
    private function step(string $label, bool $completed, ?string $completedAt = null): array
    {
        return [
            'label' => $label,
            'completed' => $completed,
            'completed_at' => $completedAt,
        ];
    }
}
