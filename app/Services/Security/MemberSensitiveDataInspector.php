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
     *         envelope_version: ?string,
     *         encryption_version: ?string,
     *         bidx_version: ?string,
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
            $envelopeVersion = $hasEncrypted ? $this->crypto->envelopeVersion($encrypted) : null;
            $recordedEncryptionVersion = is_string($encryptionVersion) ? $encryptionVersion : null;
            $recordedBlindIndexVersion = is_string($blindIndexVersion) ? $blindIndexVersion : null;

            if ($hasEncrypted) {
                if ($envelopeVersion === null) {
                    $issues[] = 'legacy_ciphertext';
                } elseif (! $this->crypto->hasEncryptionVersion($envelopeVersion)) {
                    $issues[] = 'unknown_key_version';
                }

                if ($recordedEncryptionVersion === null) {
                    $issues[] = 'missing_key_version';
                } elseif ($envelopeVersion !== null && $recordedEncryptionVersion !== $envelopeVersion) {
                    $issues[] = 'envelope_version_mismatch';
                } elseif (! $this->crypto->hasEncryptionVersion($recordedEncryptionVersion)) {
                    $issues[] = 'unknown_key_version';
                }

                try {
                    $decrypted = $this->crypto->decrypt($encrypted, $field, (string) ($record['id'] ?? ''));
                } catch (PiiDecryptionException) {
                    $issues[] = 'decrypt_failure';
                }

                $status = $legacy !== null
                    ? ($decrypted !== null && $decrypted === $legacy ? 'dual_equal' : 'dual_mismatch')
                    : 'encrypted_only';

                if ($decrypted !== null && $legacy !== null && $decrypted !== $legacy) {
                    $issues[] = 'plaintext_encrypted_mismatch';
                }
            } elseif ($legacy !== null) {
                $status = 'legacy_only';
            } else {
                $status = 'empty';
            }

            if ($decrypted !== null || $legacy !== null) {
                if ($blindIndex === null) {
                    $issues[] = 'missing_bidx';
                } elseif ($recordedBlindIndexVersion === null || ! $this->crypto->hasBlindIndexVersion($recordedBlindIndexVersion)) {
                    $issues[] = 'unknown_bidx_version';
                } else {
                    $expectedBlindIndex = $this->crypto->blindIndexForVersion(
                        $field,
                        $decrypted ?? $legacy,
                        $recordedBlindIndexVersion,
                    );

                    if ($blindIndex !== $expectedBlindIndex) {
                        $issues[] = 'bidx_mismatch';
                    }
                }
            } elseif ($blindIndex !== null) {
                $issues[] = 'orphan_bidx';
            }

            if (($hasEncrypted || $blindIndex !== null) && $migratedAt === null) {
                $issues[] = 'missing_migrated_at';
            }

            if ($this->crypto->allowsPlaintextRetirement() && $legacy !== null) {
                $issues[] = 'plaintext_remaining_after_retirement';
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
                'envelope_version' => $envelopeVersion,
                'encryption_version' => $recordedEncryptionVersion,
                'bidx_version' => $recordedBlindIndexVersion,
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
