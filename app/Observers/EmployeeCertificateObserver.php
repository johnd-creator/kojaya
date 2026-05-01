<?php

namespace App\Observers;

use App\Models\EmployeeCertificate;
use App\Services\AuditLogService;

class EmployeeCertificateObserver
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function created(EmployeeCertificate $employeeCertificate): void
    {
        $this->auditLogService->logModelEvent('CREATE', $employeeCertificate);
    }

    public function updated(EmployeeCertificate $employeeCertificate): void
    {
        $this->auditLogService->logModelEvent('UPDATE', $employeeCertificate);
    }

    public function deleted(EmployeeCertificate $employeeCertificate): void
    {
        $this->auditLogService->logModelEvent('DELETE', $employeeCertificate);
    }
}
