<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;

class MemberNumberGenerator
{
    public function generate(): string
    {
        $candidates = CooperativeMember::query()
            ->withTrashed()
            ->where('no_anggota', 'like', 'KOP-%')
            ->pluck('no_anggota');

        $max = $candidates
            ->filter(fn (string $value): bool => preg_match('/^KOP-\d+$/', $value))
            ->map(fn (string $value) => (int) substr($value, 4))
            ->max();

        $next = ($max ?? 0) + 1;

        return 'KOP-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
