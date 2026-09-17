<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
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

            abort(403, 'Status keanggotaan tidak valid.');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
