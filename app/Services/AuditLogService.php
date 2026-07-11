<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditLogService
{
    public function log(string $action, string $module, ?Model $subject = null, ?array $changes = []): AuditLog
    {
        return AuditLog::create($this->payload($action, $module, $subject, $changes));
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function payload(string $action, string $module, ?Model $subject, array $changes): array
    {
        $actor = Auth::user();
        $organizationId = $subject?->getAttribute('organization_id') ?? $actor?->organization_id;

        return [
            'correlation_id' => $this->correlationId(),
            'user_id' => $actor?->id,
            'organization_id' => $organizationId,
            'actor_roles' => $actor?->relationLoaded('roles')
                ? $actor->roles->pluck('name')->values()->all()
                : $actor?->getRoleNames()->values()->all(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'old_values' => $this->redact($changes['old'] ?? null),
            'new_values' => $this->redact($changes['new'] ?? null),
            'reason' => isset($changes['reason']) ? (string) $changes['reason'] : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'occurred_at' => now(),
        ];
    }

    public function logAuth(string $action, $userId = null): AuditLog
    {
        $payload = $this->payload($action, 'auth', null, []);
        $payload['user_id'] = $userId ?? Auth::id();

        return AuditLog::create($payload);
    }

    public function logModelEvent(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return AuditLog::create($this->payload($action, $this->getModuleName($model), $model, [
            'old' => $oldValues,
            'new' => $newValues,
        ]));
    }

    private function correlationId(): string
    {
        $correlationId = Request::getFacadeRoot()?->attributes->get('correlation_id') ?? Request::header('X-Correlation-ID');

        return is_string($correlationId) && Str::isUuid($correlationId) ? $correlationId : (string) Str::uuid();
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            $keyName = strtolower((string) $key);
            $redacted[$key] = preg_match('/password|secret|token|authorization|qr(_string|_payload)?|identity(_number)?|nik|npwp|bank(_account|_account_number|_account_holder)?|account(_number|_holder)?|card(_number)?/', $keyName) === 1
                ? '[REDACTED]'
                : $this->redact($item);
        }

        return $redacted;
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
