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

        if (! $user) {
            return redirect()->route('dashboard');
        }

        $member = $user->cooperativeMember()->first();
        if ($member === null) {
            return redirect()->route('dashboard');
        }

        if (! $this->isFullyActive($member) && ! $this->isAllowedPendingRoute($request)) {
            return redirect()
                ->route('member.onboarding')
                ->with('warning', 'Akun Anda sedang menunggu penerimaan Admin Koperasi.');
        }

        return $next($request);
    }

    private function isFullyActive(CooperativeMember $member): bool
    {
        return $member->status === CooperativeMember::VALIDATION_ACTIVE
            && $member->validation_status === CooperativeMember::VALIDATION_ACTIVE;
    }

    private function isAllowedPendingRoute(Request $request): bool
    {
        return $request->routeIs([
            'member.dashboard',
            'member.onboarding',
            'member.onboarding.submit',
            'member.onboarding.steps',
            'member.payments.proof',
        ]);
    }
}
