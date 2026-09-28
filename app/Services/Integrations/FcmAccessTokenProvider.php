<?php

namespace App\Services\Integrations;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FcmAccessTokenProvider
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private ?string $accessToken = null;

    private ?string $credentialFingerprint = null;

    private int $expiresAt = 0;

    public function isConfigured(): bool
    {
        return filled(config('services.fcm.project_id'))
            && filled(config('services.fcm.service_account_path'));
    }

    public function projectId(): string
    {
        $project = config('services.fcm.project_id');

        if (! is_string($project) || preg_match('/\A[a-z][a-z0-9-]{4,28}[a-z0-9]\z/D', $project) !== 1) {
            throw new RuntimeException('FCM project configuration is invalid.');
        }

        return $project;
    }

    /** Validate locally; never mint a token or contact a provider during preflight. */
    public function validateConfiguration(): void
    {
        $this->projectId();
        $this->credentials();
    }

    public function forgetToken(): void
    {
        $this->accessToken = null;
        $this->expiresAt = 0;
    }

    public function accessToken(): string
    {
        try {
            $credentials = $this->credentials();
            $fingerprint = hash('sha256', $this->projectId().$credentials['client_email'].$credentials['private_key']);

            if ($this->credentialFingerprint === $fingerprint && $this->accessToken !== null && $this->expiresAt > now()->timestamp) {
                return $this->accessToken;
            }

            $this->forgetToken();
            $issuedAt = now()->timestamp;
            $assertion = JWT::encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => self::TOKEN_URL,
                'iat' => $issuedAt,
                'exp' => $issuedAt + 3600,
            ], $credentials['private_key'], 'RS256');

            $response = Http::asForm()->acceptJson()->withoutRedirecting()
                ->connectTimeout(5)->timeout(15)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            $token = $response->json('access_token');
            $lifetime = $response->json('expires_in');
            if (! $response->successful() || ! is_string($token) || trim($token) === ''
                || preg_match('/\s/', $token) === 1
                || strcasecmp((string) $response->json('token_type'), 'Bearer') !== 0
                || ! is_int($lifetime) || $lifetime <= 60) {
                throw new RuntimeException('FCM authentication failed.');
            }

            // Process-local only: bearer credentials never enter the shared cache.
            $this->accessToken = $token;
            $this->credentialFingerprint = $fingerprint;
            $this->expiresAt = $issuedAt + min($lifetime, 3600) - 60;

            return $token;
        } catch (Throwable) {
            $this->forgetToken();

            // Provider bodies, assertion, credentials and exception chains are secret.
            throw new RuntimeException('FCM authentication failed.');
        }
    }

    /** @return array{client_email: string, private_key: string} */
    private function credentials(): array
    {
        try {
            $path = config('services.fcm.service_account_path');
            $resolved = is_string($path) && $path !== '' ? realpath($path) : false;
            if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
                throw new RuntimeException;
            }

            // Resolve symlinks too; a secret file must never be served by the web root.
            $normalized = strtolower(str_replace('\\', '/', $resolved));
            foreach ([public_path(), storage_path('app/public')] as $publicRoot) {
                $root = strtolower(str_replace('\\', '/', realpath($publicRoot) ?: $publicRoot));
                if ($normalized === $root || str_starts_with($normalized, rtrim($root, '/').'/')) {
                    throw new RuntimeException;
                }
            }

            $credentials = json_decode((string) @file_get_contents($resolved), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($credentials) || ($credentials['type'] ?? null) !== 'service_account'
                || ! is_string($credentials['client_email'] ?? null)
                || ! filter_var($credentials['client_email'], FILTER_VALIDATE_EMAIL)
                || ! is_string($credentials['private_key'] ?? null)) {
                throw new RuntimeException;
            }

            $key = @openssl_pkey_get_private($credentials['private_key']);
            $details = $key === false ? false : openssl_pkey_get_details($key);
            if ($details === false || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
                throw new RuntimeException;
            }

            return ['client_email' => $credentials['client_email'], 'private_key' => $credentials['private_key']];
        } catch (Throwable) {
            throw new RuntimeException('FCM service account configuration is invalid.');
        }
    }
}
