<?php

namespace App\Enums;

enum MemberStoreDelegateStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Revoked => 'Dicabut',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Revoked => 'gray',
        };
    }
}
