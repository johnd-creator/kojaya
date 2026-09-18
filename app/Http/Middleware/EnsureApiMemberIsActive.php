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
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly \App\Services\Cooperative\MemberAccessService $memberAccessService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->cooperativeMember()->first();

        if (! $member) {
            return ApiResponse::error(
                'Akun ini belum terhubung ke anggota koperasi.',
                403,
            );
        }

        $experience = $this->memberAccessService->experience($member);

        if ($experience->isActive()) {
            return $next($request);
        }

        $this->logAccessDenied($request, $member, (string) $member->validation_status);

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
