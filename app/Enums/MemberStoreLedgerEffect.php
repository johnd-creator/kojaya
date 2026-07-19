<?php

namespace App\Enums;

enum MemberStoreLedgerEffect: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function increasesBalance(): bool
    {
        return $this === self::Credit;
    }

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Kredit (Tambah Saldo)',
            self::Debit => 'Debit (Kurangi Saldo)',
        };
    }
}
