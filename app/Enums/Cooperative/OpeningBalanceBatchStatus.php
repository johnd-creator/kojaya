<?php

namespace App\Enums\Cooperative;

enum OpeningBalanceBatchStatus: string
{
    case Draft = 'DRAFT';

    case Posted = 'POSTED';

    case Voided = 'VOID';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Sudah diposting',
            self::Voided => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Posted => 'emerald',
            self::Voided => 'rose',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Posted || $this === self::Voided;
    }
}
