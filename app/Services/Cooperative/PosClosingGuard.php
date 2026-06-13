<?php

namespace App\Services\Cooperative;

use App\Models\PosTransaction;
use Illuminate\Validation\ValidationException;

class PosClosingGuard
{
    public function __construct(private readonly PosDailyClosingService $closings) {}

    /**
     * Throw ValidationException if the given date is locked.
     */
    public function ensureOpen(string $date, string $context = 'closing_date'): void
    {
        if ($this->isLocked($date)) {
            throw ValidationException::withMessages([
                $context => "Periode {$date} sudah ditutup. Buka kembali sebelum membuat perubahan.",
            ]);
        }
    }

    /**
     * Guard a new sale. Date is the sale's business date (sold_at).
     */
    public function guardSale(string $date): void
    {
        $this->ensureOpen($date, 'sold_at');
    }

    /**
     * Guard a return. Locks if either the original transaction date or the return date is locked.
     */
    public function guardReturn(PosTransaction $transaction, string $returnDate): void
    {
        $transactionDate = $transaction->sold_at?->toDateString() ?? $returnDate;
        if ($this->isLocked($transactionDate)) {
            throw ValidationException::withMessages([
                'pos_transaction_id' => "Transaksi asal pada tanggal {$transactionDate} sudah ditutup.",
            ]);
        }

        if ($this->isLocked($returnDate)) {
            throw ValidationException::withMessages([
                'returned_at' => "Tanggal retur {$returnDate} sudah ditutup.",
            ]);
        }
    }

    /**
     * Guard a void. Locks if the original transaction's date is locked.
     */
    public function guardVoid(PosTransaction $transaction): void
    {
        $transactionDate = $transaction->sold_at?->toDateString() ?? now()->toDateString();
        if ($this->isLocked($transactionDate)) {
            throw ValidationException::withMessages([
                'transaction' => "Transaksi pada tanggal {$transactionDate} sudah ditutup dan tidak bisa di-void.",
            ]);
        }
    }

    public function isLocked(string $date): bool
    {
        return $this->closings->isLocked($date);
    }
}
