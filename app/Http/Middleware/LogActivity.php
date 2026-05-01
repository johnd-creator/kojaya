<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check() && $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $this->auditLogService->log(
                action: $this->getActionFromMethod($request->method()),
                module: $this->getModuleFromPath($request->path()),
            );
        }

        return $response;
    }

    protected function getActionFromMethod(string $method): string
    {
        return match ($method) {
            'POST' => 'CREATE',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'ACCESS',
        };
    }

    protected function getModuleFromPath(string $path): string
    {
        $segments = explode('/', $path);

        return $segments[0] ?? 'unknown';
    }
}
