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
        $app = $currentToken->token_app ?: $classifier->classify($currentToken->abilities);
        if (! TokenApp::tryFrom((string) $app)) {
            throw ValidationException::withMessages([
                'token' => 'Legacy token requires explicit application rotation.',
            ]);
        }

        $newAccessToken = DB::transaction(function () use ($user, $currentToken, $deviceName, $app, $tokenIssuer) {
            $newAccessToken = $tokenIssuer->issue($user, TokenApp::from((string) $app), $deviceName, $currentToken->device_id);
            $currentToken->delete();

            return $newAccessToken;
        });

        $expirationMinutes = config('sanctum.expiration');

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $newAccessToken->plainTextToken,
            'abilities' => $newAccessToken->accessToken->abilities,
            'expires_at' => is_numeric($expirationMinutes)
                ? now()->addMinutes((int) $expirationMinutes)->toISOString()
                : null,
        ]);
    }
}
