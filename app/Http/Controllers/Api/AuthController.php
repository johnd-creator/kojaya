<?php

namespace App\Http\Controllers\Api;

use App\Enums\TokenApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileLoginRequest;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Auth\Sso\GoogleSsoService;
use App\Services\Auth\TokenIssuanceService;
use App\Services\Cooperative\MemberAccessRevocationService;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly TokenIssuanceService $tokenIssuer,
        private readonly MemberAccessRevocationService $tokenRevocation,
    ) {}

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

        $app = isset($validated['app'])
            ? TokenApp::from($validated['app'])
            : $this->defaultTokenAppFor($user);
        $deviceName = $validated['device_name'] ?? $this->defaultDeviceName($app->value);
        $token = $this->tokenIssuer->issue($user, $app, $deviceName, $validated['device_id'] ?? null);
        $abilities = $token->accessToken->abilities;

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
                'token_app' => $request->user()->currentAccessToken() instanceof PersonalAccessToken
                    ? $request->user()->currentAccessToken()->token_app
                    : null,
                'token_version' => $request->user()->currentAccessToken() instanceof PersonalAccessToken
                    ? $request->user()->currentAccessToken()->token_version
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
        $this->tokenRevocation->revokeAccountWide(
            $request->user(),
            'User initiated account-wide token revocation.',
            $request->user(),
        );

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

    private function defaultTokenAppFor(User $user): TokenApp
    {
        if ($user->cooperativeMember) {
            return TokenApp::MEMBER;
        }

        if ($user->employee) {
            return TokenApp::ESS;
        }

        return TokenApp::ADMIN;
    }

    public function loginWithGoogle(
        Request $request,
        GoogleSsoService $googleSso
    ): JsonResponse {
        if (! $googleSso->isEnabled()) {
            return response()->json([
                'message' => 'Login dengan Google belum diaktifkan.',
            ], 422);
        }

        $request->validate([
            'id_token' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:100',
            'platform' => 'nullable|string',
            'app' => 'nullable|string|in:member,ess,technician,admin',
        ]);

        $idToken = $request->input('id_token');

        $payload = $this->verifyGoogleIdToken($idToken);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

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
        $socialiteUser = new class($sub, $name, $email, $picture, $hd) implements \Laravel\Socialite\Contracts\User
        {
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

            public function getId()
            {
                return $this->id;
            }

            public function getNickname()
            {
                return null;
            }

            public function getName()
            {
                return $this->name;
            }

            public function getEmail()
            {
                return $this->email;
            }

            public function getAvatar()
            {
                return $this->avatar;
            }

            public function getRaw()
            {
                return $this->user;
            }
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

        $app = $request->filled('app')
            ? TokenApp::from((string) $request->input('app'))
            : $this->defaultTokenAppFor($user);
        $deviceName = $request->input('device_name') ?? $this->defaultDeviceName($app->value);
        $token = $this->tokenIssuer->issue($user, $app, $deviceName, $request->input('device_id'));
        $abilities = $token->accessToken->abilities;
        $user = $user->refresh()->load(['roles', 'employee', 'cooperativeMember']);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'auth_result' => $resolution['result'] ?? GoogleSsoService::RESULT_LOGIN_EXISTING,
            'user' => $this->sessionPayload($user),
            'member_status' => $user->cooperativeMember?->status,
            'validation_status' => $user->cooperativeMember?->validation_status,
            'onboarding_next_step' => $this->onboardingNextStep($user),
        ]);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function verifyGoogleIdToken(string $idToken): array|JsonResponse
    {
        if (substr_count($idToken, '.') !== 2) {
            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa.',
            ], 422);
        }

        $jwksResult = $this->verifyGoogleIdTokenWithJwks($idToken);
        if (! ($jwksResult instanceof JsonResponse && $jwksResult->getStatusCode() === 503)) {
            return $jwksResult;
        }

        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('sso.google.mobile_tokeninfo_unreachable', [
                'message' => $exception->getMessage(),
            ]);

            return $jwksResult;
        }

        if ($response->serverError()) {
            Log::warning('sso.google.mobile_tokeninfo_server_error', [
                'status' => $response->status(),
            ]);

            return $jwksResult;
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa.',
            ], 422);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return response()->json([
                'message' => 'Respons verifikasi Google tidak dikenali.',
            ], 422);
        }

        return $this->validateGoogleIdTokenPayload($payload);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function verifyGoogleIdTokenWithJwks(string $idToken): array|JsonResponse
    {
        $jwks = $this->googleJwks();
        if (! $jwks) {
            return response()->json([
                'message' => 'Layanan verifikasi Google sedang tidak dapat dihubungi. Coba lagi beberapa saat.',
            ], 503);
        }

        try {
            $payload = (array) JWT::decode($idToken, JWK::parseKeySet($jwks));
        } catch (Throwable $exception) {
            Log::info('sso.google.mobile_jwt_invalid', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa.',
            ], 422);
        }

        return $this->validateGoogleIdTokenPayload($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function googleJwks(): ?array
    {
        try {
            $jwks = Cache::remember('sso.google.jwks', now()->addHours(6), function (): ?array {
                $response = Http::timeout(5)
                    ->acceptJson()
                    ->get('https://www.googleapis.com/oauth2/v3/certs');

                if ($response->failed()) {
                    return null;
                }

                $payload = $response->json();

                return is_array($payload) && isset($payload['keys']) ? $payload : null;
            });

            return is_array($jwks) ? $jwks : null;
        } catch (Throwable $exception) {
            Log::warning('sso.google.jwks_unreachable', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|JsonResponse
     */
    private function validateGoogleIdTokenPayload(array $payload): array|JsonResponse
    {
        $expectedAudience = config('services.google.client_id');
        $audience = data_get($payload, 'aud');
        if ($expectedAudience && $audience !== $expectedAudience) {
            Log::warning('sso.google.mobile_audience_mismatch', [
                'expected_audience' => $expectedAudience,
                'actual_audience' => $audience,
            ]);

            return response()->json([
                'message' => 'Token Google tidak ditujukan untuk aplikasi Kojaya.',
            ], 422);
        }

        $issuer = data_get($payload, 'iss');
        if ($issuer && ! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            Log::warning('sso.google.mobile_issuer_mismatch', [
                'issuer' => $issuer,
            ]);

            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa.',
            ], 422);
        }

        return $payload;
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

    private function onboardingNextStep(User $user): ?string
    {
        $member = $user->cooperativeMember;
        if (! $member) {
            return null;
        }

        return match ($member->validation_status) {
            CooperativeMember::VALIDATION_ACTIVE => 'dashboard',
            CooperativeMember::VALIDATION_PENDING_REVIEW => 'waiting_final_approval',
            CooperativeMember::VALIDATION_REJECTED => 'rejected',
            default => 'waiting_admin_acceptance',
        };
    }
}
