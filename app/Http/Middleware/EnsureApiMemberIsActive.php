<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Models\CooperativeMember;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiMemberIsActive
{
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
            return ApiResponse::error(
                'Akun ini belum terhubung ke anggota koperasi.',
                403,
            );
        }

        $status = $member->validation_status ?: $member->status;

        if (in_array($status, self::ACTIVE_STATUSES, true) && $member->status === CooperativeMember::VALIDATION_ACTIVE) {
            return $next($request);
        }

        $this->logAccessDenied($request, $member, $status);

        return ApiResponse::error(
            'Keanggotaan Anda belum aktif. Fitur ini hanya tersedia untuk anggota aktif.',
            403,
            code: ApiErrorCode::MemberNotActive,
        );
    }

    private function logAccessDenied(Request $request, CooperativeMember $member, string $status): void
    {
        try {
            $this->audit->log('api.member.gated_access_denied', 'cooperative.api', $member, [
                'new' => [
                    'attempted_url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'validation_status' => $status,
                    'member_status' => $member->status,
                ],
            ]);
        } catch (\Throwable) {
            // best effort
        }
    }
}
