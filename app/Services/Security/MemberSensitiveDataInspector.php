<?php

namespace App\Services\Security;

use App\Exceptions\PiiDecryptionException;

final class MemberSensitiveDataInspector
{
    public function __construct(
        private readonly PiiCryptoService $crypto,
    ) {}

    /**
     * @param  array<string, mixed>  $record
     * @return array{
     *     fields: array<string, array{
     *         status: string,
     *         legacy: ?string,
     *         decrypted: ?string,
     *         issues: list<string>
     *     }>,
     *     has_legacy_plaintext: bool,
     *     has_issues: bool
     * }
     */
    public function inspect(array $record): array
    {
        $fields = [];

        foreach (PiiCryptoService::FIELDS as $field) {
            $legacy = $record[$field] ?? null;
            $encrypted = $record[$field.'_enc'] ?? null;
            $blindIndex = $record[$field.'_bidx'] ?? null;
            $encryptionVersion = $record[$field.'_key_version'] ?? null;
            $blindIndexVersion = $record[$field.'_bidx_version'] ?? null;
            $migratedAt = $record[$field.'_migrated_at'] ?? null;
            $decrypted = null;
            $issues = [];
            $hasEncrypted = is_string($encrypted) && $encrypted !== '';

            if ($hasEncrypted) {
                try {
                    $decrypted = $this->crypto->decrypt($encrypted, $field, (string) ($record['id'] ?? ''));
                } catch (PiiDecryptionException) {
                    $issues[] = 'decrypt_failure';
                }

                $status = $legacy !== null
                    ? ($decrypted === $legacy ? 'dual_equal' : 'dual_mismatch')
                    : 'encrypted_only';
            } elseif ($legacy !== null) {
                $status = 'legacy_only';
            } else {
                $status = 'empty';
            }

            if ($decrypted !== null || $legacy !== null) {
                $expectedBlindIndex = $this->crypto->blindIndex($field, $decrypted ?? $legacy);
                if ($blindIndex !== $expectedBlindIndex) {
                    $issues[] = $blindIndex === null ? 'missing_bidx' : 'bidx_mismatch';
                }
            } elseif ($blindIndex !== null) {
                $issues[] = 'orphan_bidx';
            }

            if ($hasEncrypted && $encryptionVersion !== $this->crypto->currentEncryptionVersion()) {
                $issues[] = 'key_version_mismatch';
            }

            if ($blindIndex !== null && $blindIndexVersion !== $this->crypto->currentBlindIndexVersion()) {
                $issues[] = 'bidx_version_mismatch';
            }

            if ($hasEncrypted && $migratedAt === null) {
                $issues[] = 'missing_migrated_at';
            }

            if ($status === 'empty' && (
                $encryptionVersion !== null
                || $blindIndexVersion !== null
                || $migratedAt !== null
            )) {
                $issues[] = 'orphan_metadata';
            }

            $fields[$field] = [
                'status' => $status,
                'legacy' => is_string($legacy) ? $legacy : null,
                'decrypted' => $decrypted,
                'issues' => array_values(array_unique($issues)),
            ];
        }

        return [
            'fields' => $fields,
            'has_legacy_plaintext' => collect($fields)->contains(
                fn (array $field): bool => $field['legacy'] !== null,
            ),
            'has_issues' => collect($fields)->contains(
                fn (array $field): bool => $field['issues'] !== [],
            ),
        ];
    }
}
