<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->cooperativeMember) {
            return redirect()->intended('/member');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
