<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
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
