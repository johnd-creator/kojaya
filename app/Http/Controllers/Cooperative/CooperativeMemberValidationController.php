<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\RejectCooperativeMemberRequest;
use App\Http\Requests\Cooperative\RequestCooperativeMemberRevisionRequest;
use App\Http\Requests\Cooperative\ValidateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberValidationService;
use Illuminate\Http\RedirectResponse;

class CooperativeMemberValidationController extends Controller
{
    public function __construct(
        private readonly MemberValidationService $validation,
    ) {}

    public function approve(ValidateCooperativeMemberRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->ensurePending($member);

        $this->validation->approve(
            $member,
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Anggota berhasil divalidasi dan diaktifkan.');
    }

    public function requestRevision(RequestCooperativeMemberRevisionRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->ensurePending($member);

        $this->validation->requestRevision(
            $member,
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Permintaan revisi telah dikirim ke anggota.');
    }

    public function reject(RejectCooperativeMemberRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->ensurePending($member);

        $this->validation->reject(
            $member,
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Anggota ditolak. Status diperbarui.');
    }

    private function ensurePending(CooperativeMember $member): void
    {
        if (! $this->validation->isPendingReview($member)) {
            abort(409, 'Anggota tidak dalam status menunggu validasi.');
        }
    }
}
