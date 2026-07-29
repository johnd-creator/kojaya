<?php

namespace App\Services\Authorization;

use App\Enums\RoleExperience;
use App\Models\User;

class PrimaryRoleResolver
{
    /**
     * @var array<string, RoleExperience>
     */
    private const ROLE_EXPERIENCES = [
        'System Admin' => RoleExperience::SystemAdmin,
        'Admin Pusat' => RoleExperience::AdminPusat,
        'Pengurus Koperasi' => RoleExperience::Pengurus,
        'Manajer Koperasi' => RoleExperience::Manajer,
        'Admin Koperasi' => RoleExperience::AdminKoperasi,
        'Kasir Koperasi' => RoleExperience::Kasir,
    ];

    public function resolve(?User $user): RoleExperience
    {
        if (! $user) {
            return RoleExperience::Generic;
        }

        foreach (self::ROLE_EXPERIENCES as $role => $experience) {
            if ($user->hasRole($role)) {
                return $experience;
            }
        }

        return RoleExperience::Generic;
    }
}
