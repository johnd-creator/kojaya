<?php

namespace App\Http\Middleware;

use App\Services\Cooperative\MemberAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsMember
{
    public function __construct(
        private readonly MemberAccessService $memberAccessService,
    ) {}

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

        $memberAccess = $this->memberAccessService->for($member);

        if (! $memberAccess['is_active'] && ! $this->isAllowedRoute($request, $memberAccess)) {
            $targetRoute = $memberAccess['can_access_onboarding']
                ? 'member.onboarding'
                : 'member.dashboard';

            return redirect()
                ->route($targetRoute)
                ->with('warning', 'Akun Anda sedang menunggu penerimaan Admin Koperasi.');
        }

        return $next($request);
    }

    /**
     * @param  array{is_active: bool, can_access_onboarding: bool}  $memberAccess
     */
    private function isAllowedRoute(Request $request, array $memberAccess): bool
    {
        if ($request->routeIs([
            'member.dashboard',
            'member.profile',
            'member.profile.update',
            'member.notifications',
        ])) {
            return true;
        }

        return $memberAccess['can_access_onboarding'] && $request->routeIs([
            'member.onboarding',
            'member.onboarding.submit',
            'member.onboarding.steps',
            'member.payments.proof',
        ]);
    }
}
