<?php

namespace App\Services\Cooperative;

use App\Enums\LoanStatus;
use App\Enums\WithdrawalStatus;
use App\Models\CoffeeOrder;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanRestructure;
use App\Models\PointTransaction;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\RewardRedemption;
use App\Models\SavingsWithdrawal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CooperativeNotificationDispatcher
{
    public function loanApplied(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.applied', 'loan', 'info', 'Pengajuan pinjaman terkirim', 'Pengajuan pinjaman Anda berhasil dikirim dan menunggu review Manajer Koperasi.', $actor),
            'deduplication_key' => "member.loan.applied:{$loan->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'admin.loan.created', 'loan', 'info', 'Pengajuan pinjaman baru', "Pengajuan pinjaman {$loan->member?->name} perlu dipantau operasional.", $actor),
            'deduplication_key' => "admin.loan.created:{$loan->id}",
        ]);

        $this->notifyRoles(['Manajer Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'manager.loan.review_required', 'loan', 'warning', 'Review pinjaman diperlukan', "Pengajuan pinjaman {$loan->member?->name} menunggu review Manajer Koperasi.", $actor),
            'deduplication_key' => "manager.loan.review_required:{$loan->id}",
        ]);
    }

    public function loanManagerReviewed(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.manager_reviewed', 'loan', 'info', 'Pinjaman selesai direview Manajer', 'Pengajuan pinjaman Anda sudah direview dan menunggu approval Pengurus Koperasi.', $actor),
            'deduplication_key' => "member.loan.manager_reviewed:{$loan->id}",
        ]);

        $this->notifyRoles(['Pengurus Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'pengurus.loan.final_approval_required', 'loan', 'warning', 'Final approval pinjaman diperlukan', "Pengajuan pinjaman {$loan->member?->name} menunggu approval Pengurus Koperasi.", $actor),
            'deduplication_key' => "pengurus.loan.final_approval_required:{$loan->id}",
        ]);
    }

    public function loanApproved(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.approved', 'loan', 'success', 'Pinjaman disetujui', 'Pengajuan pinjaman Anda sudah disetujui dan menunggu pencairan.', $actor),
            'deduplication_key' => "member.loan.approved:{$loan->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'admin.loan.ready_for_disbursement', 'loan', 'warning', 'Pinjaman siap dicairkan', "Pinjaman {$loan->member?->name} sudah final approval dan siap dicairkan.", $actor),
            'deduplication_key' => "admin.loan.ready_for_disbursement:{$loan->id}",
        ]);
    }

    public function loanRejected(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);
        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.rejected', 'loan', 'warning', 'Pinjaman ditolak', 'Pengajuan pinjaman Anda ditolak. Silakan lihat detail untuk informasi lanjutan.', $actor),
            'deduplication_key' => "member.loan.rejected:{$loan->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi', 'Manajer Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'manager.loan.rejected', 'loan', 'info', 'Pinjaman ditolak', "Pengajuan pinjaman {$loan->member?->name} ditolak.", $actor),
            'deduplication_key' => "internal.loan.rejected:{$loan->id}",
        ]);
    }

    public function loanDisbursed(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.disbursed', 'loan', 'success', 'Pinjaman dicairkan', 'Pinjaman Anda sudah dicairkan.', $actor),
            'deduplication_key' => "member.loan.disbursed:{$loan->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi', 'Manajer Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'admin.loan.disbursed', 'loan', 'info', 'Pinjaman dicairkan', "Pinjaman {$loan->member?->name} sudah dicairkan.", $actor),
            'deduplication_key' => "internal.loan.disbursed:{$loan->id}",
        ]);
    }

    public function loanPaymentRecorded(LoanPayment $payment, ?User $actor = null): void
    {
        $payment = $payment->loadMissing(['loan.member.user']);
        $loan = $payment->loan;

        if (! $loan) {
            return;
        }

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.payment_recorded', 'loan', 'success', 'Angsuran pinjaman diterima', 'Pembayaran angsuran pinjaman Anda sudah tercatat.', $actor),
            'deduplication_key' => "member.loan.payment_recorded:{$payment->id}",
            'metadata' => [
                'organization_id' => $loan->organization_id,
                'member_id' => $loan->cooperative_member_id,
                'loan_id' => $loan->id,
                'loan_payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
            ],
        ]);

        $this->notifyRoles(['Admin Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'admin.loan.payment_recorded', 'loan', 'info', 'Angsuran pinjaman dicatat', "Pembayaran angsuran pinjaman {$loan->member?->name} sudah dicatat.", $actor),
            'deduplication_key' => "admin.loan.payment_recorded:{$payment->id}",
            'metadata' => [
                'organization_id' => $loan->organization_id,
                'member_id' => $loan->cooperative_member_id,
                'loan_id' => $loan->id,
                'loan_payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
            ],
        ]);
    }

    public function paymentRecorded(CooperativePayment $payment, ?User $actor = null): void
    {
        $payment = $payment->loadMissing(['member.user', 'invoice.contributionType', 'contributionType']);

        $this->notifyMember($payment->member?->user, [
            ...$this->paymentPayload($payment, 'member.payment.proof_uploaded', 'payment', 'info', 'Bukti pembayaran diterima', 'Bukti pembayaran Anda sudah diterima dan menunggu verifikasi Admin Koperasi.', $actor),
            'deduplication_key' => "member.payment.proof_uploaded:{$payment->id}",
        ]);

        if ($payment->status === 'PENDING') {
            $this->notifyRoles(['Admin Koperasi'], $payment->member?->organization_id, [
                ...$this->paymentPayload($payment, 'admin.payment.approval_required', 'payment', 'warning', 'Bukti pembayaran perlu diverifikasi', "Pembayaran {$payment->member?->name} menunggu approval Admin Koperasi.", $actor),
                'deduplication_key' => "admin.payment.approval_required:{$payment->id}",
            ]);
        }
    }

    public function paymentApproved(CooperativePayment $payment, ?User $actor = null): void
    {
        $payment = $payment->loadMissing(['member.user', 'invoice.contributionType', 'contributionType']);

        $this->notifyMember($payment->member?->user, [
            ...$this->paymentPayload($payment, 'member.payment.approved', 'payment', 'success', 'Pembayaran disetujui', 'Pembayaran simpanan/iuran Anda sudah disetujui.', $actor),
            'deduplication_key' => "member.payment.approved:{$payment->id}",
        ]);
    }

    public function coffeeOrderReceived(CoffeeOrder $coffeeOrder, ?User $actor = null): void
    {
        $coffeeOrder = $coffeeOrder->loadMissing(['member.user', 'product', 'transaction']);

        $this->notifyMember($coffeeOrder->member?->user, [
            ...$this->coffeeOrderPayload($coffeeOrder, 'member.coffee_order.received', 'coffee', 'info', 'Pesanan kopi diterima', 'Pesanan kopi Anda sudah diterima dan menunggu diproses.', $actor, $this->routePath('member.transactions')),
            'deduplication_key' => "member.coffee_order.received:{$coffeeOrder->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi'], $coffeeOrder->member?->organization_id, [
            ...$this->coffeeOrderPayload($coffeeOrder, 'admin.coffee_order.received', 'coffee', 'info', 'Pesanan kopi baru', "Pesanan kopi {$coffeeOrder->member?->name} perlu diproses.", $actor, $this->routePath('cooperative.pos.coffee-orders.index')),
            'deduplication_key' => "admin.coffee_order.received:{$coffeeOrder->id}",
        ]);
    }

    public function coffeeOrderStatusChanged(CoffeeOrder $coffeeOrder, ?User $actor = null): void
    {
        $coffeeOrder = $coffeeOrder->loadMissing(['member.user', 'product', 'transaction']);

        $this->notifyMember($coffeeOrder->member?->user, [
            ...$this->coffeeOrderPayload($coffeeOrder, 'member.coffee_order.status_changed', 'coffee', 'info', $coffeeOrder->statusLabel(), $this->coffeeOrderStatusMessage($coffeeOrder), $actor, $this->routePath('member.transactions')),
            'deduplication_key' => "member.coffee_order.status_changed:{$coffeeOrder->id}:{$coffeeOrder->status}",
        ]);
    }

    public function loanWrittenOff(Loan $loan, ?User $actor = null): void
    {
        $loan = $loan->loadMissing(['member.user', 'loanType']);

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.written_off', 'loan', 'warning', 'Pinjaman dihapus buku', 'Pinjaman Anda dihapus buku (write-off). Silakan hubungi koperasi untuk informasi lanjutan.', $actor),
            'deduplication_key' => "member.loan.written_off:{$loan->id}",
        ]);

        $this->notifyRoles(['Pengurus Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'pengurus.loan.written_off', 'loan', 'info', 'Pinjaman dihapus buku', "Pinjaman {$loan->member?->name} dihapus buku.", $actor),
            'deduplication_key' => "pengurus.loan.written_off:{$loan->id}",
        ]);
    }

    public function loanRestructureRequested(LoanRestructure $restructure, ?User $actor = null): void
    {
        $restructure->loadMissing(['loan.member.user']);
        $loan = $restructure->loan;

        if (! $loan) {
            return;
        }

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.restructure_requested', 'loan', 'info', 'Pengajuan restrukturisasi terkirim', 'Pengajuan restrukturisasi pinjaman Anda terkirim dan menunggu review Manajer Koperasi.', $actor),
            'deduplication_key' => "member.loan.restructure_requested:{$restructure->id}",
        ]);

        $this->notifyRoles(['Manajer Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'manager.loan.restructure_review_required', 'loan', 'warning', 'Review restrukturisasi diperlukan', "Pengajuan restrukturisasi pinjaman {$loan->member?->name} menunggu review.", $actor),
            'deduplication_key' => "manager.loan.restructure_review_required:{$restructure->id}",
        ]);
    }

    public function loanRestructureRejected(LoanRestructure $restructure, ?User $actor = null): void
    {
        $restructure->loadMissing(['loan.member.user']);
        $loan = $restructure->loan;

        if (! $loan) {
            return;
        }

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.restructure_rejected', 'loan', 'warning', 'Restrukturisasi ditolak', 'Pengajuan restrukturisasi pinjaman Anda ditolak. Silakan hubungi koperasi untuk informasi lanjutan.', $actor),
            'deduplication_key' => "member.loan.restructure_rejected:{$restructure->id}",
        ]);
    }

    public function loanRestructureApproved(LoanRestructure $restructure, ?User $actor = null): void
    {
        $restructure->loadMissing(['loan.member.user']);
        $loan = $restructure->loan;

        if (! $loan) {
            return;
        }

        $this->notifyMember($loan->member?->user, [
            ...$this->loanPayload($loan, 'member.loan.restructured', 'loan', 'success', 'Restrukturisasi disetujui', 'Pengajuan restrukturisasi pinjaman Anda sudah disetujui dan diterapkan. Jadwal angsuran baru telah dibuat.', $actor),
            'deduplication_key' => "member.loan.restructured:{$restructure->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi', 'Manajer Koperasi'], $loan->organization_id, [
            ...$this->loanPayload($loan, 'admin.loan.restructured', 'loan', 'info', 'Restrukturisasi diterapkan', "Restrukturisasi pinjaman {$loan->member?->name} sudah diterapkan.", $actor),
            'deduplication_key' => "admin.loan.restructured:{$restructure->id}",
        ]);
    }

    public function memberSubmittedForValidation(CooperativeMember $member, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $this->notifyRoles(['Admin Koperasi'], $member->organization_id, [
            ...$this->memberPayload($member, 'admin.member.validation_required', 'member', 'warning', 'Validasi calon anggota diperlukan', "{$member->name} mengirim data onboarding dan menunggu verifikasi Admin Koperasi.", $actor, $this->routePath('cooperative.members.index')),
            'deduplication_key' => "admin.member.validation_required:{$member->id}",
        ]);
    }

    public function memberAdminVerified(CooperativeMember $member, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $this->notifyRoles(['Pengurus Koperasi'], $member->organization_id, [
            ...$this->memberPayload($member, 'pengurus.member.approval_required', 'member', 'warning', 'Approval final anggota diperlukan', "{$member->name} sudah diverifikasi Admin Koperasi dan menunggu approval Pengurus Koperasi.", $actor, $this->routePath('cooperative.members.index')),
            'deduplication_key' => "pengurus.member.approval_required:{$member->id}",
        ]);
    }

    public function memberFinalApproved(CooperativeMember $member, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $this->notifyMember($member->user, [
            ...$this->memberPayload($member, 'member.member.approved', 'member', 'success', 'Keanggotaan disetujui', 'Selamat! Keanggotaan koperasi Anda sudah aktif. Anda dapat mengakses seluruh layanan anggota.', $actor, $this->routePath('member.dashboard')),
            'deduplication_key' => "member.member.approved:{$member->id}",
        ]);
    }

    public function memberRevisionRequested(CooperativeMember $member, ?User $actor = null, ?string $notes = null): void
    {
        $member->loadMissing('user');

        $this->notifyMember($member->user, [
            ...$this->memberPayload($member, 'member.member.revision_requested', 'member', 'warning', 'Data keanggotaan perlu diperbaiki', 'Pengajuan keanggotaan Anda perlu diperbaiki. Silakan periksa catatan revisi dan lengkapi kembali data Anda.', $actor, $this->routePath('member.profile')),
            'deduplication_key' => "member.member.revision_requested:{$member->id}",
            'metadata' => array_merge($this->memberMetadata($member), ['notes' => $notes]),
        ]);
    }

    public function memberRejected(CooperativeMember $member, ?User $actor = null, ?string $reason = null): void
    {
        $member->loadMissing('user');

        $this->notifyMember($member->user, [
            ...$this->memberPayload($member, 'member.member.rejected', 'member', 'warning', 'Pengajuan keanggotaan ditolak', 'Pengajuan keanggotaan koperasi Anda ditolak. Silakan hubungi koperasi untuk informasi lanjutan.', $actor, $this->routePath('member.profile')),
            'deduplication_key' => "member.member.rejected:{$member->id}",
            'metadata' => array_merge($this->memberMetadata($member), ['reason' => $reason]),
        ]);
    }

    public function posSaleCompleted(PosTransaction $transaction, ?User $actor = null): void
    {
        $transaction->loadMissing('member.user');

        $this->notifyMember($transaction->member?->user, [
            ...$this->posPayload($transaction, 'member.pos.sale_completed', 'pos', 'success', 'Transaksi belanja selesai', "Transaksi {$transaction->transaction_no} berhasil. Total Rp ".number_format((float) $transaction->total_amount, 0, ',', '.').'.', $actor, $this->routePath('member.transactions')),
            'deduplication_key' => "member.pos.sale_completed:{$transaction->id}",
        ]);
    }

    public function posVoidRequested(PosTransaction $transaction, User $requester, ?string $organizationId = null): void
    {
        $transaction->loadMissing('member.user');

        $this->notifyRoles(['Pengurus Koperasi'], $organizationId ?? $requester->organization_id, [
            ...$this->posPayload($transaction, 'pengurus.pos.void_required', 'pos', 'warning', 'Approval void transaksi diperlukan', "Permintaan void transaksi {$transaction->transaction_no} menunggu approval Pengurus Koperasi.", $requester, $this->routePath('cooperative.pos.void-requests.index')),
            'deduplication_key' => "pengurus.pos.void_required:{$transaction->id}",
        ]);
    }

    public function posVoidApproved(PosTransaction $transaction, PosVoidRequest $request, ?User $actor = null): void
    {
        $transaction->loadMissing('member.user');
        $request->loadMissing('requester');

        $this->notifyMember($transaction->member?->user, [
            ...$this->posPayload($transaction, 'member.pos.voided', 'pos', 'info', 'Transaksi dibatalkan', "Transaksi {$transaction->transaction_no} telah di-void.", $actor, $this->routePath('member.transactions')),
            'deduplication_key' => "member.pos.voided:{$transaction->id}",
        ]);

        $requester = $request->requester;
        if ($requester && (! $actor || $requester->id !== $actor->id)) {
            $this->notifyMember($requester, [
                ...$this->posPayload($transaction, 'cashier.pos.void_approved', 'pos', 'success', 'Void disetujui', "Void transaksi {$transaction->transaction_no} disetujui.", $actor, $this->routePath('cooperative.pos.transactions.show', $transaction)),
                'deduplication_key' => "cashier.pos.void_approved:{$request->id}",
            ]);
        }
    }

    public function posVoidRejected(PosTransaction $transaction, PosVoidRequest $request, ?User $actor = null): void
    {
        $transaction->loadMissing('member.user');
        $request->loadMissing('requester');

        $requester = $request->requester;

        if ($requester) {
            $this->notifyMember($requester, [
                ...$this->posPayload($transaction, 'cashier.pos.void_rejected', 'pos', 'warning', 'Void ditolak', "Permintaan void transaksi {$transaction->transaction_no} ditolak.", $actor, $this->routePath('cooperative.pos.transactions.show', $transaction)),
                'deduplication_key' => "cashier.pos.void_rejected:{$request->id}",
            ]);
        }
    }

    public function withdrawalRequested(SavingsWithdrawal $withdrawal, ?User $actor = null): void
    {
        $withdrawal->loadMissing('member.organization');

        $this->notifyRoles(['Admin Koperasi', 'Pengurus Koperasi'], $withdrawal->member?->organization_id, [
            ...$this->withdrawalPayload($withdrawal, 'admin.withdrawal.requested', 'savings', 'warning', 'Approval penarikan simpanan diperlukan', "Penarikan simpanan {$withdrawal->member?->name} senilai Rp ".number_format((float) $withdrawal->amount, 0, ',', '.').' menunggu approval.', $actor, $this->routePath('cooperative.savings.withdrawals.index')),
            'deduplication_key' => "admin.withdrawal.requested:{$withdrawal->id}",
        ]);
    }

    public function withdrawalApproved(SavingsWithdrawal $withdrawal, ?User $actor = null): void
    {
        $withdrawal->loadMissing('member.user');

        $this->notifyMember($withdrawal->member?->user, [
            ...$this->withdrawalPayload($withdrawal, 'member.withdrawal.approved', 'savings', 'success', 'Penarikan simpanan disetujui', 'Penarikan simpanan sukarela Rp '.number_format((float) $withdrawal->amount, 0, ',', '.').' sudah diproses dan ditransfer ke rekening tujuan.', $actor, $this->routePath('member.savings')),
            'deduplication_key' => "member.withdrawal.approved:{$withdrawal->id}",
        ]);
    }

    public function withdrawalRejected(SavingsWithdrawal $withdrawal, ?User $actor = null, ?string $reason = null): void
    {
        $withdrawal->loadMissing('member.user');

        $this->notifyMember($withdrawal->member?->user, [
            ...$this->withdrawalPayload($withdrawal, 'member.withdrawal.rejected', 'savings', 'warning', 'Penarikan simpanan ditolak', 'Pengajuan penarikan simpanan sukarela Anda ditolak. Silakan hubungi koperasi untuk informasi lanjutan.', $actor, $this->routePath('member.savings')),
            'deduplication_key' => "member.withdrawal.rejected:{$withdrawal->id}",
            'metadata' => array_merge($this->withdrawalMetadata($withdrawal), ['reason' => $reason]),
        ]);
    }

    public function pointsEarned(CooperativeMember $member, PointTransaction $transaction, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $this->notifyMember($member->user, [
            ...$this->pointPayload($member, $transaction, 'member.points.earned', 'points', 'success', 'Poin baru diperoleh', "Anda mendapat {$this->signedPoints($transaction)} poin dari transaksi koperasi. Saldo poin: ".number_format((int) $transaction->balance_after).'.', $actor, $this->routePath('member.points')),
            'deduplication_key' => "member.points.earned:{$transaction->id}",
        ]);
    }

    public function pointsRedeemed(CooperativeMember $member, PointTransaction $transaction, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $rewardName = $transaction->metadata['reward_name'] ?? 'reward';

        $this->notifyMember($member->user, [
            ...$this->pointPayload($member, $transaction, 'member.points.redeemed', 'points', 'info', 'Poin ditukar', "Anda menukar {$this->signedPoints($transaction)} poin untuk {$rewardName}. Sisa saldo: ".number_format((int) $transaction->balance_after).'.', $actor, $this->routePath('member.points')),
            'deduplication_key' => "member.points.redeemed:{$transaction->id}",
        ]);
    }

    public function pointsExpired(CooperativeMember $member, PointTransaction $transaction, ?User $actor = null): void
    {
        $member->loadMissing('user');

        $this->notifyMember($member->user, [
            ...$this->pointPayload($member, $transaction, 'member.points.expired', 'points', 'warning', 'Poin kedaluwarsa', "{$this->signedPoints($transaction)} poin Anda kedaluwarsa. Saldo poin: ".number_format((int) $transaction->balance_after).'.', $actor, $this->routePath('member.points')),
            'deduplication_key' => "member.points.expired:{$transaction->id}",
        ]);
    }

    public function rewardStatusChanged(RewardRedemption $redemption, ?User $actor = null): void
    {
        $redemption->loadMissing(['member.user', 'reward']);

        $member = $redemption->member;
        $rewardName = $redemption->reward?->name ?? 'reward';

        [$title, $message, $severity] = $this->rewardStatusMessage($redemption->status, $rewardName);

        $this->notifyMember($member?->user, [
            'event_type' => 'member.reward.status_changed',
            'category' => 'reward',
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'reward_redemption',
                'id' => $redemption->id,
                'label' => $rewardName,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $this->routePath('member.points'),
            ],
            'metadata' => [
                'organization_id' => $member?->organization_id,
                'member_id' => $redemption->cooperative_member_id,
                'redemption_id' => $redemption->id,
                'reward_id' => $redemption->reward_id,
                'reward_name' => $rewardName,
                'quantity' => (int) $redemption->quantity,
                'points_used' => (int) $redemption->points_used,
                'status' => $redemption->status,
            ],
            'deduplication_key' => "member.reward.status_changed:{$redemption->id}:{$redemption->status}",
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notifyMember(?User $user, array $payload): void
    {
        if (! $user) {
            return;
        }

        $this->notifyUsers(collect([$user]), $payload);
    }

    /**
     * @param  array<int, string>  $roles
     * @param  array<string, mixed>  $payload
     */
    private function notifyRoles(array $roles, mixed $organizationId, array $payload): void
    {
        $existingRoles = Role::query()
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        if ($existingRoles === []) {
            return;
        }

        $users = User::role($existingRoles)
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->get();

        $this->notifyUsers($users, $payload);
    }

    /**
     * @param  Collection<int, User>|EloquentCollection<int, User>  $users
     * @param  array<string, mixed>  $payload
     */
    private function notifyUsers(Collection|EloquentCollection $users, array $payload): void
    {
        $users
            ->unique('id')
            ->each(function (User $user) use ($payload): void {
                $deduplicationKey = (string) ($payload['deduplication_key'] ?? Str::uuid());
                $exists = $user->notifications()
                    ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
                    ->where('data->deduplication_key', $deduplicationKey)
                    ->exists();

                if ($exists) {
                    return;
                }

                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\CooperativeDatabaseNotification',
                    'data' => $payload,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Resolve a named route to a relative path so notification action URLs stay
     * host-agnostic while still validating the route name at runtime.
     */
    private function routePath(string $name, mixed $parameters = []): string
    {
        return parse_url(route($name, $parameters), PHP_URL_PATH);
    }

    /**
     * @return array<string, mixed>
     */
    private function loanPayload(Loan $loan, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'loan',
                'id' => $loan->id,
                'label' => 'Pinjaman '.$loan->member?->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $this->routePath('cooperative.loans.show', $loan),
            ],
            'metadata' => [
                'organization_id' => $loan->organization_id,
                'member_id' => $loan->cooperative_member_id,
                'amount' => (float) $loan->principal_amount,
                'status' => $loan->status instanceof LoanStatus ? $loan->status->value : (string) $loan->status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(CooperativePayment $payment, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'payment',
                'id' => $payment->id,
                'label' => 'Pembayaran '.$payment->member?->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $this->routePath('cooperative.payments.index'),
            ],
            'metadata' => [
                'organization_id' => $payment->member?->organization_id,
                'member_id' => $payment->cooperative_member_id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coffeeOrderPayload(CoffeeOrder $coffeeOrder, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor, string $actionUrl): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'coffee_order',
                'id' => $coffeeOrder->id,
                'label' => $coffeeOrder->transaction?->transaction_no ?? 'Pesanan kopi',
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $actionUrl,
            ],
            'metadata' => [
                'organization_id' => $coffeeOrder->member?->organization_id,
                'member_id' => $coffeeOrder->cooperative_member_id,
                'coffee_order_id' => $coffeeOrder->id,
                'coffee_order_code' => $coffeeOrder->transaction?->transaction_no,
                'product_id' => $coffeeOrder->pos_product_id,
                'product_name' => $coffeeOrder->product?->name,
                'quantity' => (int) $coffeeOrder->quantity,
                'status' => $coffeeOrder->status,
            ],
        ];
    }

    private function coffeeOrderStatusMessage(CoffeeOrder $coffeeOrder): string
    {
        $productName = $coffeeOrder->product?->name ?? 'kopi';

        return match ($coffeeOrder->status) {
            CoffeeOrder::STATUS_BREWING => "Pesanan {$productName} sedang diseduh.",
            CoffeeOrder::STATUS_READY => "Pesanan {$productName} siap diambil.",
            CoffeeOrder::STATUS_PICKED_UP => "Pesanan {$productName} sudah selesai.",
            CoffeeOrder::STATUS_CANCELLED => "Pesanan {$productName} dibatalkan.",
            default => "Pesanan {$productName} diterima.",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(CooperativeMember $member, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor, string $actionUrl): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'member',
                'id' => $member->id,
                'label' => $member->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $actionUrl,
            ],
            'metadata' => $this->memberMetadata($member),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberMetadata(CooperativeMember $member): array
    {
        return [
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'validation_status' => $member->validation_status,
            'status' => $member->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function posPayload(PosTransaction $transaction, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor, string $actionUrl): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'pos_transaction',
                'id' => $transaction->id,
                'label' => $transaction->transaction_no,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $actionUrl,
            ],
            'metadata' => [
                'organization_id' => $transaction->member?->organization_id,
                'member_id' => $transaction->cooperative_member_id,
                'transaction_id' => $transaction->id,
                'transaction_no' => $transaction->transaction_no,
                'amount' => (float) $transaction->total_amount,
                'status' => $transaction->status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function withdrawalPayload(SavingsWithdrawal $withdrawal, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor, string $actionUrl): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'savings_withdrawal',
                'id' => $withdrawal->id,
                'label' => 'Penarikan simpanan '.$withdrawal->member?->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $actionUrl,
            ],
            'metadata' => $this->withdrawalMetadata($withdrawal),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function withdrawalMetadata(SavingsWithdrawal $withdrawal): array
    {
        return [
            'organization_id' => $withdrawal->member?->organization_id,
            'member_id' => $withdrawal->cooperative_member_id,
            'withdrawal_id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'status' => $withdrawal->status instanceof WithdrawalStatus ? $withdrawal->status->value : (string) $withdrawal->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pointPayload(CooperativeMember $member, PointTransaction $transaction, string $eventType, string $category, string $severity, string $title, string $message, ?User $actor, string $actionUrl): array
    {
        return [
            'event_type' => $eventType,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'subject' => [
                'type' => 'point_transaction',
                'id' => $transaction->id,
                'label' => 'Poin '.$member->name,
            ],
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
            ] : null,
            'action' => [
                'label' => 'Buka detail',
                'url' => $actionUrl,
            ],
            'metadata' => [
                'organization_id' => $member->organization_id,
                'member_id' => $member->id,
                'point_transaction_id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'points' => (int) $transaction->points,
                'balance_after' => (int) $transaction->balance_after,
                'reference_number' => $transaction->reference_number,
                'description' => $transaction->description,
            ],
        ];
    }

    private function signedPoints(PointTransaction $transaction): string
    {
        $points = abs((int) $transaction->points);

        return number_format($points);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function rewardStatusMessage(string $status, string $rewardName): array
    {
        return match ($status) {
            'PROCESSING' => ['Reward sedang diproses', "Penukaran {$rewardName} sedang diproses oleh koperasi.", 'info'],
            'SHIPPED' => ['Reward dikirim', "Penukaran {$rewardName} sudah dikirim.", 'info'],
            'DELIVERED' => ['Reward diterima', "Penukaran {$rewardName} telah sampai. Selamat menikmati!", 'success'],
            'CANCELLED' => ['Reward dibatalkan', "Penukaran {$rewardName} dibatalkan. Poin telah dikembalikan ke saldo Anda.", 'warning'],
            'PENDING' => ['Penukaran diterima', "Penukaran {$rewardName} diterima dan menunggu diproses.", 'info'],
            default => ['Status reward berubah', "Status penukaran {$rewardName} kini {$status}.", 'info'],
        };
    }
}
