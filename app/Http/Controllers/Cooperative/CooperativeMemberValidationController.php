<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ApproveCooperativeMemberRequest;
use App\Http\Requests\Cooperative\RejectCooperativeMemberRequest;
use App\Http\Requests\Cooperative\RequestCooperativeMemberRevisionRequest;
use App\Http\Requests\Cooperative\ValidateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\MemberValidationService;
use Illuminate\Http\RedirectResponse;

class CooperativeMemberValidationController extends Controller
{
    public function __construct(
        private readonly MemberValidationService $validation,
        private readonly OrganizationScopeService $scopeService,
    ) {}

    public function approve(ValidateCooperativeMemberRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->assertVisible($request, $member);

        if (! $this->validation->canBeVerifiedByAdmin($member)) {
            abort(409, 'Anggota tidak dalam status menunggu verifikasi admin.');
        }

        $this->validation->verifyByAdmin(
            $member,
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Data anggota berhasil diverifikasi. Menunggu approval Pengurus Koperasi.');
    }

    public function approveFinal(ApproveCooperativeMemberRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->assertVisible($request, $member);

        if (! $this->validation->canBeApprovedFinal($member)) {
            abort(409, 'Anggota belum siap untuk approval final.');
        }

        $this->validation->approveFinal(
            $member,
            $request->user(),
            $request->validated('notes'),
        );

        return back()->with('success', 'Anggota berhasil disetujui final dan diaktifkan.');
    }

    public function requestRevision(RequestCooperativeMemberRevisionRequest $request, CooperativeMember $member): RedirectResponse
    {
        $this->assertVisible($request, $member);

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
        $this->assertVisible($request, $member);

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

    private function assertVisible(\Illuminate\Http\Request $request, CooperativeMember $member): void
    {
        $this->scopeService->assertVisible($request->user(), $member);
    }
}
