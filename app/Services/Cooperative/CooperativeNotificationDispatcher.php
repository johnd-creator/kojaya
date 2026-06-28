<?php

namespace App\Services\Cooperative;

use App\Enums\LoanStatus;
use App\Models\CoffeeOrder;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanPayment;
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
            ...$this->coffeeOrderPayload($coffeeOrder, 'member.coffee_order.received', 'coffee', 'info', 'Pesanan kopi diterima', 'Pesanan kopi Anda sudah diterima dan menunggu diproses.', $actor, '/member/transactions'),
            'deduplication_key' => "member.coffee_order.received:{$coffeeOrder->id}",
        ]);

        $this->notifyRoles(['Admin Koperasi'], $coffeeOrder->member?->organization_id, [
            ...$this->coffeeOrderPayload($coffeeOrder, 'admin.coffee_order.received', 'coffee', 'info', 'Pesanan kopi baru', "Pesanan kopi {$coffeeOrder->member?->name} perlu diproses.", $actor, '/cooperative/pos/coffee-orders'),
            'deduplication_key' => "admin.coffee_order.received:{$coffeeOrder->id}",
        ]);
    }

    public function coffeeOrderStatusChanged(CoffeeOrder $coffeeOrder, ?User $actor = null): void
    {
        $coffeeOrder = $coffeeOrder->loadMissing(['member.user', 'product', 'transaction']);

        $this->notifyMember($coffeeOrder->member?->user, [
            ...$this->coffeeOrderPayload($coffeeOrder, 'member.coffee_order.status_changed', 'coffee', 'info', $coffeeOrder->statusLabel(), $this->coffeeOrderStatusMessage($coffeeOrder), $actor, '/member/transactions'),
            'deduplication_key' => "member.coffee_order.status_changed:{$coffeeOrder->id}:{$coffeeOrder->status}",
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
                'url' => '/cooperative/loans/'.$loan->id,
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
                'url' => '/cooperative/payments?status=PENDING',
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
}
