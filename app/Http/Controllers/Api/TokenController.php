<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RotateTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function rotate(RotateTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        abort_unless($user && $currentToken instanceof PersonalAccessToken, 400, 'Only bearer tokens can be rotated.');

        $validated = $request->validated();
        $deviceName = $validated['device_name'] ?? $currentToken->name;
        $abilities = $currentToken->abilities ?: ['*'];

        $newAccessToken = DB::transaction(function () use ($user, $currentToken, $deviceName, $abilities) {
            $newAccessToken = $user->createToken($deviceName, $abilities);
            $currentToken->delete();

            return $newAccessToken;
        });

        $expirationMinutes = config('sanctum.expiration');

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $newAccessToken->plainTextToken,
            'abilities' => $abilities,
            'expires_at' => is_numeric($expirationMinutes)
                ? now()->addMinutes((int) $expirationMinutes)->toISOString()
                : null,
        ]);
    }
}
