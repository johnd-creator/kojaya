<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\LegacyTokenClassifier;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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
        'delete_access',
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
     * Two invocation modes:
     *
     * 1. Atomic lifecycle operation (preferred for Document 05):
     *    Call this method directly inside an outer DB::transaction(). The nested
     *    transaction participates in the outer one via savepoints, so token
     *    deletion, the mandatory revocation audit, the state transition, role
     *    removal, and any preceding lifecycle audit are all atomic. A mandatory
     *    audit failure rolls back the entire outer transaction — no partial state,
     *    role, token, or audit survives.
     *
     * 2. Standalone operation:
     *    Call this method outside any transaction. The nested transaction commits
     *    independently and the mandatory revocation audit is guaranteed.
     *
     * Callers that are part of a wider business operation (e.g. member lifecycle)
     * must pass the shared AuditContext so every audit in that operation shares a
     * single actor, organization, and correlation ID. Standalone callers may omit
     * it, in which case a context is derived once from the actor/request.
     *
     * @return int Number of tokens revoked.
     */
    public function revokeFor(CooperativeMember $member, string $reason, ?User $actor = null, ?AuditContext $context = null): int
    {
        $user = $member->user;

        if (! $user) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($member, $user, $actor, $reason, $context): int {
                $count = $this->revokeSelectedMemberTokens($user);
                $this->logRevocation($member, $user, $actor, $reason, $count, $context);

                return $count;
            });
        } catch (Throwable $exception) {
            $this->monitorFailure($member, $user, $reason, $exception);

            throw $exception;
        }
    }

    public function revokeMemberAppTokens(
        User $user,
        string $reason,
        ?User $actor = null,
        ?CooperativeMember $member = null,
        ?AuditContext $context = null,
    ): int {
        try {
            return DB::transaction(function () use ($user, $reason, $actor, $member, $context): int {
                $count = $this->revokeSelectedMemberTokens($user);
                $this->logRevocation($member, $user, $actor, $reason, $count, $context);

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
                ], $this->contextFor($actor));

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
     * Use this variant ONLY when the business requirement explicitly defers
     * revocation until after the outer transaction commits. The caller accepts
     * that revocation is no longer atomic with the preceding state transition:
     * if the post-commit revocation fails, the state change persists but tokens
     * remain active until a recovery path runs.
     *
     * Pass the shared AuditContext so the post-commit audit keeps the same
     * correlation ID as the enclosing operation.
     */
    public function revokeAfterCommit(CooperativeMember $member, string $reason, ?User $actor = null, ?AuditContext $context = null): void
    {
        DB::afterCommit(fn () => $this->revokeFor($member->refresh(), $reason, $actor, $context));
    }

    private function logRevocation(
        ?CooperativeMember $member,
        User $user,
        ?User $actor,
        string $reason,
        int $count,
        ?AuditContext $context = null,
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
            $context ?? $this->contextFor($actor),
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

    private function contextFor(?User $actor): AuditContext
    {
        $request = app()->bound('request') ? app('request') : null;

        return $request instanceof Request
            ? AuditContext::fromHttp($request, $actor)
            : AuditContext::forActor($actor);
    }
}
