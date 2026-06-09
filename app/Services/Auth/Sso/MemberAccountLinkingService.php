<?php

namespace App\Services\Auth\Sso;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class MemberAccountLinkingService
{
    public function link(User $user, SocialiteUser $googleUser, string $provider): SocialAccount
    {
        $email = $googleUser->getEmail() ? strtolower($googleUser->getEmail()) : null;
        $accessToken = $googleUser->token ?? null;
        $refreshToken = $googleUser->refreshToken ?? null;

        return SocialAccount::query()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => (string) $googleUser->getId(),
            ],
            [
                'user_id' => $user->id,
                'provider_email' => $email,
                'provider_name' => $googleUser->getName(),
                'provider_avatar' => $googleUser->getAvatar(),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => $googleUser->attributes['token_type'] ?? 'Bearer',
                'expires_in' => $googleUser->expiresIn ?? null,
                'linked_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
            ]
        );
    }

    public function unlink(User $user, string $provider, string $providerId): bool
    {
        return (bool) SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->delete();
    }

    public function isEmailBoundToOtherSocial(User $user, string $email, string $providerId, string $provider): bool
    {
        if ($email === '') {
            return false;
        }

        return SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', '!=', $providerId)
            ->whereHas('user', function ($query) use ($user, $email): void {
                $query->where('id', '!=', $user->id)
                    ->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->exists();
    }
}
