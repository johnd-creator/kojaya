<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class MemberAccessRevocationService
{
    public function __construct(
        private readonly AuditLogService $audit,
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

        $tokens = $user->tokens()->get()->filter(
            fn ($token): bool => $token->can('member:read') || $token->can('member:write'),
        );

        if ($tokens->isEmpty()) {
            return 0;
        }

        $count = $tokens->count();
        $user->tokens()->whereKey($tokens->modelKeys())->delete();

        $this->logRevocation($member, $actor, $reason, $count);

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
        try {
            $this->audit->log('member.access.revoked', 'cooperative.lifecycle', $member, [
                'new' => [
                    'reason' => $reason,
                    'tokens_revoked' => $count,
                    'member_status' => $member->status,
                    'validation_status' => $member->validation_status,
                    'affected_user_id' => $member->user_id,
                ],
                'reason' => $reason,
            ]);
        } catch (\Throwable) {
            $this->audit->log('member_access_revocation.audit_failed', 'cooperative.lifecycle', $member, [
                'new' => ['reason' => $reason, 'error' => 'Failed to write revocation audit log.'],
            ]);
        }
    }
}
