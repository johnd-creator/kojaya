<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CooperativeMemberService
{
    public function __construct(
        private readonly CooperativeMemberResignationGuard $resignationGuard,
        private readonly MemberStatusTransitionService $transitions,
    ) {}

    public function resign(CooperativeMember $member, User $actor): CooperativeMember
    {
        return DB::transaction(function () use ($member, $actor): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);

            $this->resignationGuard->assertCanResign($member);

            return $this->transitions->resign($member, $actor);
        });
    }
}
