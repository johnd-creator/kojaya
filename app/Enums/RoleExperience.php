<?php

namespace App\Enums;

enum RoleExperience: string
{
    case SystemAdmin = 'system-admin';
    case AdminPusat = 'admin-pusat';
    case Pengurus = 'pengurus';
    case Manajer = 'manajer';
    case AdminKoperasi = 'admin-koperasi';
    case Kasir = 'kasir';
    case Generic = 'generic';

    public function isPlatform(): bool
    {
        return in_array($this, [self::SystemAdmin, self::AdminPusat], true);
    }
}
