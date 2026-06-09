<?php

namespace App\Http\Middleware;

use App\Models\CooperativeMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->cooperativeMember) {
            return redirect()->route('dashboard');
        }

        $member = $user->cooperativeMember;
        $validationStatus = $member->validation_status ?: $member->status;

        if ($validationStatus !== CooperativeMember::VALIDATION_ACTIVE && ! $this->isAllowedPendingRoute($request)) {
            return redirect()
                ->route('member.onboarding')
                ->with('warning', 'Akun Anda sedang menunggu penerimaan Admin Koperasi.');
        }

        return $next($request);
    }

    private function isAllowedPendingRoute(Request $request): bool
    {
        return $request->routeIs([
            'member.onboarding',
            'member.onboarding.submit',
            'member.onboarding.steps',
        ]);
    }
}
