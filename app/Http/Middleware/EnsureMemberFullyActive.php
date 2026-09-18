<?php

namespace App\Http\Middleware;

use App\Models\CooperativeMember;
use App\Services\AuditLogService;
use App\Services\Cooperative\MemberAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberFullyActive
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly MemberAccessService $memberAccessService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->cooperativeMember()->first();

        if (! $member) {
            return redirect()->route('member.dashboard');
        }

        $experience = $this->memberAccessService->experience($member);

        if ($experience->isActive()) {
            return $next($request);
        }

        $this->logAccessDenied($request, $member);

        if ($experience->isBlocked()) {
            abort(403, 'Status keanggotaan tidak valid.');
        }

        $memberAccess = $this->memberAccessService->for($member);
        $targetRoute = ($memberAccess && $memberAccess['can_access_onboarding'])
            ? 'member.onboarding'
            : 'member.dashboard';

        return redirect()
            ->route($targetRoute)
            ->with('warning', $this->messageForExperience($experience));
    }

    private function logAccessDenied(Request $request, CooperativeMember $member): void
    {
        try {
            $this->audit->log('sso.member.gated_access_denied', 'cooperative.sso', $member, [
                'new' => [
                    'attempted_url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'validation_status' => $member->validation_status,
                    'member_status' => $member->status,
                ],
            ]);
        } catch (\Throwable) {
            // best effort
        }
    }

    private function messageForExperience(\App\Enums\Cooperative\MemberLifecycleExperience $experience): string
    {
        return match ($experience) {
            \App\Enums\Cooperative\MemberLifecycleExperience::WaitingVerification => 'Pendaftaran Anda sedang menunggu verifikasi awal Admin Koperasi.',
            \App\Enums\Cooperative\MemberLifecycleExperience::UnderReview => 'Onboarding Anda sedang menunggu validasi pengurus. Setelah disetujui, fitur anggota akan terbuka.',
            \App\Enums\Cooperative\MemberLifecycleExperience::RevisionRequired => 'Pengurus meminta revisi data. Lengkapi onboarding untuk mengajukan ulang.',
            \App\Enums\Cooperative\MemberLifecycleExperience::Rejected => 'Pendaftaran Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
            default => 'Status keanggotaan Anda belum aktif.',
        };
    }
}
