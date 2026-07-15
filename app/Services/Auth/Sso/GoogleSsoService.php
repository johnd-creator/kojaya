<?php

namespace App\Services\Auth\Sso;

use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Throwable;

class GoogleSsoService
{
    public const PROVIDER = 'google';

    public const RESULT_LOGIN_EXISTING = 'login_existing';

    public const RESULT_LOGIN_LINKED = 'login_linked';

    public const RESULT_CREATED_PENDING = 'created_pending';

    public const RESULT_NO_REGISTRATION = 'no_registration';

    public function __construct(
        private readonly MemberAccountLinkingService $linking,
        private readonly AuditLogService $audit,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('services.google.sso_enabled', false);
    }

    public function allowsNewMemberRegistration(): bool
    {
        return (bool) config('services.google.allow_new_member_registration', true);
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
     * @return array{user: User|null, result: string, social_account?: SocialAccount, member?: CooperativeMember, reason?: string}
     */
    public function resolveUserFromGoogle(SocialiteUser $googleUser): array
    {
        if (! $googleUser->getId()) {
            return ['user' => null, 'result' => self::RESULT_LOGIN_EXISTING, 'reason' => 'missing_provider_id'];
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $providerId = (string) $googleUser->getId();

        $existingSocial = SocialAccount::query()
            ->where('provider', self::PROVIDER)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingSocial) {
            $this->touchSocial($existingSocial, $email, $googleUser);

            return [
                'user' => $existingSocial->user,
                'result' => self::RESULT_LOGIN_EXISTING,
                'social_account' => $existingSocial,
            ];
        }

        $user = $email !== '' ? User::query()->whereRaw('LOWER(email) = ?', [$email])->first() : null;

        if ($user) {
            $this->audit->logAuth('sso.google.existing_user_matched', $user->id);

            return $this->linkToExistingUser($user, $googleUser, $email, $providerId);
        }

        $member = $email !== ''
            ? CooperativeMember::query()->whereRaw('LOWER(email) = ?', [$email])->first()
            : null;

        if ($member) {
            $this->logSecurity('sso.google.manual_member_link_required', [
                'member_id' => $member->id,
                'organization_id' => $member->organization_id,
            ]);

            return [
                'user' => null,
                'result' => self::RESULT_NO_REGISTRATION,
                'reason' => 'manual_member_link_required',
            ];
        }

        if (! $this->allowsNewMemberRegistration()) {
            $this->logSecurity('sso.google.new_member_registration_blocked', [
                'email' => $email,
            ]);

            return ['user' => null, 'result' => self::RESULT_NO_REGISTRATION, 'reason' => 'registration_disabled'];
        }

        $created = $this->createPendingUserAndMember($googleUser, $email, $providerId);

        return [
            'user' => $created['user'],
            'result' => self::RESULT_CREATED_PENDING,
            'social_account' => $created['social'],
            'member' => $created['member'],
        ];
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

        if ($email !== '' && User::query()->whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($user->id)->exists()) {
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
        $this->markEmailVerifiedFromGoogle($user);
        $this->audit->logAuth('sso.google.authenticated_user_linked', $user->id);

        return $social;
    }

    /**
     * @return array{user: User, result: string, social_account: SocialAccount}
     */
    private function linkToExistingUser(User $user, SocialiteUser $googleUser, string $email, string $providerId): array
    {
        if ($this->linking->isEmailBoundToOtherSocial($user, $email, $providerId, self::PROVIDER)) {
            $this->logSecurity('sso.google.provider_conflict', [
                'user_id' => $user->id,
                'email' => $email,
                'incoming_provider_id' => $providerId,
            ]);

            abort(403, 'Akun Google ini tidak dapat ditautkan karena konflik identitas.');
        }

        $social = $this->linking->link($user, $googleUser, self::PROVIDER);
        $this->markEmailVerifiedFromGoogle($user);

        $this->audit->logAuth('sso.google.existing_user_linked', $user->id);

        return ['user' => $user, 'result' => self::RESULT_LOGIN_LINKED, 'social_account' => $social];
    }

    /**
     * @return array{user: User, social: SocialAccount, member: CooperativeMember}
     */
    private function createPendingUserAndMember(SocialiteUser $googleUser, string $email, string $providerId): array
    {
        $name = $googleUser->getName() ?: 'Anggota Baru';
        $organization = \App\Models\Organization::query()->orderBy('id')->first()
            ?? \App\Models\Organization::factory()->create();
        $memberNo = $this->generateTemporaryMemberNo();

        return DB::transaction(function () use ($googleUser, $email, $name, $organization, $memberNo): array {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(40)),
                'organization_id' => $organization->id,
            ]);
            $this->markEmailVerifiedFromGoogle($user);

            $member = CooperativeMember::query()->create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'member_no' => $memberNo,
                'no_anggota' => $memberNo,
                'nama_anggota' => $name,
                'name' => $name,
                'email' => $email,
                'phone' => null,
                'address' => null,
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
                'sso_provider' => self::PROVIDER,
                'last_sso_login_at' => now(),
            ]);

            $social = $this->linking->link($user, $googleUser, self::PROVIDER);

            $this->audit->logAuth('sso.google.new_pending_member_created', $user->id);

            return ['user' => $user, 'social' => $social, 'member' => $member];
        });
    }

    private function generateTemporaryMemberNo(): string
    {
        do {
            $memberNo = 'TMP'.strtoupper(Str::random(7));
        } while (CooperativeMember::query()
            ->withTrashed()
            ->where(function ($query) use ($memberNo): void {
                $query->where('member_no', $memberNo)
                    ->orWhere('no_anggota', $memberNo);
            })
            ->exists());

        return $memberNo;
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
            'last_login_at' => Carbon::now(),
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
