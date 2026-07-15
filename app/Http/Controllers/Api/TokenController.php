<?php

namespace App\Http\Controllers\Api;

use App\Enums\TokenApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RotateTokenRequest;
use App\Services\Auth\LegacyTokenClassifier;
use App\Services\Auth\TokenIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function rotate(RotateTokenRequest $request, TokenIssuanceService $tokenIssuer, LegacyTokenClassifier $classifier): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        abort_unless($user && $currentToken instanceof PersonalAccessToken, 400, 'Only bearer tokens can be rotated.');

        $validated = $request->validated();
        $deviceName = $validated['device_name'] ?? $currentToken->name;
        $requestedApp = $validated['app'] ?? null;
        $classifiedApp = $currentToken->token_app ?: $classifier->classify($currentToken->abilities);
        $resolvedApp = TokenApp::tryFrom((string) $classifiedApp);

        if ($resolvedApp !== null) {
            if ($requestedApp !== null && $requestedApp !== $resolvedApp->value) {
                throw ValidationException::withMessages([
                    'app' => 'Application rotation must preserve the current token profile.',
                ]);
            }
        } elseif ($requestedApp !== null) {
            $resolvedApp = TokenApp::tryFrom($requestedApp);
        }

        if ($resolvedApp === null) {
            throw ValidationException::withMessages([
                'app' => 'Unsafe legacy token requires an explicit member, ess, technician, or admin application.',
            ]);
        }

        $newAccessToken = DB::transaction(function () use ($user, $currentToken, $deviceName, $resolvedApp, $tokenIssuer) {
            $newAccessToken = $tokenIssuer->issue($user, $resolvedApp, $deviceName, $currentToken->device_id);
            $currentToken->delete();

            return $newAccessToken;
        });

        $expirationMinutes = config('sanctum.expiration');

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $newAccessToken->plainTextToken,
            'abilities' => $newAccessToken->accessToken->abilities,
            'token_app' => $newAccessToken->accessToken->token_app,
            'token_version' => $newAccessToken->accessToken->token_version,
            'expires_at' => is_numeric($expirationMinutes)
                ? now()->addMinutes((int) $expirationMinutes)->toISOString()
                : null,
        ]);
    }
}
