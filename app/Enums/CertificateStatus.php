<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case VALID = 'VALID';
    case EXPIRED = 'EXPIRED';
    case REVOKED = 'REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::EXPIRED => 'Expired',
            self::REVOKED => 'Revoked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VALID => 'green',
            self::EXPIRED => 'red',
            self::REVOKED => 'gray',
        };
    }
}
