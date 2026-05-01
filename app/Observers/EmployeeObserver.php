<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\AuditLogService;

class EmployeeObserver
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function created(Employee $employee): void
    {
        $this->auditLogService->logModelEvent('CREATE', $employee);
    }

    public function updated(Employee $employee): void
    {
        $this->auditLogService->logModelEvent('UPDATE', $employee);
    }

    public function deleted(Employee $employee): void
    {
        $this->auditLogService->logModelEvent('DELETE', $employee);
    }
}
