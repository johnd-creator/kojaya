<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $input): void {
            $user->forceFill(['password' => $input['password']])->save();
            app(\App\Services\Auth\MemberSessionService::class)->revokeForUser($user, 'password_reset', $user);
        });
    }
}
