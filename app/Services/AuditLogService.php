<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuditLogService
{
    public function log(
        string $action,
        string $module,
        ?Model $subject = null,
        ?array $changes = [],
        ?AuditContext $context = null,
    ): AuditLog {
        return AuditLog::create($this->payload($action, $module, $subject, $changes, $context));
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function payload(
        string $action,
        string $module,
        ?Model $subject,
        array $changes,
        ?AuditContext $context = null,
    ): array {
        $unknownKeys = array_values(array_diff(array_keys($changes), ['old', 'new', 'reason']));

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(sprintf(
                'Audit changes must use only the canonical keys [old, new, reason]. Unknown key(s): [%s].',
                implode(', ', $unknownKeys),
            ));
        }

        $context ??= AuditContext::fromCurrentRequest();
        $organizationId = $subject?->getAttribute('organization_id') ?? $context->organizationId;
        if ($organizationId === null && $subject && method_exists($subject, 'member')) {
            $organizationId = $subject->member?->organization_id;
        }
        if ($organizationId === null && $subject && method_exists($subject, 'user')) {
            $organizationId = $subject->user?->organization_id;
        }
        $oldValues = $changes['old'] ?? null;
        $newValues = $changes['new'] ?? null;

        return [
            'correlation_id' => $context->correlationId,
            'user_id' => $context->actorId,
            'organization_id' => $organizationId,
            'actor_roles' => $context->actorRoles,
            'source' => $context->source,
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'reason' => $this->redactReason($changes['reason'] ?? null),
            'ip_address' => $context->ip,
            'user_agent' => $context->userAgent,
            'occurred_at' => now(),
        ];
    }

    public function logAuth(string $action, string|int|null $userId = null, ?AuditContext $context = null): AuditLog
    {
        $context ??= AuditContext::fromCurrentRequest();

        [$context, $subject] = $this->resolveAuthActorAndSubject($userId, $context);

        $payload = $this->payload($action, 'auth', $subject, [], $context);

        // Authentication organization always follows the actor context so an
        // affected user's organization can never bleed into another actor's record.
        $payload['organization_id'] = $context->organizationId;

        return AuditLog::create($payload);
    }

    /**
     * Resolve a truthful actor context and the affected-user subject for an auth event.
     *
     * The affected user (userId) is the subject of the authentication. When the
     * caller has not supplied an authenticated actor, the affected user is also the
     * actor of their own authentication, so a truthful context is rebuilt from that
     * user's real roles and organization. A pre-existing, differing actor is kept
     * and the affected user is recorded as the subject instead of mixing identities.
     *
     * @return array{0: AuditContext, 1: ?Model}
     */
    private function resolveAuthActorAndSubject(string|int|null $userId, AuditContext $context): array
    {
        if ($userId === null) {
            return [$context, null];
        }

        if ($context->actorId !== null && (string) $userId === (string) $context->actorId) {
            return [$context, null];
        }

        if ($context->actorId === null) {
            return [$this->buildContextFromAffectedUser($userId, $context), null];
        }

        // Actor already exists and differs: record the affected user as the subject.
        // Guard the DB lookup against identifiers that are not valid bigint candidates
        // so PostgreSQL never receives an invalid input syntax error.
        if (! $this->isValidUserIdentifier($userId)) {
            return [$context, null];
        }

        return [$context, User::query()->find($userId)];
    }

    private function buildContextFromAffectedUser(string|int $userId, AuditContext $context): AuditContext
    {
        // Guard the DB lookup against identifiers that are not valid bigint
        // candidates. PostgreSQL throws "invalid input syntax for type bigint"
        // on non-numeric strings; SQLite tolerates them but returns null. We
        // normalize to a null actor for any invalid identifier without hitting
        // the database, so behavior is identical across drivers.
        if (! $this->isValidUserIdentifier($userId)) {
            return $this->anonymousContext($context);
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            // Unknown user id (e.g. a credential that resolved no account). Do not
            // fabricate an actor: audit_logs.user_id is a foreign key and cannot
            // hold a nonexistent identity, and inventing roles/organization would
            // be untruthful. Keep actor null so the failure is recorded without a
            // fake actor identity.
            return $this->anonymousContext($context);
        }

        return new AuditContext(
            actorId: $user->getKey(),
            actorRoles: $user->getRoleNames()->values()->all(),
            organizationId: $user->organization_id,
            correlationId: $context->correlationId,
            ip: $context->ip,
            userAgent: $context->userAgent,
            source: $context->source,
        );
    }

    /**
     * PostgreSQL signed BIGINT maximum value (9223372036854775807).
     * Identifiers strictly greater than this must be rejected before any
     * primary-key lookup so PostgreSQL never raises a numeric out-of-range error.
     */
    private const BIGINT_MAX = '9223372036854775807';

    /**
     * Determine whether the identifier is a valid candidate for a bigint
     * primary key lookup. Only positive integers or numeric integer strings
     * representing positive integers within the signed BIGINT range qualify.
     *
     * Rejected (no database query is ever issued for these):
     * - empty string;
     * - negative integer / numeric string;
     * - decimal, scientific notation, whitespace, leading "+";
     * - non-numeric strings;
     * - all-zero variants ("0", "00", "0000");
     * - numeric strings exceeding the PostgreSQL signed BIGINT maximum.
     *
     * Leading zeros on otherwise valid positive values are tolerated
     * (e.g. "0001" represents the positive integer 1).
     */
    private function isValidUserIdentifier(string|int $userId): bool
    {
        if (is_int($userId)) {
            return $userId > 0;
        }

        if ($userId === '') {
            return false;
        }

        if (! ctype_digit($userId)) {
            return false;
        }

        $normalized = ltrim($userId, '0');

        if ($normalized === '') {
            return false;
        }

        return $this->decimalStringWithinBigintRange($normalized);
    }

    /**
     * Lexical decimal comparison against the PostgreSQL signed BIGINT maximum.
     * The normalized input is guaranteed to be a non-empty decimal digit string
     * with no leading zeros, so a length-then-lexical comparison is sound.
     */
    private function decimalStringWithinBigintRange(string $normalized): bool
    {
        $maxLength = strlen(self::BIGINT_MAX);

        if (strlen($normalized) < $maxLength) {
            return true;
        }

        if (strlen($normalized) > $maxLength) {
            return false;
        }

        return strcmp($normalized, self::BIGINT_MAX) <= 0;
    }

    private function anonymousContext(AuditContext $context): AuditContext
    {
        return new AuditContext(
            actorId: null,
            actorRoles: [],
            organizationId: null,
            correlationId: $context->correlationId,
            ip: $context->ip,
            userAgent: $context->userAgent,
            source: $context->source,
        );
    }

    public function logModelEvent(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?AuditContext $context = null,
    ): AuditLog {
        return AuditLog::create($this->payload($action, $this->getModuleName($model), $model, [
            'old' => $oldValues,
            'new' => $newValues,
        ], $context));
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            $redacted[$key] = $this->isSensitiveKey((string) $key)
                ? '[REDACTED]'
                : $this->redact($item);
        }

        return $redacted;
    }

    private function redactReason(mixed $reason): ?string
    {
        if (! is_string($reason) || trim($reason) === '') {
            return null;
        }

        return preg_match('/identity|nik|npwp|rekening|bank|token|secret|password|credential|authorization|header|ciphertext|blind[ _-]?index|gateway[ _-]?payload|webhook[ _-]?payload|raw[ _-]?payload|\d{6,}/i', $reason) === 1
            ? '[REDACTED]'
            : Str::limit(trim($reason), 255, '');
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($key)) ?? strtolower($key);

        return in_array($normalized, [
            'identity',
            'identitynumber',
            'nik',
            'npwp',
            'norekening',
            'bankaccount',
            'bankaccountnumber',
            'bankaccountholder',
            'accountholder',
            'accountnumber',
            'cardnumber',
            'token',
            'accesstoken',
            'refreshtoken',
            'idtoken',
            'apikey',
            'clientsecret',
            'privatekey',
            'serverkey',
            'signaturekey',
            'authorization',
            'authorizationheader',
            'headers',
            'secret',
            'credentials',
            'password',
            'qr',
            'qrstring',
            'qrpayload',
            'gatewaypayload',
            'webhookpayload',
            'rawpayload',
            'ciphertext',
            'blindindex',
            'bidx',
        ], true);
    }

    protected function getModuleName(Model $model): string
    {
        $class = get_class($model);

        return match ($class) {
            \App\Models\Employee::class => 'employees',
            \App\Models\EmployeeCertificate::class => 'certificates',
            \App\Models\MedicalCheckup::class => 'medical_checkups',
            \App\Models\Payroll::class => 'payrolls',
            \App\Models\Invoice::class => 'invoices',
            default => strtolower(class_basename($class)),
        };
    }
}
