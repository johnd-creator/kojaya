<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->with(['roles', 'employee', 'cooperativeMember'])
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $abilities = $this->abilitiesFor($user, $validated['app'] ?? null);
        $deviceName = $validated['device_name'] ?? $this->defaultDeviceName($validated['app'] ?? null);
        $token = $user->createToken($deviceName, $abilities);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'user' => $this->sessionPayload($user->refresh()->load(['roles', 'employee', 'cooperativeMember'])),
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->sessionPayload($request->user()->load(['roles', 'employee', 'cooperativeMember'])),
            'token' => [
                'name' => $request->user()->currentAccessToken() instanceof PersonalAccessToken
                    ? $request->user()->currentAccessToken()->name
                    : null,
                'abilities' => $request->user()->currentAccessToken() instanceof PersonalAccessToken
                    ? $request->user()->currentAccessToken()->abilities
                    : null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken instanceof PersonalAccessToken) {
            $currentToken->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked.']);
    }

    /**
     * @return array<int, string>
     */
    private function abilitiesFor(User $user, ?string $app): array
    {
        if ($user->hasAnyRole(['System Admin', 'Admin Pusat'])) {
            return ['*'];
        }

        $abilities = ['profile:read'];

        if ($user->hasRole('Anggota') || $user->cooperativeMember) {
            $abilities = [
                ...$abilities,
                'member:read',
                'member:write',
                'cooperative:read',
                'cooperative:write',
            ];
        }

        if ($user->hasRole('Employee') || $user->employee) {
            $abilities = [
                ...$abilities,
                'ess:read',
                'ess:write',
                'attendance:read',
                'attendance:write',
                'payroll:read',
            ];
        }

        if ($user->hasRole('Technician') || $user->can('view_work_order_unit') || $user->can('view_work_order_all')) {
            $abilities = [
                ...$abilities,
                'work-orders:read',
            ];
        }

        if ($user->hasRole('Technician') || $user->can('manage_work_order')) {
            $abilities = [
                ...$abilities,
                'work-orders:write',
            ];
        }

        if ($user->can('manage_work_order')) {
            $abilities = [
                ...$abilities,
                'work-orders:review',
            ];
        }

        if ($user->hasAnyRole(['Pengurus Koperasi', 'Kasir Koperasi'])) {
            $abilities = [
                ...$abilities,
                'cooperative:read',
                'cooperative:write',
                'pos:read',
                'pos:write',
                'reports:read',
            ];
        }

        $abilities = array_values(array_unique($abilities));

        return match ($app) {
            'member' => array_values(array_intersect($abilities, ['profile:read', 'member:read', 'member:write', 'cooperative:read', 'cooperative:write'])),
            'ess' => array_values(array_intersect($abilities, ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read'])),
            'technician' => array_values(array_intersect($abilities, ['profile:read', 'work-orders:read', 'work-orders:write', 'work-orders:review'])),
            default => $abilities,
        };
    }

    private function defaultDeviceName(?string $app): string
    {
        return match ($app) {
            'member' => 'Kojayaku Mobile',
            'ess' => 'ESS Mobile',
            'technician' => 'Technician Mobile',
            default => 'Mobile App',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values(),
            'employee_id' => $user->employee?->id,
            'cooperative_member_id' => $user->cooperativeMember?->id,
        ];
    }
}
