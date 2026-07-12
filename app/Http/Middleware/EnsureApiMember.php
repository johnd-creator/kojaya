<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->cooperativeMember;

        if (! $member) {
            return ApiResponse::error(
                'Akun ini belum terhubung ke anggota koperasi.',
                403,
            );
        }

        return $next($request);
    }
}
