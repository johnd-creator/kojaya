<?php

declare(strict_types=1);

namespace App\Services\Cooperative;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use JsonException;

class PreviewProofService
{
    public const DEFAULT_TTL_SECONDS = 1800; // 30 minutes

    public const CODE_INVALID = 'PREVIEW_PROOF_INVALID';

    public const CODE_EXPIRED = 'PREVIEW_PROOF_EXPIRED';

    public const CODE_FILE_CHANGED = 'FILE_CHANGED_SINCE_PREVIEW';

    /**
     * Issue a tamper-resistant, short-lived preview proof for a validated CSV.
     * Proof contains NON-PII metadata only.
     */
    public function generate(
        string $fileSha256,
        string $organizationId,
        string $importDate,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): string {
        if ($fileSha256 === '' || $organizationId === '' || $importDate === '') {
            throw new InvalidArgumentException('Semua parameter metadata preview proof wajib diisi.');
        }

        $now = now()->timestamp;
        $payload = [
            'file_sha256' => $fileSha256,
            'organization_id' => $organizationId,
            'import_date' => $importDate,
            'issued_at' => $now,
            'expires_at' => $now + $ttlSeconds,
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Verify preview proof against the execution request parameters.
     *
     * @return array{valid: bool, code: ?string, message: ?string, payload: ?array<string, mixed>}
     */
    public function verify(
        string $proof,
        string $fileSha256,
        string $organizationId,
        string $importDate,
    ): array {
        try {
            $decrypted = Crypt::decryptString($proof);
            /** @var array{file_sha256?: string, organization_id?: string, import_date?: string, issued_at?: int, expires_at?: int} $payload */
            $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return [
                'valid' => false,
                'code' => self::CODE_INVALID,
                'message' => 'Token bukti pratinjau tidak valid atau telah dimanipulasi.',
                'payload' => null,
            ];
        }

        if (
            ! isset($payload['file_sha256'], $payload['organization_id'], $payload['import_date'], $payload['expires_at'])
            || ! is_string($payload['file_sha256'])
            || ! is_string($payload['organization_id'])
            || ! is_string($payload['import_date'])
            || ! is_int($payload['expires_at'])
        ) {
            return [
                'valid' => false,
                'code' => self::CODE_INVALID,
                'message' => 'Format struktur bukti pratinjau tidak sesuai spesifikasi.',
                'payload' => null,
            ];
        }

        // Check expiration
        if (now()->timestamp > $payload['expires_at']) {
            return [
                'valid' => false,
                'code' => self::CODE_EXPIRED,
                'message' => 'Bukti pratinjau telah kedaluwarsa. Silakan lakukan pratinjau ulang.',
                'payload' => $payload,
            ];
        }

        // Check organization binding
        if (! hash_equals($payload['organization_id'], $organizationId)) {
            return [
                'valid' => false,
                'code' => self::CODE_INVALID,
                'message' => 'Organisasi target pada eksekusi tidak cocok dengan bukti pratinjau.',
                'payload' => $payload,
            ];
        }

        // Check import date binding
        if (! hash_equals($payload['import_date'], $importDate)) {
            return [
                'valid' => false,
                'code' => self::CODE_INVALID,
                'message' => 'Tanggal impor pada eksekusi tidak cocok dengan bukti pratinjau.',
                'payload' => $payload,
            ];
        }

        // Check file hash
        if (! hash_equals($payload['file_sha256'], $fileSha256)) {
            return [
                'valid' => false,
                'code' => self::CODE_FILE_CHANGED,
                'message' => 'Berkas CSV yang diunggah berbeda dari berkas pada saat pratinjau.',
                'payload' => $payload,
            ];
        }

        return [
            'valid' => true,
            'code' => null,
            'message' => null,
            'payload' => $payload,
        ];
    }
}
