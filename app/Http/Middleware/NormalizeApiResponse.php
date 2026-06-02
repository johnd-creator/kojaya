<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload) || array_key_exists('success', $payload)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $payload['success'] = $status < 400;

        if ($status >= 400) {
            $message = $payload['message'] ?? $payload['error'] ?? Response::$statusTexts[$status] ?? 'Request failed.';
            $errors = $payload['errors'] ?? [];
            $payload['message'] = is_string($message) ? $message : 'Request failed.';
            $payload['errors'] = is_array($errors) ? $errors : [];
            $payload['error'] = $payload['message'];
            $payload['error_code'] = ApiResponse::codeForStatus($status)->value;
            $payload['error_details'] = $payload['errors'];
            $payload['request_id'] = $request->attributes->get('correlation_id');
        }

        $response->setData($payload);

        return $response;
    }
}
