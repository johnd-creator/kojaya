<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\LegacyTokenClassifier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MemberAccessRevocationService
{
    /** @var list<string> */
    private const CONTROLLED_REASONS = [
        'deactivated',
        'rejected',
        'revision_requested',
        'resigned',
        'unlinked',
        'member_lifecycle',
        'account_security',
    ];

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly LegacyTokenClassifier $legacyTokenClassifier,
    ) {}

    /**
     * Revoke only member-application Sanctum tokens for the member's user account.
     *
     * Should be called within DB::afterCommit() when invoked from inside a
     * transaction, so tokens are only removed after the state transition persists.
     *
     * @return int Number of tokens revoked.
     */
    public function revokeFor(CooperativeMember $member, string $reason, ?User $actor = null): int
    {
        $user = $member->user;

        if (! $user) {
            return 0;
        }

        $tokens = $this->memberProfileTokens($user);

        if ($tokens->isEmpty()) {
            return 0;
        }

        $count = $tokens->count();
        $user->tokens()->whereKey($tokens->modelKeys())->delete();

        $this->logRevocation($member, $actor, $reason, $count);

        return $count;
    }

    public function revokeMemberAppTokens(User $user, string $reason, ?User $actor = null, ?CooperativeMember $member = null): int
    {
        $tokens = $this->memberProfileTokens($user);

        $count = $tokens->count();
        if ($count > 0) {
            $user->tokens()->whereKey($tokens->modelKeys())->delete();
        }

        if ($member) {
            $this->logRevocation($member, $actor, $reason, $count);
        }

        return $count;
    }

    public function revokeAccountWide(User $user, string $reason, ?User $actor = null): int
    {
        $count = $user->tokens()->count();
        $user->tokens()->delete();

        $this->audit->log('account.tokens.revoked', 'auth.token', null, [
            'new' => [
                'affected_user_id' => $user->id,
                'tokens_revoked' => $count,
            ],
            'reason' => $this->safeReason($reason),
        ]);

        return $count;
    }

    /**
     * Schedule token revocation after the current transaction commits.
     *
     * Use this variant when calling from inside a DB::transaction() block.
     */
    public function revokeAfterCommit(CooperativeMember $member, string $reason, ?User $actor = null): void
    {
        DB::afterCommit(fn () => $this->revokeFor($member->refresh(), $reason, $actor));
    }

    private function logRevocation(CooperativeMember $member, ?User $actor, string $reason, int $count): void
    {
        $reasonCode = $this->safeReason($reason);

        try {
            $this->audit->log('member.access.revoked', 'cooperative.lifecycle', $member, [
                'new' => [
                    'reason_code' => $reasonCode,
                    'tokens_revoked' => $count,
                    'member_status' => $member->status,
                    'validation_status' => $member->validation_status,
                    'affected_user_id' => $member->user_id,
                ],
                'reason' => 'Controlled member access revocation.',
            ]);
        } catch (\Throwable) {
            $this->audit->log('member_access_revocation.audit_failed', 'cooperative.lifecycle', $member, [
                'new' => ['reason_code' => $reasonCode, 'error' => 'Failed to write revocation audit log.'],
            ]);
        }
    }

    /**
     * Select both explicit member-app tokens and exact legacy member profiles.
     *
     * @return Collection<int, \Laravel\Sanctum\PersonalAccessToken>
     */
    private function memberProfileTokens(User $user): Collection
    {
        $explicit = $user->tokens()->where('token_app', 'member')->get();
        $legacy = $user->tokens()
            ->whereNull('token_app')
            ->get()
            ->filter(fn ($token): bool => $this->legacyTokenClassifier->classify($token->abilities) === 'member');

        return $explicit->merge($legacy)->unique('id')->values();
    }

    private function safeReason(string $reason): string
    {
        return in_array($reason, self::CONTROLLED_REASONS, true) ? $reason : 'member_lifecycle';
    }
}
