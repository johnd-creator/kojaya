<?php

namespace App\Enums;

enum MemberStoreAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
            self::Closed => 'Ditutup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'amber',
            self::Closed => 'gray',
        };
    }

    public function canPurchase(): bool
    {
        return $this === self::Active;
    }

    public function canReceiveFunding(): bool
    {
        return $this === self::Active || $this === self::Suspended;
    }
}
