<?php

namespace App\Support;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $extra = []): JsonResponse
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve(request());
        } elseif ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json([...$payload, ...$extra], $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $message,
        int $status = 400,
        array $errors = [],
        ApiErrorCode|string|null $code = null,
    ): JsonResponse {
        $errorCode = $code instanceof ApiErrorCode ? $code->value : $code;

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error' => $message,
            'error_code' => $errorCode ?? self::codeForStatus($status)->value,
            'error_details' => Arr::wrap($errors),
            'request_id' => request()?->attributes->get('correlation_id'),
        ], $status);
    }

    public static function codeForStatus(int $status): ApiErrorCode
    {
        return match ($status) {
            401 => ApiErrorCode::Unauthorized,
            403 => ApiErrorCode::Forbidden,
            404 => ApiErrorCode::NotFound,
            409 => ApiErrorCode::Conflict,
            422 => ApiErrorCode::ValidationError,
            429 => ApiErrorCode::TooManyRequests,
            default => $status >= 500 ? ApiErrorCode::ServerError : ApiErrorCode::BusinessRuleViolation,
        };
    }
}
