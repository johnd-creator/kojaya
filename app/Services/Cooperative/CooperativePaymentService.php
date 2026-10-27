<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeNotificationOutbox;
use App\Models\CooperativePayment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CooperativePaymentService
{
    public const PROOF_DISK = 'local';

    public function __construct(
        private readonly CooperativePeriodLockService $periodLockService,
        private readonly CooperativeReceiptService $receiptService,
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
        private readonly CooperativeNotificationOutboxService $notificationOutbox,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $user = null): CooperativePayment
    {
        $invoice = $this->resolveInvoice($data);
        $contributionType = $this->resolveContributionType($data, $invoice);

        if (! $invoice && ! $contributionType) {
            throw ValidationException::withMessages([
                'cooperative_contribution_type_id' => 'Jenis simpanan wajib dipilih.',
            ]);
        }

        if ($contributionType && in_array($contributionType->code, ['POKOK', 'WAJIB'], true)) {
            $expectedAmount = round((float) $contributionType->default_amount, 2);
            $submittedAmount = round((float) $data['amount'], 2);

            if ($submittedAmount !== $expectedAmount) {
                throw ValidationException::withMessages([
                    'amount' => "Nominal {$contributionType->name} harus ".number_format($expectedAmount, 0, ',', '.').'.',
                ]);
            }
        }

        $this->periodLockService->assertUnlocked($invoice?->period ?? substr((string) $data['paid_at'], 0, 7));

        return DB::transaction(function () use ($data, $invoice, $contributionType, $user): CooperativePayment {
            $payment = CooperativePayment::query()->create([
                ...$data,
                'cooperative_dues_invoice_id' => $invoice?->id,
                'cooperative_contribution_type_id' => $contributionType?->id,
                'user_id' => $user?->id,
                'status' => $data['status'] ?? 'PENDING',
            ]);

            $outboxIds = [];

            foreach ($this->notificationDispatcher->paymentNotificationIntents($payment, $user) as $intent) {
                $outbox = $this->notificationOutbox->enqueueForUser(
                    $intent['user'],
                    (string) $intent['payload']['deduplication_key'],
                    $intent['payload'],
                );

                if ($outbox) {
                    $outboxIds[] = $outbox->id;
                }
            }

            $this->scheduleOutboxDelivery($outboxIds);

            return $payment;
        });
    }

    public function approve(CooperativePayment $payment, ?User $approver = null, ?AuditContext $context = null): CooperativePayment
    {
        $context ??= AuditContext::forActor($approver);

        return DB::transaction(function () use ($payment, $approver, $context): CooperativePayment {
            $payment = CooperativePayment::query()
                ->lockForUpdate()
                ->with('ledgerEntries')
                ->findOrFail($payment->id);

            $originalStatus = $payment->getOriginal('status');

            if (! in_array($originalStatus, ['PENDING', 'APPROVED'], true)) {
                throw ValidationException::withMessages([
                    'payment' => 'Pengajuan yang dibatalkan atau ditolak tidak dapat disetujui.',
                ]);
            }

            if ($payment->status === 'APPROVED' && $payment->ledgerEntries->isNotEmpty()) {
                $this->receiptService->issue($payment, $approver);

                return $payment->refresh()->load('receipt');
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            if ($originalStatus !== 'APPROVED' && $approver && $payment->user_id && (int) $approver->id === (int) $payment->user_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'approved_by' => 'Pembuat pembayaran tidak dapat menyetujui pembayarannya sendiri.',
                ]);
            }

            $payment->forceFill([
                'status' => 'APPROVED',
                'approved_at' => now(),
                'approved_by' => $approver?->id,
            ])->save();

            $invoice = null;

            if ($payment->cooperative_dues_invoice_id) {
                $invoice = CooperativeDuesInvoice::query()
                    ->lockForUpdate()
                    ->with('contributionType')
                    ->findOrFail($payment->cooperative_dues_invoice_id);

                $remainingCents = (int) round((float) $invoice->amount * 100)
                    - (int) round((float) $invoice->paid_amount * 100);
                $paymentCents = (int) round((float) $payment->amount * 100);

                if (! in_array($invoice->status, ['UNPAID', 'PARTIAL'], true)
                    || $paymentCents <= 0 || $paymentCents > $remainingCents) {
                    throw ValidationException::withMessages([
                        'payment' => 'Pembayaran melebihi sisa tagihan atau tagihan sudah lunas/dibatalkan. Periksa pengajuan lain sebelum menyetujui.',
                    ]);
                }

                $paidAmount = round((float) $invoice->paid_amount + (float) $payment->amount, 2);

                $invoice->forceFill([
                    'paid_amount' => $paidAmount,
                    'status' => $paidAmount >= (float) $invoice->amount ? 'PAID' : 'PARTIAL',
                ])->save();
            }

            $contributionType = $payment->contributionType;

            if (! $contributionType && $invoice?->cooperative_contribution_type_id) {
                $contributionType = $invoice->contributionType;

                $payment->forceFill([
                    'cooperative_contribution_type_id' => $invoice->cooperative_contribution_type_id,
                ])->save();
            }

            CooperativeLedgerEntry::query()->firstOrCreate(
                [
                    'cooperative_payment_id' => $payment->id,
                    'entry_type' => 'SAVING_PAYMENT',
                ],
                [
                    'cooperative_member_id' => $payment->cooperative_member_id,
                    'organization_id' => $payment->member?->organization_id,
                    'source_type' => CooperativePayment::class,
                    'source_id' => $payment->id,
                    'cooperative_contribution_type_id' => $contributionType?->id,
                    'ledger_scope' => 'SAVINGS',
                    'category_snapshot' => $contributionType?->category,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'period' => $invoice?->period ?? $payment->paid_at?->format('Y-m'),
                    'description' => $this->paymentLedgerDescription($payment),
                    'posted_at' => $payment->paid_at,
                ],
            );

            $payment->logApproval($originalStatus, 'APPROVED', $approver, 'Pembayaran disetujui');
            $this->audit->log('payment.approved', 'cooperative.payment', $payment, [
                'new' => [
                    'status' => 'APPROVED',
                    'amount' => (float) $payment->amount,
                ],
                'reason' => 'Cooperative payment approved.',
            ], $context);

            $this->receiptService->issue($payment, $approver);

            $outboxIds = [];

            foreach ($this->notificationDispatcher->paymentNotificationIntents($payment, $approver, approved: true) as $intent) {
                $outbox = $this->notificationOutbox->enqueueForUser(
                    $intent['user'],
                    (string) $intent['payload']['deduplication_key'],
                    $intent['payload'],
                );

                if ($outbox) {
                    $outboxIds[] = $outbox->id;
                }
            }

            $this->scheduleOutboxDelivery($outboxIds);

            return $payment->refresh()->load('receipt');
        });
    }

    public function cancelPendingPayment(CooperativePayment $payment, User $user, string $reason, ?AuditContext $context = null): CooperativePayment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan pembatalan wajib diisi.']);
        }

        $context ??= AuditContext::forActor($user);

        return DB::transaction(function () use ($payment, $user, $reason, $context): CooperativePayment {
            $payment = CooperativePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'PENDING' || $payment->approved_at || $payment->reconciled_at
                || $payment->ledgerEntries()->exists() || $payment->receipt()->exists()
                || ! in_array($payment->payment_method, ['CASH', 'TRANSFER'], true)
                || filled($payment->gateway_provider) || filled($payment->gateway_reference)) {
                throw ValidationException::withMessages([
                    'reason' => 'Hanya pengajuan manual PENDING yang belum dibukukan dapat dibatalkan. Muat ulang daftar pembayaran.',
                ]);
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $oldNotes = $payment->notes;
            $payment->forceFill([
                'status' => 'VOID',
                'notes' => trim((string) $oldNotes."\nPengajuan dibatalkan oleh {$user->name}: {$reason}"),
            ])->save();

            $payment->logApproval('PENDING', 'VOID', $user, $reason);
            $this->audit->log('payment.pending_cancelled', 'cooperative.payment', $payment, [
                'old' => ['status' => 'PENDING', 'notes' => $oldNotes],
                'new' => ['status' => 'VOID', 'notes' => $payment->notes],
                'reason' => $reason,
            ], $context);

            return $payment->refresh();
        });
    }

    /**
     * Attempt prompt delivery only after the financial transaction commits.
     * The durable outbox retains any failure for the existing retry worker.
     *
     * @param  array<int, string>  $outboxIds
     */
    private function scheduleOutboxDelivery(array $outboxIds): void
    {
        if ($outboxIds === []) {
            return;
        }

        DB::afterCommit(function () use ($outboxIds): void {
            foreach ($outboxIds as $outboxId) {
                try {
                    $outbox = CooperativeNotificationOutbox::query()->find($outboxId);

                    if ($outbox) {
                        $this->notificationOutbox->deliver($outbox);
                    }
                } catch (\Throwable) {
                    // The committed outbox row remains available to the retry worker.
                }
            }
        });
    }

    public function reconcile(CooperativePayment $payment, ?User $user, string $reference, bool $approve = true, ?AuditContext $context = null): CooperativePayment
    {
        $context ??= AuditContext::forActor($user);

        return DB::transaction(function () use ($payment, $user, $reference, $approve, $context): CooperativePayment {
            $payment = CooperativePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->reconciled_at) {
                return $payment->refresh();
            }

            if ($approve && $payment->status !== 'APPROVED') {
                $payment = $this->approve($payment, $user, $context);
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $payment->forceFill([
                'reconciled_at' => now(),
                'reconciled_by' => $user?->id,
                'reconciliation_reference' => $reference,
            ])->save();

            $payment->logApproval('APPROVED', 'RECONCILED', $user, "Referensi: {$reference}");
            $this->audit->log('payment.reconciled', 'cooperative.payment', $payment, [
                'new' => [
                    'reconciled_at' => $payment->reconciled_at?->toISOString(),
                    'reconciliation_reference' => $reference,
                ],
                'reason' => 'Cooperative payment reconciled.',
            ], $context);

            return $payment->refresh();
        });
    }

    /**
     * @param  array{reason: string}  $data
     */
    public function cancelLedgerPayment(CooperativeLedgerEntry $entry, User $user, array $data, ?AuditContext $context = null): CooperativePayment
    {
        $context ??= AuditContext::forActor($user);

        return DB::transaction(function () use ($entry, $user, $data, $context): CooperativePayment {
            $entry = CooperativeLedgerEntry::query()
                ->lockForUpdate()
                ->with('payment.invoice')
                ->findOrFail($entry->id);

            $payment = $this->editablePaymentForLedgerEntry($entry);

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $oldStatus = $payment->status;
            $oldAmount = (float) $payment->amount;
            $oldPaidAt = $payment->paid_at?->toISOString() ?? (string) $payment->paid_at;
            $oldPaymentMethod = $payment->payment_method;
            $oldNotes = $payment->notes;

            $this->adjustInvoicePaidAmount($payment, -((float) $payment->amount));

            foreach ($payment->ledgerEntries()->lockForUpdate()->get() as $ledgerEntry) {
                $ledgerEntry->delete();
            }

            $this->deleteReceipt($payment);

            $reason = trim($data['reason']);
            $payment->forceFill([
                'status' => 'VOID',
                'notes' => trim((string) $payment->notes."\nDibatalkan oleh {$user->name}: {$reason}"),
                'receipt_no' => null,
                'receipt_issued_at' => null,
            ])->save();

            $payment->logApproval('APPROVED', 'VOID', $user, $reason);
            $this->audit->log('payment.cancelled', 'cooperative.payment', $payment, [
                'old' => [
                    'payment_id' => $payment->id,
                    'member_id' => $payment->cooperative_member_id,
                    'invoice_id' => $payment->cooperative_dues_invoice_id,
                    'status' => $oldStatus,
                    'amount' => $oldAmount,
                    'effective_ledger_amount' => $oldAmount,
                    'paid_at' => $oldPaidAt,
                    'payment_method' => $oldPaymentMethod,
                    'notes' => $oldNotes,
                ],
                'new' => [
                    'payment_id' => $payment->id,
                    'member_id' => $payment->cooperative_member_id,
                    'invoice_id' => $payment->cooperative_dues_invoice_id,
                    'status' => 'VOID',
                    'amount' => $oldAmount,
                    'effective_ledger_amount' => 0.0,
                    'paid_at' => $oldPaidAt,
                    'payment_method' => $oldPaymentMethod,
                    'notes' => $payment->notes,
                ],
                'reason' => $reason,
            ], $context);

            return $payment->refresh();
        });
    }

    /**
     * @param  array{amount: numeric, payment_method: string, paid_at: string, notes?: ?string, reason: string}  $data
     */
    public function reviseLedgerPayment(CooperativeLedgerEntry $entry, User $user, array $data, ?AuditContext $context = null): CooperativePayment
    {
        $context ??= AuditContext::forActor($user);

        return DB::transaction(function () use ($entry, $user, $data, $context): CooperativePayment {
            $entry = CooperativeLedgerEntry::query()
                ->lockForUpdate()
                ->with(['payment.invoice', 'payment.contributionType'])
                ->findOrFail($entry->id);

            $payment = $this->editablePaymentForLedgerEntry($entry);
            $contributionType = $payment->contributionType;
            $newAmount = round((float) $data['amount'], 2);

            if ($contributionType && in_array($contributionType->code, ['POKOK', 'WAJIB'], true)) {
                $expectedAmount = round((float) $contributionType->default_amount, 2);

                if ($newAmount !== $expectedAmount) {
                    throw ValidationException::withMessages([
                        'amount' => "Nominal {$contributionType->name} harus ".number_format($expectedAmount, 0, ',', '.').'.',
                    ]);
                }
            }

            $oldAmount = round((float) $payment->amount, 2);
            $oldPaidAt = $payment->paid_at?->toISOString() ?? (string) $payment->paid_at;
            $oldPaymentMethod = $payment->payment_method;
            $oldStatus = $payment->status;
            $oldNotes = $payment->notes;

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));
            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? substr($data['paid_at'], 0, 7));

            $payment->forceFill([
                'amount' => $newAmount,
                'payment_method' => $data['payment_method'],
                'paid_at' => $data['paid_at'],
                'notes' => $data['notes'] ?? null,
            ])->save();

            $this->adjustInvoicePaidAmount($payment, $newAmount - $oldAmount);

            $ledgerPeriod = $payment->invoice?->period ?? $payment->paid_at?->format('Y-m');

            foreach ($payment->ledgerEntries()->lockForUpdate()->get() as $ledgerEntry) {
                $ledgerEntry->forceFill([
                    'credit' => $newAmount,
                    'debit' => 0,
                    'period' => $ledgerPeriod,
                    'description' => $this->paymentLedgerDescription($payment),
                    'posted_at' => $payment->paid_at,
                ])->save();
            }

            $this->deleteReceipt($payment);
            $this->receiptService->issue($payment->refresh(), $user);

            $reason = trim($data['reason']);
            $payment->logApproval('APPROVED', 'APPROVED', $user, 'Revisi pembayaran: '.$reason);
            $this->audit->log('payment.revised', 'cooperative.payment', $payment, [
                'old' => [
                    'payment_id' => $payment->id,
                    'member_id' => $payment->cooperative_member_id,
                    'invoice_id' => $payment->cooperative_dues_invoice_id,
                    'status' => $oldStatus,
                    'amount' => $oldAmount,
                    'paid_at' => $oldPaidAt,
                    'payment_method' => $oldPaymentMethod,
                    'notes' => $oldNotes,
                ],
                'new' => [
                    'payment_id' => $payment->id,
                    'member_id' => $payment->cooperative_member_id,
                    'invoice_id' => $payment->cooperative_dues_invoice_id,
                    'status' => $payment->status,
                    'amount' => $newAmount,
                    'paid_at' => $data['paid_at'],
                    'payment_method' => $data['payment_method'],
                    'notes' => $payment->notes,
                ],
                'reason' => $reason,
            ], $context);

            return $payment->refresh()->load('receipt');
        });
    }

    public function voidDuesInvoicePayments(CooperativeDuesInvoice $invoice, User $user): int
    {
        return DB::transaction(function () use ($invoice, $user): int {
            $invoice = CooperativeDuesInvoice::query()
                ->lockForUpdate()
                ->with(['payments.receipt', 'payments.ledgerEntries'])
                ->findOrFail($invoice->id);

            $this->periodLockService->assertUnlocked($invoice->period);

            $payments = $invoice->payments->filter(
                fn (CooperativePayment $payment): bool => $payment->status === 'APPROVED' && $payment->reconciled_at === null,
            );

            if ($payments->isEmpty()) {
                return 0;
            }

            foreach ($payments as $payment) {
                foreach ($payment->ledgerEntries as $entry) {
                    $entry->delete();
                }

                if ($payment->receipt) {
                    if ($payment->receipt->pdf_path) {
                        Storage::disk('local')->delete($payment->receipt->pdf_path);
                    }

                    $payment->receipt->delete();
                }

                $payment->forceFill([
                    'status' => 'VOID',
                    'notes' => trim((string) $payment->notes."\nDibatalkan oleh System Admin karena koreksi input pembayaran."),
                    'receipt_no' => null,
                    'receipt_issued_at' => null,
                ])->save();

                $payment->logApproval('APPROVED', 'VOID', $user, 'Koreksi pembayaran iuran: status tagihan dikembalikan menjadi belum bayar.');
            }

            $invoice->forceFill([
                'paid_amount' => 0,
                'status' => 'UNPAID',
            ])->save();

            return $payments->count();
        });
    }

    private function paymentLedgerDescription(CooperativePayment $payment): string
    {
        $invoice = $payment->invoice;
        $type = $invoice?->contributionType ?? $payment->contributionType;

        if ($invoice && ($type?->code === 'WAJIB' || $type?->category === 'WAJIB')) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $invoice->period)
                ->locale('id')->translatedFormat('F Y');

            return 'Simpanan Wajib '.$month.(filled($payment->notes) ? ' — '.$payment->notes : '');
        }

        return $payment->notes ?: 'Pembayaran iuran/simpanan koperasi';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveInvoice(array &$data): ?CooperativeDuesInvoice
    {
        if (! empty($data['cooperative_dues_invoice_id'])) {
            return CooperativeDuesInvoice::query()->find($data['cooperative_dues_invoice_id']);
        }

        if (empty($data['cooperative_member_id']) || empty($data['cooperative_contribution_type_id'])) {
            return null;
        }

        $invoice = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $data['cooperative_member_id'])
            ->where('cooperative_contribution_type_id', $data['cooperative_contribution_type_id'])
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->orderBy('period')
            ->orderBy('id')
            ->first();

        if ($invoice) {
            $data['cooperative_dues_invoice_id'] = $invoice->id;
        }

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveContributionType(array $data, ?CooperativeDuesInvoice $invoice): ?CooperativeContributionType
    {
        if (! empty($data['cooperative_contribution_type_id'])) {
            return CooperativeContributionType::query()->find($data['cooperative_contribution_type_id']);
        }

        if ($invoice?->cooperative_contribution_type_id) {
            return $invoice->contributionType()->first();
        }

        return null;
    }

    private function editablePaymentForLedgerEntry(CooperativeLedgerEntry $entry): CooperativePayment
    {
        if ($entry->entry_type !== 'SAVING_PAYMENT' || ! $entry->cooperative_payment_id) {
            throw ValidationException::withMessages([
                'ledger_entry' => 'Hanya transaksi pembayaran simpanan yang dapat dikoreksi dari ledger.',
            ]);
        }

        $payment = CooperativePayment::query()
            ->lockForUpdate()
            ->with(['invoice', 'contributionType', 'receipt'])
            ->findOrFail($entry->cooperative_payment_id);

        if ($payment->status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'ledger_entry' => 'Hanya pembayaran berstatus approved yang dapat dikoreksi.',
            ]);
        }

        if ($payment->reconciled_at !== null) {
            throw ValidationException::withMessages([
                'ledger_entry' => 'Pembayaran yang sudah direkonsiliasi tidak dapat dikoreksi dari ledger.',
            ]);
        }

        return $payment;
    }

    private function adjustInvoicePaidAmount(CooperativePayment $payment, float $delta): void
    {
        if (! $payment->cooperative_dues_invoice_id) {
            return;
        }

        $invoice = CooperativeDuesInvoice::query()
            ->lockForUpdate()
            ->findOrFail($payment->cooperative_dues_invoice_id);

        $paidAmount = max(0, round((float) $invoice->paid_amount + $delta, 2));

        $invoice->forceFill([
            'paid_amount' => $paidAmount,
            'status' => match (true) {
                $paidAmount <= 0 => 'UNPAID',
                $paidAmount >= (float) $invoice->amount => 'PAID',
                default => 'PARTIAL',
            },
        ])->save();
    }

    private function deleteReceipt(CooperativePayment $payment): void
    {
        $payment->loadMissing('receipt');

        if (! $payment->receipt) {
            return;
        }

        if ($payment->receipt->pdf_path) {
            Storage::disk('local')->delete($payment->receipt->pdf_path);
        }

        $payment->receipt->delete();

        $payment->forceFill([
            'receipt_no' => null,
            'receipt_issued_at' => null,
        ])->save();
    }

    public function downloadProofResponse(CooperativePayment $payment, bool $inline = false): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = $payment->proof_path;

        if ($path === null || $path === '') {
            return null;
        }

        $disk = config('filesystems.payment_proof_disk', self::PROOF_DISK);

        if (Storage::disk($disk)->exists($path)) {
            $storage = Storage::disk($disk);
        } elseif (config('filesystems.payment_proof_legacy_public_fallback', true)
            && Storage::disk('public')->exists($path)) {
            $storage = Storage::disk('public');
        } else {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? $extension : 'bin';
        $filename = "bukti-pembayaran-{$payment->id}.{$extension}";

        $mimeType = $storage->mimeType($path);
        $disposition = $inline && in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true)
            ? 'inline'
            : 'attachment';

        return $storage->response($path, $filename, [
            'Cache-Control' => 'private, max-age=0, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'; frame-ancestors 'none'",
        ], $disposition);
    }
}
