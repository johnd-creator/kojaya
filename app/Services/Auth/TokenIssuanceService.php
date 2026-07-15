<?php

namespace App\Services\Auth;

use App\Enums\TokenApp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

class TokenIssuanceService
{
    public function __construct(
        private readonly TokenAbilityResolver $abilityResolver,
    ) {}

    public function issue(User $user, TokenApp $app, string $deviceName, ?string $deviceId = null): NewAccessToken
    {
        $abilities = $this->abilityResolver->for($user, $app->value);
        $tokenVersion = (string) config('security.token_version', 'v1');

        return DB::transaction(function () use ($user, $app, $deviceName, $deviceId, $abilities, $tokenVersion): NewAccessToken {
            $token = $user->createToken($deviceName, $abilities);
            $token->accessToken->forceFill([
                'token_app' => $app->value,
                'token_version' => $tokenVersion,
                'device_id' => $deviceId,
                'issued_at' => now(),
            ])->save();

            return $token;
        });
    }
}
