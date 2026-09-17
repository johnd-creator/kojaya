<?php

declare(strict_types=1);

namespace App\Services\Auth\Sso;

use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class MemberGoogleSsoMatchingService
{
    public const CODE_SUCCESS_EXISTING = 'SUCCESS_EXISTING_USER';

    public const CODE_SUCCESS_LINKED = 'SUCCESS_FIRST_TIME_LINK';

    public const CODE_MISSING_PROVIDER_ID = 'GOOGLE_PROVIDER_ID_MISSING';

    public const CODE_EMAIL_NOT_VERIFIED = 'GOOGLE_EMAIL_NOT_VERIFIED';

    public const CODE_MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND_FOR_GOOGLE_ACCOUNT';

    public const CODE_MEMBER_AMBIGUOUS = 'MEMBER_SSO_AMBIGUOUS';

    public const CODE_MEMBER_INELIGIBLE = 'MEMBER_NOT_ELIGIBLE_FOR_SSO';

    public const CODE_PROVIDER_ALREADY_LINKED = 'GOOGLE_ACCOUNT_ALREADY_LINKED';

    public const CODE_MEMBER_USER_CONFLICT = 'MEMBER_USER_LINK_CONFLICT';

    public const CODE_USER_EMAIL_CONFLICT = 'USER_EMAIL_CONFLICT';

    public const CODE_CONCURRENT_CONFLICT = 'GOOGLE_LINK_CONCURRENT_CONFLICT';

    public const CODE_AUDIT_FAILED = 'GOOGLE_LINK_AUDIT_FAILED';

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Resolve and authenticate or match a Google user identity to a system User or canonical CooperativeMember.
     */
    public function resolve(SocialiteUser $googleUser): MemberGoogleSsoMatchResult
    {
        $providerId = (string) $googleUser->getId();
        if ($providerId === '') {
            return MemberGoogleSsoMatchResult::failure(
                'failed',
                self::CODE_MISSING_PROVIDER_ID,
            );
        }

        // STEP 1: Lookup existing SocialAccount binding (provider = google, provider_id)
        $existingSocial = SocialAccount::query()
            ->where('provider', GoogleSsoService::PROVIDER)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingSocial) {
            $user = $existingSocial->user;
            if (! $user) {
                Log::warning('Orphaned social account detected', [
                    'provider' => GoogleSsoService::PROVIDER,
                    'provider_id' => $providerId,
                ]);

                return MemberGoogleSsoMatchResult::failure(
                    'conflict',
                    self::CODE_PROVIDER_ALREADY_LINKED,
                );
            }

            // Touch social account login timestamp
            $existingSocial->forceFill(['last_login_at' => Carbon::now()])->save();
            if ($user->cooperativeMember) {
                $user->cooperativeMember->forceFill(['last_sso_login_at' => Carbon::now()])->save();
            }

            $this->audit->logAuth('sso.google.login_success', $user->id);

            return MemberGoogleSsoMatchResult::successExisting(
                $user,
                $existingSocial,
                $user->cooperativeMember,
            );
        }

        // STEP 2: First-time Google Match (NO existing provider binding)
        $email = (string) $googleUser->getEmail();
        $isVerified = (bool) (data_get($googleUser->user, 'email_verified')
            ?? data_get($googleUser->user, 'verified_email')
            ?? false);

        if ($email === '' || ! $isVerified) {
            return MemberGoogleSsoMatchResult::failure(
                'failed',
                self::CODE_EMAIL_NOT_VERIFIED,
            );
        }

        $normalizedEmail = strtolower(trim($email));

        // Exact match against canonical cooperative_members.email
        $candidates = CooperativeMember::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->get();

        if ($candidates->isEmpty()) {
            // NO MEMBER MATCHED -> Controlled failure (NO member created, NO user created)
            return MemberGoogleSsoMatchResult::failure(
                'no_member_matched',
                self::CODE_MEMBER_NOT_FOUND,
            );
        }

        if ($candidates->count() > 1) {
            // Ambiguous duplicate member record
            return MemberGoogleSsoMatchResult::failure(
                'ambiguous_member',
                self::CODE_MEMBER_AMBIGUOUS,
            );
        }

        /** @var CooperativeMember $candidateMember */
        $candidateMember = $candidates->first();

        // Check eligibility
        if (! $this->isMemberEligibleForSso($candidateMember)) {
            return MemberGoogleSsoMatchResult::failure(
                'ineligible_member',
                self::CODE_MEMBER_INELIGIBLE,
            );
        }

        // Check member.user_id conflict: if member already has user_id, fail closed for guest callback
        if ($candidateMember->user_id !== null) {
            return MemberGoogleSsoMatchResult::failure(
                'member_user_conflict',
                self::CODE_MEMBER_USER_CONFLICT,
            );
        }

        // Check existing users.email collision
        if (User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->exists()) {
            return MemberGoogleSsoMatchResult::failure(
                'user_email_conflict',
                self::CODE_USER_EMAIL_CONFLICT,
            );
        }

        // Execute atomic first-time linking transaction
        try {
            return $this->executeFirstTimeLink(
                $candidateMember,
                $googleUser,
                $normalizedEmail,
                $providerId,
            );
        } catch (Throwable $e) {
            Log::error('Google SSO first-time link failed', [
                'error' => $e->getMessage(),
                'member_id' => $candidateMember->id,
            ]);

            return MemberGoogleSsoMatchResult::failure(
                'failed',
                $e->getMessage() === self::CODE_AUDIT_FAILED ? self::CODE_AUDIT_FAILED : self::CODE_CONCURRENT_CONFLICT,
            );
        }
    }

    /**
     * Check if member status & validation_status are eligible for SSO identity matching.
     */
    public function isMemberEligibleForSso(CooperativeMember $member): bool
    {
        $status = (string) $member->status;
        $validationStatus = (string) $member->validation_status;

        if ($status === CooperativeMember::VALIDATION_PENDING) {
            return in_array($validationStatus, [
                CooperativeMember::VALIDATION_PENDING,
                CooperativeMember::VALIDATION_PENDING_REVIEW,
            ], true);
        }

        if ($status === CooperativeMember::VALIDATION_ACTIVE) {
            return $validationStatus === CooperativeMember::VALIDATION_ACTIVE;
        }

        return false;
    }

    /**
     * Atomically link member, create User, assign role, create SocialAccount, and record audit log.
     */
    private function executeFirstTimeLink(
        CooperativeMember $candidateMember,
        SocialiteUser $googleUser,
        string $normalizedEmail,
        string $providerId,
    ): MemberGoogleSsoMatchResult {
        return DB::transaction(function () use ($candidateMember, $googleUser, $normalizedEmail, $providerId): MemberGoogleSsoMatchResult {
            // Lock target member row for update
            /** @var CooperativeMember|null $member */
            $member = CooperativeMember::query()
                ->whereKey($candidateMember->id)
                ->lockForUpdate()
                ->first();

            if (! $member || ! $this->isMemberEligibleForSso($member)) {
                throw new RuntimeException(self::CODE_MEMBER_INELIGIBLE);
            }

            if ($member->user_id !== null) {
                throw new RuntimeException(self::CODE_MEMBER_USER_CONFLICT);
            }

            // Recheck provider_id hasn't been claimed concurrently
            if (SocialAccount::query()
                ->where('provider', GoogleSsoService::PROVIDER)
                ->where('provider_id', $providerId)
                ->exists()) {
                throw new RuntimeException(self::CODE_PROVIDER_ALREADY_LINKED);
            }

            // Recheck users.email collision
            if (User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->exists()) {
                throw new RuntimeException(self::CODE_USER_EMAIL_CONFLICT);
            }

            // 1. Create User from canonical member record
            $canonicalName = trim((string) ($member->nama_anggota ?: $member->name));
            if ($canonicalName === '') {
                $canonicalName = 'Anggota Koperasi';
            }

            $user = User::query()->create([
                'name' => $canonicalName,
                'email' => strtolower(trim((string) $member->email)),
                'organization_id' => $member->organization_id,
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => Carbon::now(),
            ]);

            // 2. Assign Anggota role only
            $role = Role::query()->firstOrCreate(['name' => 'Anggota']);
            $user->assignRole($role);

            // 3. Link CooperativeMember to User (WITHOUT changing status/validation_status)
            $member->user_id = $user->id;
            $member->sso_provider = GoogleSsoService::PROVIDER;
            $member->last_sso_login_at = Carbon::now();
            $member->save();

            // 4. Create SocialAccount (without persisting OAuth tokens)
            $social = SocialAccount::query()->create([
                'user_id' => $user->id,
                'provider' => GoogleSsoService::PROVIDER,
                'provider_id' => $providerId,
                'provider_email' => $normalizedEmail,
                'provider_name' => $googleUser->getName(),
                'provider_avatar' => $googleUser->getAvatar(),
                'access_token' => null,
                'refresh_token' => null,
                'token_type' => null,
                'expires_in' => null,
                'linked_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
            ]);

            // 5. Mandatory safe audit log
            try {
                $this->audit->log(
                    action: 'member.google_sso_linked',
                    module: 'auth.sso',
                    subject: $member,
                    changes: [
                        'new' => [
                            'member_id' => $member->id,
                            'user_id' => $user->id,
                            'organization_id' => $member->organization_id,
                            'provider' => GoogleSsoService::PROVIDER,
                            'provider_id_hash' => hash('sha256', $providerId),
                        ],
                    ],
                );
            } catch (Throwable $e) {
                Log::error('Mandatory SSO audit failed', ['error' => $e->getMessage()]);
                throw new RuntimeException(self::CODE_AUDIT_FAILED, 0, $e);
            }

            return MemberGoogleSsoMatchResult::successFirstTimeLink($user, $social, $member);
        });
    }
}
