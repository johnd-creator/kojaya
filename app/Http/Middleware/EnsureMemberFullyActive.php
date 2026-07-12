<?php

namespace App\Http\Middleware;

use App\Models\CooperativeMember;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberFullyActive
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->cooperativeMember()->first();

        if (! $member) {
            return redirect()->route('member.dashboard');
        }

        if ($this->isFullyActive($member)) {
            return $next($request);
        }

        $this->logAccessDenied($request, $member);

        return redirect()
            ->route('member.onboarding')
            ->with('warning', $this->messageFor($member->validation_status));
    }

    private function isFullyActive(CooperativeMember $member): bool
    {
        return $member->status === CooperativeMember::VALIDATION_ACTIVE
            && $member->validation_status === CooperativeMember::VALIDATION_ACTIVE;
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

    private function messageFor(string $status): string
    {
        return match ($status) {
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW => 'Onboarding Anda sedang menunggu validasi pengurus. Setelah disetujui, fitur anggota akan terbuka.',
            CooperativeMember::VALIDATION_REVISION => 'Pengurus meminta revisi data. Lengkapi onboarding untuk mengajukan ulang.',
            CooperativeMember::VALIDATION_REJECTED => 'Pendaftaran Anda ditolak. Hubungi admin untuk informasi lebih lanjut.',
            default => 'Status keanggotaan Anda belum aktif.',
        };
    }
}
