<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(string $action, string $module, ?Model $subject = null, ?array $changes = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logAuth(string $action, $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'module' => 'auth',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logModelEvent(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        $module = $this->getModuleName($model);

        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
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
