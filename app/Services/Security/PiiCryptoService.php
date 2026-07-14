<?php

namespace App\Services\Security;

use App\Exceptions\PiiDecryptionException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class PiiCryptoService
{
    /**
     * @var list<string>
     */
    public const FIELDS = [
        'identity_number',
        'npwp',
        'no_rekening',
    ];

    /**
     * @param  array<string, string>  $encryptionKeys
     * @param  array<string, string>  $blindIndexKeys
     */
    public function __construct(
        private readonly array $encryptionKeys,
        private readonly string $currentEncryptionVersion,
        private readonly array $blindIndexKeys,
        private readonly string $currentBlindIndexVersion,
        private readonly ?string $legacyEncryptionKey = null,
    ) {
        $this->validateKeyMap($this->encryptionKeys, 'encryption');
        $this->validateKeyMap($this->blindIndexKeys, 'blind index');

        if ($this->legacyEncryptionKey !== null) {
            $this->decodedKey($this->legacyEncryptionKey, 'legacy encryption', 'legacy');
        }

        $this->decodedKey(
            $this->encryptionKeys[$this->currentEncryptionVersion] ?? '',
            'encryption',
            $this->currentEncryptionVersion,
        );
        $this->decodedKey(
            $this->blindIndexKeys[$this->currentBlindIndexVersion] ?? '',
            'blind index',
            $this->currentBlindIndexVersion,
        );

        foreach ($this->encryptionKeys as $encryptionVersion => $configuredEncryptionKey) {
            $decodedEncryptionKey = $this->decodedKey($configuredEncryptionKey, 'encryption', (string) $encryptionVersion);

            foreach ($this->blindIndexKeys as $blindIndexVersion => $configuredBlindIndexKey) {
                if (hash_equals(
                    $decodedEncryptionKey,
                    $this->decodedKey($configuredBlindIndexKey, 'blind index', (string) $blindIndexVersion),
                )) {
                    throw new RuntimeException('PII encryption and blind-index keys must be different.');
                }
            }
        }
    }

    public function currentEncryptionVersion(): string
    {
        return $this->currentEncryptionVersion;
    }

    public function currentBlindIndexVersion(): string
    {
        return $this->currentBlindIndexVersion;
    }

    public function normalizeForIndex(string $field, mixed $value): ?string
    {
        $this->assertField($field);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return match ($field) {
            'identity_number', 'npwp', 'no_rekening' => preg_replace('/\D+/', '', $value) ?? '',
        } ?: null;
    }

    public function blindIndex(string $field, mixed $value, ?string $version = null): ?string
    {
        $normalized = $this->normalizeForIndex($field, $value);
        if ($normalized === null) {
            return null;
        }

        $version ??= $this->currentBlindIndexVersion;

        return hash_hmac(
            'sha256',
            $field.'|'.$normalized,
            $this->decodedKey($this->blindIndexKeys[$version] ?? '', 'blind index', $version),
        );
    }

    public function encrypt(?string $value, ?string $version = null): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $version ??= $this->currentEncryptionVersion;
        $payload = [
            'version' => $version,
            'ciphertext' => $this->encrypter($version)->encryptString($value),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    public function decrypt(?string $payload, string $field, ?string $subjectId = null): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $version = null;

        try {
            $decoded = json_decode(base64_decode($payload, true) ?: '', true, 512, JSON_THROW_ON_ERROR);
            $version = is_string($decoded['version'] ?? null) ? $decoded['version'] : null;
            $ciphertext = $decoded['ciphertext'] ?? null;

            if (! is_string($version) || ! is_string($ciphertext)) {
                throw new RuntimeException('The PII envelope is invalid.');
            }

            return $this->encrypter($version)->decryptString($ciphertext);
        } catch (Throwable $exception) {
            if ($version === null && $this->legacyEncryptionKey !== null) {
                try {
                    return $this->legacyEncrypter()->decryptString($payload);
                } catch (Throwable $legacyException) {
                    $exception = $legacyException;
                    $version = 'legacy';
                }
            }

            Log::error('PII decryption failed.', [
                'field' => $field,
                'metric' => 'pii.decrypt.failure',
                'key_version' => $version,
                'subject_id' => $subjectId,
                'exception' => $exception::class,
            ]);

            throw PiiDecryptionException::forField($field, $version);
        }
    }

    private function encrypter(string $version): Encrypter
    {
        return new Encrypter(
            $this->decodedKey($this->encryptionKeys[$version] ?? '', 'encryption', $version),
            'aes-256-cbc',
        );
    }

    private function legacyEncrypter(): Encrypter
    {
        return new Encrypter(
            $this->decodedKey($this->legacyEncryptionKey ?? '', 'legacy encryption', 'legacy'),
            'aes-256-cbc',
        );
    }

    /**
     * @param  array<string, string>  $keys
     */
    private function validateKeyMap(array $keys, string $purpose): void
    {
        if ($keys === []) {
            throw new RuntimeException("PII {$purpose} keys are not configured.");
        }

        foreach ($keys as $version => $key) {
            $this->decodedKey($key, $purpose, (string) $version);
        }
    }

    private function decodedKey(string $key, string $purpose, string $version): string
    {
        if ($key === '') {
            throw new RuntimeException("PII {$purpose} key [{$version}] is missing.");
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        if (strlen($key) !== 32) {
            throw new RuntimeException("PII {$purpose} key [{$version}] must decode to 32 bytes.");
        }

        return $key;
    }

    private function assertField(string $field): void
    {
        if (! in_array($field, self::FIELDS, true)) {
            throw new RuntimeException("Unsupported PII field [{$field}].");
        }
    }
}
