<?php

declare(strict_types=1);

namespace App\Services\Auth\Sso;

use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Throwable;

class GoogleSsoService
{
    public const PROVIDER = 'google';

    public const RESULT_LOGIN_EXISTING = 'login_existing';

    public const RESULT_LOGIN_LINKED = 'login_linked';

    public const RESULT_NO_REGISTRATION = 'no_registration';

    public function __construct(
        private readonly MemberAccountLinkingService $linking,
        private readonly MemberGoogleSsoMatchingService $matchingService,
        private readonly AuditLogService $audit,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('services.google.sso_enabled', false);
    }

    public function allowsNewMemberRegistration(): bool
    {
        return (bool) config('services.google.allow_new_member_registration', false);
    }

    public function isHostedDomainAllowed(SocialiteUser $googleUser): bool
    {
        $allowedDomains = collect(config('services.google.hosted_domains', []))
            ->map(fn (string $domain): string => strtolower(trim($domain)))
            ->filter()
            ->values();

        if ($allowedDomains->isEmpty()) {
            return true;
        }

        $hostedDomain = strtolower(trim((string) data_get($googleUser->user, 'hd')));
        $emailDomain = Str::of((string) $googleUser->getEmail())->lower()->after('@')->toString();

        return $allowedDomains->contains($hostedDomain)
            || $allowedDomains->contains($emailDomain);
    }

    /**
     * Resolve the User that should be logged in for the given Google identity.
     *
     * @return array{user: User|null, result: string, social_account?: SocialAccount|null, member?: CooperativeMember|null, reason?: string}
     */
    public function resolveUserFromGoogle(SocialiteUser $googleUser): array
    {
        $matchResult = $this->matchingService->resolve($googleUser);

        if ($matchResult->socialAccount) {
            $this->touchSocial(
                $matchResult->socialAccount,
                strtolower(trim((string) $googleUser->getEmail())),
                $googleUser,
            );
        }

        return $matchResult->toArray();
    }

    public function linkAuthenticatedUser(User $user, SocialiteUser $googleUser): SocialAccount
    {
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $providerId = (string) $googleUser->getId();

        $existingSocial = SocialAccount::query()
            ->where('provider', self::PROVIDER)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingSocial && $existingSocial->user_id !== $user->id) {
            $this->logSecurity('sso.google.provider_conflict', [
                'user_id' => $user->id,
                'existing_user_id' => $existingSocial->user_id,
                'email' => $email,
                'incoming_provider_id' => $providerId,
            ]);

            abort(403, 'Akun Google ini sudah terhubung ke pengguna lain.');
        }

        if ($email !== '' && User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->whereKeyNot($user->id)->exists()) {
            $this->logSecurity('sso.google.email_conflict', [
                'user_id' => $user->id,
                'email' => $email,
                'incoming_provider_id' => $providerId,
            ]);

            abort(403, 'Email Google ini sudah digunakan oleh pengguna lain.');
        }

        if ($this->linking->isEmailBoundToOtherSocial($user, $email, $providerId, self::PROVIDER)) {
            $this->logSecurity('sso.google.social_email_conflict', [
                'user_id' => $user->id,
                'email' => $email,
                'incoming_provider_id' => $providerId,
            ]);

            abort(403, 'Akun Google ini tidak dapat ditautkan karena konflik identitas.');
        }

        $social = $this->linking->link($user, $googleUser, self::PROVIDER);

        $isGoogleEmailVerified = (bool) (data_get($googleUser->user, 'email_verified')
            ?? data_get($googleUser->user, 'verified_email')
            ?? false);

        $normalizedGoogleEmail = strtolower(trim((string) $googleUser->getEmail()));
        $normalizedUserEmail = strtolower(trim((string) $user->email));

        if ($isGoogleEmailVerified && $normalizedGoogleEmail !== '' && $normalizedGoogleEmail === $normalizedUserEmail) {
            $this->markEmailVerifiedFromGoogle($user);
        }

        $this->audit->logAuth('sso.google.authenticated_user_linked', $user->id);

        return $social;
    }

    public function recordLogin(SocialAccount $social): void
    {
        $social->forceFill(['last_login_at' => Carbon::now()])->save();

        if ($social->user && $social->user->cooperativeMember) {
            $social->user->cooperativeMember->forceFill([
                'last_sso_login_at' => Carbon::now(),
            ])->save();
        }

        $this->audit->logAuth('sso.google.login_success', $social->user_id);
    }

    public function logFailure(string $reason, array $context = []): void
    {
        $this->logSecurity('sso.google.login_failed', array_merge(['reason' => $reason], $context));
    }

    public function touchSocial(SocialAccount $social, string $email, SocialiteUser $googleUser): void
    {
        $social->forceFill([
            'provider_email' => $email ?: $social->provider_email,
            'provider_name' => $googleUser->getName() ?: $social->provider_name,
            'provider_avatar' => $googleUser->getAvatar() ?: $social->provider_avatar,
        ])->save();
    }

    private function logSecurity(string $action, array $context = []): void
    {
        try {
            $this->audit->log($action, 'auth.sso', null, ['new' => $context]);
        } catch (Throwable $exception) {
            Log::warning('Failed to record SSO audit log', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function markEmailVerifiedFromGoogle(User $user): void
    {
        if ($user->email_verified_at !== null) {
            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
