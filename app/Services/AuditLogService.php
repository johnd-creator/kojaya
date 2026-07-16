<?php

namespace App\Services;

use App\Models\AuditLog;
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
        if ($userId !== null && $userId !== $context->actorId) {
            $context = new AuditContext(
                actorId: $userId,
                actorRoles: $context->actorRoles,
                organizationId: $context->organizationId,
                correlationId: $context->correlationId,
                ip: $context->ip,
                userAgent: $context->userAgent,
                source: $context->source,
            );
        }

        $payload = $this->payload($action, 'auth', null, [], $context);

        return AuditLog::create($payload);
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
