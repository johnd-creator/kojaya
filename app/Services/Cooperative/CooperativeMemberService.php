<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use Illuminate\Support\Facades\DB;

class CooperativeMemberService
{
    public function __construct(private readonly CooperativeMemberResignationGuard $resignationGuard) {}

    public function resign(CooperativeMember $member): CooperativeMember
    {
        return DB::transaction(function () use ($member): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);

            $this->resignationGuard->assertCanResign($member);

            $member->forceFill([
                'status' => 'RESIGNED',
                'resigned_at' => now()->toDateString(),
            ])->save();

            return $member->refresh();
        });
    }
}
