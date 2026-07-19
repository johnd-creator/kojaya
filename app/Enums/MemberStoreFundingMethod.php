<?php

namespace App\Enums;

enum MemberStoreFundingMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer',
        };
    }

    public function requiresReview(): bool
    {
        return $this === self::Transfer;
    }
}
