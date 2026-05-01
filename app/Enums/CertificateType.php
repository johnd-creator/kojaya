<?php

namespace App\Enums;

enum CertificateType: string
{
    case SIO_K3 = 'SIO_K3';
    case TRAINING = 'TRAINING';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SIO_K3 => 'SIO K3',
            self::TRAINING => 'Training',
            self::OTHER => 'Other',
        };
    }
}
