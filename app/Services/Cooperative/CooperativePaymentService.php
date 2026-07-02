<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CooperativePaymentService
{
    public function __construct(
        private readonly CooperativePeriodLockService $periodLockService,
        private readonly CooperativeReceiptService $receiptService,
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
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

        $payment = CooperativePayment::query()->create([
            ...$data,
            'cooperative_dues_invoice_id' => $invoice?->id,
            'cooperative_contribution_type_id' => $contributionType?->id,
            'user_id' => $user?->id,
            'status' => $data['status'] ?? 'PENDING',
        ]);

        DB::afterCommit(fn () => $this->notificationDispatcher->paymentRecorded($payment, $user));

        return $payment;
    }

    public function approve(CooperativePayment $payment, ?User $approver = null): CooperativePayment
    {
        return DB::transaction(function () use ($payment, $approver): CooperativePayment {
            $payment = CooperativePayment::query()
                ->lockForUpdate()
                ->with('ledgerEntries')
                ->findOrFail($payment->id);

            $originalStatus = $payment->getOriginal('status');

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

                $paidAmount = (float) $invoice->paid_amount + (float) $payment->amount;

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
                    'source_type' => CooperativePayment::class,
                    'source_id' => $payment->id,
                    'cooperative_contribution_type_id' => $contributionType?->id,
                    'ledger_scope' => 'SAVINGS',
                    'category_snapshot' => $contributionType?->category,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'period' => $invoice?->period ?? $payment->paid_at?->format('Y-m'),
                    'description' => $payment->notes ?: 'Pembayaran iuran/simpanan koperasi',
                    'posted_at' => $payment->paid_at,
                ],
            );

            $payment->logApproval($originalStatus, 'APPROVED', $approver, 'Pembayaran disetujui');

            $this->receiptService->issue($payment, $approver);
            DB::afterCommit(fn () => $this->notificationDispatcher->paymentApproved($payment, $approver));

            return $payment->refresh()->load('receipt');
        });
    }

    public function reconcile(CooperativePayment $payment, ?User $user, string $reference, bool $approve = true): CooperativePayment
    {
        return DB::transaction(function () use ($payment, $user, $reference, $approve): CooperativePayment {
            $payment = CooperativePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->reconciled_at) {
                return $payment->refresh();
            }

            if ($approve && $payment->status !== 'APPROVED') {
                $payment = $this->approve($payment, $user);
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $payment->forceFill([
                'reconciled_at' => now(),
                'reconciled_by' => $user?->id,
                'reconciliation_reference' => $reference,
            ])->save();

            $payment->logApproval('APPROVED', 'RECONCILED', $user, "Referensi: {$reference}");

            return $payment->refresh();
        });
    }

    /**
     * @param  array{reason: string}  $data
     */
    public function cancelLedgerPayment(CooperativeLedgerEntry $entry, User $user, array $data): CooperativePayment
    {
        return DB::transaction(function () use ($entry, $user, $data): CooperativePayment {
            $entry = CooperativeLedgerEntry::query()
                ->lockForUpdate()
                ->with('payment.invoice')
                ->findOrFail($entry->id);

            $payment = $this->editablePaymentForLedgerEntry($entry);

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

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

            return $payment->refresh();
        });
    }

    /**
     * @param  array{amount: numeric, payment_method: string, paid_at: string, notes?: ?string, reason: string}  $data
     */
    public function reviseLedgerPayment(CooperativeLedgerEntry $entry, User $user, array $data): CooperativePayment
    {
        return DB::transaction(function () use ($entry, $user, $data): CooperativePayment {
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
                    'description' => $payment->notes ?: 'Pembayaran iuran/simpanan koperasi',
                    'posted_at' => $payment->paid_at,
                ])->save();
            }

            $this->deleteReceipt($payment);
            $this->receiptService->issue($payment->refresh(), $user);

            $payment->logApproval('APPROVED', 'APPROVED', $user, 'Revisi pembayaran: '.trim($data['reason']));

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
}
