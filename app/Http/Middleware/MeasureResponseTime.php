<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MeasureResponseTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

        $response->headers->set('X-Response-Time-Ms', (string) $durationMs);

        if ($request->is('api/*')) {
            Log::info('api_request_timing', [
                'request_id' => $request->attributes->get('correlation_id'),
                'method' => $request->method(),
                'route' => $request->route()?->getName() ?: $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
            ]);
        }

        return $response;
    }
}
