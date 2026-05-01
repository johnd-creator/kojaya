<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSuccessfulLogout implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function handle(object $event): void
    {
        $this->auditLogService->logAuth('LOGOUT', $event->user->id ?? null);
    }
}
