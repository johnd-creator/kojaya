<?php

namespace App\Http\Responses;

use App\Models\CooperativeMember;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->cooperativeMember || $user->hasRole('Anggota'))) {
            $member = $user->cooperativeMember;
            $status = $member->validation_status ?: $member->status;

            if (in_array($status, [
                CooperativeMember::VALIDATION_PENDING,
                CooperativeMember::VALIDATION_PENDING_REVIEW,
                CooperativeMember::VALIDATION_REVISION,
            ], true)) {
                return redirect()->intended(route('member.onboarding', absolute: false));
            }

            return redirect()->intended(route('member.dashboard', absolute: false));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
