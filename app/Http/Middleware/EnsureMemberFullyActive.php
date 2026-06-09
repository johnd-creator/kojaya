<?php

namespace App\Http\Middleware;

use App\Models\CooperativeMember;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberFullyActive
{
    /**
     * Statuses yang dianggap "fully active" untuk fitur finansial.
     *
     * @var array<int, string>
     */
    private const ACTIVE_STATUSES = [
        CooperativeMember::VALIDATION_ACTIVE,
    ];

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->cooperativeMember;

        if (! $member) {
            return redirect()->route('member.dashboard');
        }

        $status = $member->validation_status ?: $member->status;

        if (in_array($status, self::ACTIVE_STATUSES, true)) {
            return $next($request);
        }

        $this->logAccessDenied($request, $member, $status);

        return redirect()
            ->route('member.onboarding')
            ->with('warning', $this->messageFor($status));
    }

    private function logAccessDenied(Request $request, CooperativeMember $member, string $status): void
    {
        try {
            $this->audit->log('sso.member.gated_access_denied', 'cooperative.sso', $member, [
                'new' => [
                    'attempted_url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'validation_status' => $status,
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
