<?php

declare(strict_types=1);

namespace App\Services\Auth\Sso;

use App\Models\CooperativeMember;
use App\Models\SocialAccount;
use App\Models\User;

final readonly class MemberGoogleSsoMatchResult
{
    public function __construct(
        public bool $success,
        public ?User $user = null,
        public ?SocialAccount $socialAccount = null,
        public ?CooperativeMember $member = null,
        public string $resultCode = '',
        public string $reason = '',
    ) {}

    public static function successExisting(User $user, SocialAccount $socialAccount, ?CooperativeMember $member = null): self
    {
        return new self(
            success: true,
            user: $user,
            socialAccount: $socialAccount,
            member: $member,
            resultCode: GoogleSsoService::RESULT_LOGIN_EXISTING,
            reason: 'existing_provider_binding',
        );
    }

    public static function successFirstTimeLink(User $user, SocialAccount $socialAccount, CooperativeMember $member): self
    {
        return new self(
            success: true,
            user: $user,
            socialAccount: $socialAccount,
            member: $member,
            resultCode: GoogleSsoService::RESULT_LOGIN_LINKED,
            reason: 'first_time_canonical_member_linked',
        );
    }

    public static function failure(string $resultCode, string $reason): self
    {
        return new self(
            success: false,
            user: null,
            socialAccount: null,
            member: null,
            resultCode: $resultCode,
            reason: $reason,
        );
    }

    /**
     * Convert the DTO to an array compatible with legacy consumers.
     *
     * @return array{user: User|null, result: string, social_account?: SocialAccount|null, member?: CooperativeMember|null, reason: string}
     */
    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'result' => $this->success ? $this->resultCode : GoogleSsoService::RESULT_NO_REGISTRATION,
            'social_account' => $this->socialAccount,
            'member' => $this->member,
            'reason' => $this->reason,
        ];
    }
}
