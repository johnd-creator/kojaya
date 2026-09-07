<?php

namespace App\Services\Cooperative;

use App\Models\PosDailyClosing;
use App\Models\PosTransaction;
use Illuminate\Validation\ValidationException;

class PosClosingGuard
{
    public function __construct(private readonly PosDailyClosingService $closings) {}

    /**
     * Throw ValidationException if the given date is locked for the organization.
     */
    public function ensureOpen(string $date, string $organizationId, string $context = 'closing_date'): void
    {
        if ($this->isLocked($date, $organizationId)) {
            throw ValidationException::withMessages([
                $context => "Periode {$date} sudah ditutup. Buka kembali sebelum membuat perubahan.",
            ]);
        }
    }

    /**
     * Preflight guard for a new sale. Date is the sale's business date (sold_at).
     */
    public function guardSale(string $date, string $organizationId): void
    {
        $this->ensureOpen($date, $organizationId, 'sold_at');
    }

    /**
     * Authoritative sale guard inside mutation transaction with DB row lock.
     */
    public function assertAndLockSale(string $organizationId, string $date): PosDailyClosing
    {
        $closing = $this->closings->acquireLockRow($organizationId, $date);
        if ($closing->is_locked) {
            throw ValidationException::withMessages([
                'sold_at' => "Periode {$date} sudah ditutup. Transaksi penjualan tidak diizinkan.",
            ]);
        }

        return $closing;
    }

    /**
     * Preflight guard for a return. Locks if either the original transaction date or return date is locked.
     */
    public function guardReturn(PosTransaction $transaction, string $returnDate): void
    {
        $orgId = (string) $transaction->organization_id;
        if (empty($orgId)) {
            throw ValidationException::withMessages([
                'pos_transaction_id' => 'Organisasi transaksi asal tidak valid.',
            ]);
        }

        $transactionDate = $transaction->sold_at?->toDateString() ?? $returnDate;
        if ($this->isLocked($transactionDate, $orgId)) {
            throw ValidationException::withMessages([
                'pos_transaction_id' => "Transaksi asal pada tanggal {$transactionDate} sudah ditutup.",
            ]);
        }

        if ($this->isLocked($returnDate, $orgId)) {
            throw ValidationException::withMessages([
                'returned_at' => "Tanggal retur {$returnDate} sudah ditutup.",
            ]);
        }
    }

    /**
     * Authoritative return guard inside mutation transaction with DB row locks in deterministic sorted date order.
     */
    public function assertAndLockReturn(PosTransaction $transaction, string $returnDate): void
    {
        $orgId = (string) $transaction->organization_id;
        if (empty($orgId)) {
            throw ValidationException::withMessages([
                'pos_transaction_id' => 'Organisasi transaksi asal tidak valid.',
            ]);
        }

        $txDate = $transaction->sold_at?->toDateString() ?? $returnDate;
        $dates = array_unique([$txDate, $returnDate]);
        sort($dates);

        foreach ($dates as $d) {
            $closing = $this->closings->acquireLockRow($orgId, $d);
            if ($closing->is_locked) {
                if ($d === $txDate) {
                    throw ValidationException::withMessages([
                        'pos_transaction_id' => "Transaksi asal pada tanggal {$txDate} sudah ditutup.",
                    ]);
                }
                throw ValidationException::withMessages([
                    'returned_at' => "Tanggal retur {$returnDate} sudah ditutup.",
                ]);
            }
        }
    }

    /**
     * Preflight guard for a void. Locks if the original transaction's date is locked.
     */
    public function guardVoid(PosTransaction $transaction): void
    {
        $orgId = (string) $transaction->organization_id;
        if (empty($orgId)) {
            throw ValidationException::withMessages([
                'transaction' => 'Organisasi transaksi tidak valid.',
            ]);
        }

        $transactionDate = $transaction->sold_at?->toDateString() ?? now()->toDateString();
        if ($this->isLocked($transactionDate, $orgId)) {
            throw ValidationException::withMessages([
                'transaction' => "Transaksi pada tanggal {$transactionDate} sudah ditutup dan tidak bisa di-void.",
            ]);
        }
    }

    /**
     * Authoritative void guard inside mutation transaction with DB row lock.
     */
    public function assertAndLockVoid(PosTransaction $transaction): PosDailyClosing
    {
        $orgId = (string) $transaction->organization_id;
        if (empty($orgId)) {
            throw ValidationException::withMessages([
                'transaction' => 'Organisasi transaksi tidak valid.',
            ]);
        }

        $transactionDate = $transaction->sold_at?->toDateString() ?? now()->toDateString();
        $closing = $this->closings->acquireLockRow($orgId, $transactionDate);
        if ($closing->is_locked) {
            throw ValidationException::withMessages([
                'transaction' => "Transaksi pada tanggal {$transactionDate} sudah ditutup dan tidak bisa di-void.",
            ]);
        }

        return $closing;
    }

    public function isLocked(string $date, string $organizationId): bool
    {
        return $this->closings->isLocked($date, $organizationId);
    }
}
