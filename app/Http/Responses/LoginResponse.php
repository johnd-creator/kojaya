<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->cooperativeMember || $user->hasRole('Anggota'))) {
            $member = $user->cooperativeMember;
            if (! $member) {
                return redirect()->intended(config('fortify.home'));
            }

            $experience = \App\Enums\Cooperative\MemberLifecycleExperience::fromMember($member);

            if ($experience->isActive()) {
                return redirect()->intended(route('member.dashboard', absolute: false));
            }

            if ($experience->isNonActiveLifecycle()) {
                session()->forget('url.intended');

                return redirect()->route('member.onboarding');
            }

            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Status keanggotaan tidak valid.');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
