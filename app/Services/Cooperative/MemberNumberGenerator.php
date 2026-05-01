<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;

class MemberNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "KOP-{$year}-";

        $latest = CooperativeMember::query()
            ->where('member_no', 'like', $prefix.'%')
            ->orderByDesc('member_no')
            ->value('member_no');

        $next = $latest ? ((int) substr($latest, -5)) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
