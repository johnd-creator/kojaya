<?php

namespace App\Services\Auth;

use App\Enums\Cooperative\MemberLifecycleExperience;
use App\Enums\TokenApp;
use App\Models\MemberAuthSession;
use App\Models\MemberSessionRenewal;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Separate member-only protocol. Renewal secrets never authenticate ordinary API requests. */
class MemberSessionService
{
    public function __construct(private readonly TokenIssuanceService $issuer, private readonly TokenAbilityResolver $abilities, private readonly AuditLogService $audit) {}

    public function enabled(): bool
    {
        return (bool) config('member_sessions.enabled', false);
    }

    /** @return array{token: \Laravel\Sanctum\NewAccessToken, session: array<string, mixed>} */
    public function issue(User $user, TokenApp $app, string $deviceName, ?string $deviceId, bool $remembered): array
    {
        abort_unless($this->enabled() && $this->schemaAvailable(), 503, 'Sesi perangkat belum tersedia.');
        abort_unless($app === TokenApp::MEMBER && $this->eligible($user), 403, 'Sesi member tidak tersedia.');

        return DB::transaction(function () use ($user, $deviceName, $deviceId, $remembered): array {
            $now = now()->toImmutable();
            $absolute = $now->addMinutes($this->minutes($remembered ? 'remembered_absolute_minutes' : 'normal_absolute_minutes'));
            $session = MemberAuthSession::query()->create([
                'id' => (string) Str::uuid(), 'user_id' => $user->id, 'device_name' => $deviceName,
                'device_id' => $deviceId, 'remembered' => $remembered, 'security_digest' => $this->fingerprint($user),
                'idle_expires_at' => $now->addMinutes($this->idleMinutes($remembered))->min($absolute),
                'absolute_expires_at' => $absolute,
            ]);
            $bundle = $this->newBundle($session, $user);
            $this->auditEvent($session, 'created', $user);

            return $bundle;
        });
    }

    /** @return array<string, mixed> */
    public function renew(string $credential): array
    {
        abort_unless($this->schemaAvailable(), 503, 'Sesi perangkat belum tersedia.');
        $parts = explode('.', $credential, 2);
        if (count($parts) !== 2 || ! Str::isUuid($parts[0]) || ! preg_match('/\A[a-f0-9]{64}\z/D', $parts[1])) {
            throw new HttpException(401, 'Sesi perangkat tidak valid. Silakan masuk kembali.');
        }
        $found = MemberSessionRenewal::query()->find($parts[0]);
        if (! $found || ! hash_equals($found->secret_hash, hash('sha256', $parts[1]))) {
            throw new HttpException(401, 'Sesi perangkat tidak valid. Silakan masuk kembali.');
        }
        $result = DB::transaction(function () use ($found, $parts): array {
            // Consistent lock order for refresh/revoke and a CAS fallback for SQLite tests.
            $session = MemberAuthSession::query()->lockForUpdate()->find($found->member_auth_session_id);
            $renewal = MemberSessionRenewal::query()->lockForUpdate()->find($found->id);
            if (! $session || ! $renewal || ! hash_equals($renewal->secret_hash, hash('sha256', $parts[1]))) {
                return ['invalid' => true];
            }
            $user = User::query()->find($session->user_id);
            if ($session->revoked_at) {
                return ['invalid' => true];
            }
            if ($renewal->consumed_at) {
                $this->revokeLocked($session, 'renewal_reuse', $user);

                return ['invalid' => true];
            }
            if (! $this->valid($session, $user)) {
                $this->revokeLocked($session, 'expired_or_changed', $user);

                return ['invalid' => true];
            }
            $claimed = MemberSessionRenewal::query()->whereKey($renewal->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            if ($claimed !== 1) {
                $this->revokeLocked($session, 'renewal_reuse', $user);

                return ['invalid' => true];
            }
            $session->forceFill([
                'last_renewed_at' => now(),
                'idle_expires_at' => now()->addMinutes($this->idleMinutes($session->remembered))->min($session->absolute_expires_at),
            ])->save();
            $bundle = $this->newBundle($session, $user);
            $this->auditEvent($session, 'renewed', $user);

            return $bundle;
        }, 3);
        // Throw AFTER the transaction, otherwise a reuse revocation would roll back.
        if (isset($result['invalid'])) {
            throw new HttpException(401, 'Sesi perangkat berakhir. Silakan masuk kembali.');
        }
        $token = $result['token'];

        return ['token_type' => 'Bearer', 'token' => $token->plainTextToken, 'abilities' => $token->accessToken->abilities, 'session' => $result['session']];
    }

    public function accessAllowed(PersonalAccessToken $token): bool
    {
        $id = $token->getAttribute('member_session_id');
        if (! $id) {
            return true;
        }
        if ($token->expires_at && ! $token->expires_at->isFuture()) {
            return false;
        }
        $session = MemberAuthSession::query()->find($id);
        if (! $session || $session->revoked_at) {
            return false;
        }
        if ($token->token_app !== 'member' || $token->tokenable_type !== User::class || (int) $token->tokenable_id !== (int) $session->user_id) {
            return false;
        }
        $user = User::query()->find($session->user_id);
        if (! $this->valid($session, $user) || array_diff($token->abilities ?? [], $this->abilities->for($user, 'member')) !== []) {
            $this->revoke($session->id, 'expired_or_changed', $user);

            return false;
        }

        return true;
    }

    public function revoke(string $id, string $reason, ?User $actor = null, ?AuditContext $context = null): void
    {
        DB::transaction(function () use ($id, $reason, $actor, $context): void {
            $session = MemberAuthSession::query()->lockForUpdate()->find($id);
            if ($session && ! $session->revoked_at) {
                $this->revokeLocked($session, $reason, $actor, $context);
            }
        }, 3);
    }

    public function revokeForUser(User $user, string $reason = 'account_changed', ?User $actor = null, ?AuditContext $context = null): void
    {
        if (! $this->schemaAvailable()) {
            return;
        }
        DB::transaction(function () use ($user, $reason, $actor, $context): void {
            $sessions = MemberAuthSession::query()->where('user_id', $user->id)->whereNull('revoked_at')->orderBy('id')->lockForUpdate()->get();
            foreach ($sessions as $session) {
                $this->revokeLocked($session, $reason, $actor, $context);
            }
        }, 3);
    }

    public function schemaAvailable(): bool
    {
        return Schema::hasTable('member_auth_sessions');
    }

    /** @return array{token: \Laravel\Sanctum\NewAccessToken, session: array<string, mixed>} */
    private function newBundle(MemberAuthSession $session, User $user): array
    {
        $now = now()->toImmutable();
        $expiry = $now->addMinutes($this->minutes('access_minutes'))->min($session->idle_expires_at)->min($session->absolute_expires_at);
        // Preserve a stricter existing global expiry; never silently relax it for this package.
        $global = config('sanctum.expiration');
        if (is_numeric($global) && (int) $global > 0) {
            $expiry = $expiry->min($now->addMinutes((int) $global));
        }
        $token = $this->issuer->issue($user, TokenApp::MEMBER, $session->device_name, $session->device_id);
        $token->accessToken->forceFill(['member_session_id' => $session->id, 'expires_at' => $expiry])->save();
        $secret = bin2hex(random_bytes(32));
        $renewal = MemberSessionRenewal::query()->create(['id' => (string) Str::uuid(), 'member_auth_session_id' => $session->id, 'secret_hash' => hash('sha256', $secret)]);

        return ['token' => $token, 'session' => [
            'protocol' => 2, 'id' => $session->id, 'remembered' => $session->remembered,
            'renewal_token' => $renewal->id.'.'.$secret, 'server_time' => $now->toISOString(),
            'access_expires_at' => $expiry->toISOString(), 'idle_expires_at' => $session->idle_expires_at->toISOString(),
            'absolute_expires_at' => $session->absolute_expires_at->toISOString(),
        ]];
    }

    private function eligible(?User $user): bool
    {
        return $user !== null && ! MemberLifecycleExperience::fromMember($user->cooperativeMember)->isBlocked();
    }

    private function valid(MemberAuthSession $session, ?User $user): bool
    {
        return $this->eligible($user) && $session->idle_expires_at->isFuture() && $session->absolute_expires_at->isFuture()
            && hash_equals($session->security_digest, $this->fingerprint($user));
    }

    private function fingerprint(User $user): string
    {
        $user->load(['cooperativeMember', 'roles', 'permissions']);
        $member = $user->cooperativeMember;

        return hash('sha256', json_encode([
            $user->id, $user->password, $user->email, $user->organization_id,
            $member?->id, $member?->user_id, $member?->organization_id, $member?->status, $member?->validation_status,
            $user->roles->pluck('name')->sort()->values()->all(), $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }

    private function minutes(string $key): int
    {
        $value = config('member_sessions.'.$key);
        if (! is_int($value) || $value < 1) {
            throw new \LogicException('Invalid candidate session lifetime configuration.');
        }

        return $value;
    }

    private function idleMinutes(bool $remembered): int
    {
        return $this->minutes($remembered ? 'remembered_idle_minutes' : 'normal_idle_minutes');
    }

    private function revokeLocked(MemberAuthSession $session, string $reason, ?User $actor, ?AuditContext $context = null): void
    {
        $session->forceFill(['revoked_at' => now(), 'revocation_reason' => $reason])->save();
        PersonalAccessToken::query()->where('member_session_id', $session->id)->delete();
        $this->auditEvent($session, 'revoked', $actor, $context);
    }

    private function auditEvent(MemberAuthSession $session, string $event, ?User $actor, ?AuditContext $context = null): void
    {
        $context ??= app()->bound('request') ? AuditContext::fromHttp(app('request'), $actor) : AuditContext::forActor($actor);
        $this->audit->log('member.session.'.$event, 'auth.session', null, ['new' => ['session_id' => $session->id, 'affected_user_id' => $session->user_id, 'remembered' => $session->remembered], 'reason' => $session->revocation_reason], $context);
    }
}
