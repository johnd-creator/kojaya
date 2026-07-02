<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberResignationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberResignationRequestService
{
    public function __construct(
        private readonly CooperativeMemberResignationGuard $resignationGuard,
        private readonly CooperativeMemberService $memberService,
    ) {}

    public function latestFor(CooperativeMember $member): ?MemberResignationRequest
    {
        return $member->resignationRequests()
            ->latest('id')
            ->first();
    }

    public function submit(CooperativeMember $member, array $data, ?User $requester): MemberResignationRequest
    {
        if ($member->status === 'RESIGNED') {
            throw ValidationException::withMessages([
                'member' => 'Anggota sudah dalam status pengunduran diri.',
            ]);
        }

        $existing = $member->resignationRequests()
            ->where('status', MemberResignationRequest::STATUS_PENDING)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'member' => 'Masih ada pengajuan pengunduran diri yang menunggu ditinjau.',
            ]);
        }

        return MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $requester?->id,
            'status' => MemberResignationRequest::STATUS_PENDING,
            'reason' => $data['reason'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'requested_at' => now(),
        ]);
    }

    public function cancel(MemberResignationRequest $request): MemberResignationRequest
    {
        if ($request->status !== MemberResignationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan yang sudah diproses tidak dapat dibatalkan.',
            ]);
        }

        $request->forceFill([
            'status' => MemberResignationRequest::STATUS_CANCELLED,
        ])->save();

        return $request->refresh();
    }

    public function approve(MemberResignationRequest $request, User $reviewer, ?string $notes = null): CooperativeMember
    {
        if ($request->status !== MemberResignationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $notes): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($request->cooperative_member_id);

            // Surface any financial blockers before finalizing the resignation.
            $this->resignationGuard->assertCanResign($member);

            $request->forceFill([
                'status' => MemberResignationRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();

            return $this->memberService->resign($member);
        });
    }

    public function reject(MemberResignationRequest $request, User $reviewer, ?string $notes = null): MemberResignationRequest
    {
        if ($request->status !== MemberResignationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        $request->forceFill([
            'status' => MemberResignationRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ])->save();

        return $request->refresh();
    }
}
