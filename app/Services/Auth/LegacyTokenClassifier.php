<?php

namespace App\Services\Auth;

class LegacyTokenClassifier
{
    /** @var list<string> */
    private const MEMBER = ['profile:read', 'member:read', 'member:write'];

    /** @var list<string> */
    private const ESS = ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read'];

    /** @var list<string> */
    private const TECHNICIAN = ['profile:read', 'work-orders:read', 'work-orders:write', 'work-orders:review'];

    public function classify(?array $abilities): string
    {
        $abilities = array_values(array_unique(array_map('strval', $abilities ?? [])));
        sort($abilities);

        if (in_array('*', $abilities, true)) {
            return 'unsafe';
        }

        foreach ([
            'member' => self::MEMBER,
            'ess' => self::ESS,
            'technician' => self::TECHNICIAN,
        ] as $app => $profile) {
            $expected = $profile;
            sort($expected);
            if ($abilities === $expected) {
                return $app;
            }
        }

        if ($abilities !== [] && in_array('profile:read', $abilities, true)
            && ! array_intersect($abilities, ['member:read', 'member:write', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read', 'work-orders:read', 'work-orders:write', 'work-orders:review', 'cooperative:read', 'cooperative:write'])) {
            $adminAbilities = array_filter($abilities, static fn (string $ability): bool => str_starts_with($ability, 'cooperative.')
                || str_starts_with($ability, 'pos:')
                || str_starts_with($ability, 'reports:'));

            if ($adminAbilities !== [] && count($adminAbilities) === count($abilities) - 1) {
                return 'admin';
            }
        }

        return 'unsafe';
    }
}
