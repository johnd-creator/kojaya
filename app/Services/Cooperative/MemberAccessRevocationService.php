<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\LegacyTokenClassifier;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        try {
            return DB::transaction(function () use ($member, $user, $actor, $reason): int {
                $count = $this->revokeSelectedMemberTokens($user);
                $this->logRevocation($member, $user, $actor, $reason, $count);

                return $count;
            });
        } catch (Throwable $exception) {
            $this->monitorFailure($member, $user, $reason, $exception);

            throw $exception;
        }
    }

    public function revokeMemberAppTokens(User $user, string $reason, ?User $actor = null, ?CooperativeMember $member = null): int
    {
        try {
            return DB::transaction(function () use ($user, $reason, $actor, $member): int {
                $count = $this->revokeSelectedMemberTokens($user);
                $this->logRevocation($member, $user, $actor, $reason, $count);

                return $count;
            });
        } catch (Throwable $exception) {
            $this->monitorFailure($member, $user, $reason, $exception);

            throw $exception;
        }
    }

    public function revokeAccountWide(User $user, string $reason, ?User $actor = null): int
    {
        try {
            return DB::transaction(function () use ($user, $reason, $actor): int {
                $count = $user->tokens()->count();
                $user->tokens()->delete();

                $this->audit->log('account.tokens.revoked', 'auth.token', null, [
                    'new' => [
                        'affected_user_id' => $user->id,
                        'tokens_revoked' => $count,
                    ],
                    'reason' => $this->safeReason($reason),
                ], AuditContext::forActor($actor));

                return $count;
            });
        } catch (Throwable $exception) {
            Log::critical('Mandatory account token revocation audit failed; transaction rolled back.', [
                'affected_user_id' => $user->id,
                'reason_code' => $this->safeReason($reason),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
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

    private function logRevocation(
        ?CooperativeMember $member,
        User $user,
        ?User $actor,
        string $reason,
        int $count,
    ): void {
        $reasonCode = $this->safeReason($reason);

        $this->audit->log(
            'member.access.revoked',
            $member ? 'cooperative.lifecycle' : 'auth.token',
            $member,
            [
                'new' => [
                    'reason_code' => $reasonCode,
                    'tokens_revoked' => $count,
                    'member_status' => $member?->status,
                    'validation_status' => $member?->validation_status,
                    'affected_user_id' => $member?->user_id ?? $user->id,
                ],
                'reason' => 'Controlled member access revocation.',
            ],
            AuditContext::forActor($actor),
        );
    }

    private function revokeSelectedMemberTokens(User $user): int
    {
        $tokens = $this->memberProfileTokens($user);
        $count = $tokens->count();

        if ($count > 0) {
            $user->tokens()->whereKey($tokens->modelKeys())->delete();
        }

        return $count;
    }

    private function monitorFailure(
        ?CooperativeMember $member,
        User $user,
        string $reason,
        Throwable $exception,
    ): void {
        Log::critical('Mandatory member access revocation audit failed; transaction rolled back.', [
            'member_id' => $member?->getKey(),
            'affected_user_id' => $user->getKey(),
            'reason_code' => $this->safeReason($reason),
            'exception_class' => $exception::class,
        ]);
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
