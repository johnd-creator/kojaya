<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileLoginRequest;
use App\Models\User;
use App\Services\Auth\TokenAbilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private readonly TokenAbilityResolver $abilityResolver) {}

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

        $abilities = $this->abilityResolver->for($user, $validated['app'] ?? null);
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

    private function defaultDeviceName(?string $app): string
    {
        return match ($app) {
            'member' => 'Kojayaku Mobile',
            'ess' => 'ESS Mobile',
            'technician' => 'Technician Mobile',
            default => 'Mobile App',
        };
    }

    public function loginWithGoogle(
        Request $request,
        \App\Services\Auth\Sso\GoogleSsoService $googleSso
    ): JsonResponse {
        if (! $googleSso->isEnabled()) {
            return response()->json([
                'message' => 'Login dengan Google belum diaktifkan.',
            ], 422);
        }

        $request->validate([
            'id_token' => 'required|string',
            'device_name' => 'nullable|string',
            'device_id' => 'nullable|string',
            'platform' => 'nullable|string',
            'app' => 'nullable|string',
        ]);

        $idToken = $request->input('id_token');

        // Verify ID Token with Google API
        $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa.',
            ], 422);
        }

        $payload = $response->json();

        // email_verified can be boolean or string 'true' in response
        $emailVerified = data_get($payload, 'email_verified');
        if (empty($payload['email']) || ($emailVerified !== true && $emailVerified !== 'true')) {
            return response()->json([
                'message' => 'Email Google tidak valid atau belum terverifikasi.',
            ], 422);
        }

        $sub = $payload['sub'] ?? '';
        $email = $payload['email'];
        $name = $payload['name'] ?? 'Anggota Baru';
        $picture = $payload['picture'] ?? null;
        $hd = $payload['hd'] ?? null;

        // Wrap the payload into a Laravel Socialite User object structure for compatibility
        $socialiteUser = new class($sub, $name, $email, $picture, $hd) implements \Laravel\Socialite\Contracts\User {
            public $user;
            public function __construct(
                private $id,
                private $name,
                private $email,
                private $avatar,
                $hd
            ) {
                $this->user = ['hd' => $hd];
            }

            public function getId() { return $this->id; }
            public function getNickname() { return null; }
            public function getName() { return $this->name; }
            public function getEmail() { return $this->email; }
            public function getAvatar() { return $this->avatar; }
            public function getRaw() { return $this->user; }
        };

        if (! $googleSso->isHostedDomainAllowed($socialiteUser)) {
            $googleSso->logFailure('hosted_domain_denied', [
                'hosted_domain' => $hd,
                'email' => $email,
            ]);
            return response()->json([
                'message' => 'Domain email Google ini tidak diizinkan untuk login.',
            ], 422);
        }

        $resolution = $googleSso->resolveUserFromGoogle($socialiteUser);

        if (! $resolution['user']) {
            $googleSso->logFailure('resolution_failed', [
                'provider_id' => $sub,
                'email' => $email,
                'reason' => $resolution['reason'] ?? null,
            ]);

            return response()->json([
                'message' => 'Akun Google ini tidak dapat digunakan untuk login.',
            ], 422);
        }

        $user = $resolution['user'];
        $social = $resolution['social_account'] ?? null;
        if ($social) {
            $googleSso->recordLogin($social);
        }

        $app = $request->input('app');
        $abilities = $this->abilityResolver->for($user, $app);

        $deviceName = $request->input('device_name') ?? $this->defaultDeviceName($app);
        $token = $user->createToken($deviceName, $abilities);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'user' => $this->sessionPayload($user->refresh()->load(['roles', 'employee', 'cooperativeMember'])),
        ]);
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
