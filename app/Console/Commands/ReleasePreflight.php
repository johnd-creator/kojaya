<?php

namespace App\Console\Commands;

use App\Services\Security\PiiCryptoService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class ReleasePreflight extends Command
{
    protected $signature = 'app:release-preflight
        {--strict-production : Enforce production-only configuration checks}';

    protected $description = 'Check release configuration without changing application or database state';

    /**
     * @var list<string>
     */
    private array $failures = [];

    public function handle(): int
    {
        $strictProduction = (bool) $this->option('strict-production');

        $this->check('application.release_version', function (): bool {
            return filled(config('app.version'));
        });
        $this->check('api.contract_version', function (): bool {
            return filled(config('app.api_contract_version'));
        });

        if ($strictProduction) {
            $this->check('application.environment', function (): bool {
                return config('app.env') === 'production';
            });
            $this->check('application.debug', function (): bool {
                return config('app.debug') === false;
            });
            $this->check('application.key', function (): bool {
                return $this->decodeKey((string) config('app.key')) !== null;
            });
        } else {
            $this->pass('application.environment', 'non-strict');
            $this->pass('application.debug', 'non-strict');
            $this->pass('application.key', 'non-strict');
        }

        $this->check('pii.key_maps', fn (): bool => $this->hasValidPiiKeyMaps());
        $this->check('pii.current_versions', fn (): bool => $this->hasValidPiiVersions());
        $this->check('pii.keys_distinct', fn (): bool => $this->hasDistinctPiiKeys());
        $this->check('pii.service_resolution', function (): bool {
            resolve(PiiCryptoService::class);

            return true;
        });
        $this->check('pii.schema_rollback', function (): bool {
            return config('security.pii_allow_schema_rollback') === false;
        });
        $this->check('pii.rollout_phase', function (): bool {
            return in_array(config('security.rollout_phase'), [
                PiiCryptoService::ROLLOUT_DUAL_WRITE,
                PiiCryptoService::ROLLOUT_ENCRYPTED_PREFERRED,
                PiiCryptoService::ROLLOUT_PLAINTEXT_RETIRED,
            ], true);
        });
        $this->check('ability.cutover_phase', function (): bool {
            return in_array(config('security.ability_cutover_phase'), [
                'instrument',
                'rotate',
                'deprecate',
                'remove',
            ], true);
        });
        $this->checkLegacyFallback();

        $this->integrationStatus('integrations.midtrans', [
            config('services.midtrans.merchant_id'),
            config('services.midtrans.server_key'),
            config('services.midtrans.client_key'),
        ]);
        $this->integrationStatus('integrations.whatsapp', [
            config('services.whatsapp.access_token'),
            config('services.whatsapp.phone_number_id'),
        ]);
        $this->integrationStatus('integrations.fcm', [
            config('services.fcm.server_key'),
        ]);

        if ($this->failures !== []) {
            $this->error('Release preflight failed.');

            return self::FAILURE;
        }

        $this->info('Release preflight passed.');

        return self::SUCCESS;
    }

    private function check(string $name, callable $check): void
    {
        try {
            if ($check()) {
                $this->pass($name);

                return;
            }
        } catch (Throwable) {
            // The output must not expose configuration values or exception data.
        }

        $this->failCheck($name, 'invalid configuration');
    }

    private function pass(string $name, string $status = 'PASS'): void
    {
        $this->line($name.': '.$status);
    }

    private function failCheck(string $name, string $reason): void
    {
        $this->line($name.': FAIL ('.$reason.')');
        $this->failures[] = $name;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function integrationStatus(string $name, array $values): void
    {
        $configured = collect($values)->every(static fn (mixed $value): bool => filled($value));

        $this->pass($name, $configured ? 'CONFIGURED' : 'DISABLED');
    }

    private function checkLegacyFallback(): void
    {
        if (config('security.legacy_ability_fallback_enabled') !== true) {
            $this->pass('ability.legacy_fallback', 'DISABLED');

            return;
        }

        $expiry = config('security.legacy_ability_fallback_expires_at');

        $this->check('ability.legacy_fallback_expiry', function () use ($expiry): bool {
            if (! is_string($expiry) || trim($expiry) === '') {
                return false;
            }

            return CarbonImmutable::parse($expiry)->isFuture();
        });
    }

    private function hasValidPiiKeyMaps(): bool
    {
        $encryptionKeys = config('security.encryption_keys');
        $blindIndexKeys = config('security.blind_index_keys');

        if (! is_array($encryptionKeys) || $encryptionKeys === []) {
            return false;
        }

        if (! is_array($blindIndexKeys) || $blindIndexKeys === []) {
            return false;
        }

        foreach ([$encryptionKeys, $blindIndexKeys] as $keyMap) {
            foreach ($keyMap as $key) {
                if (! is_string($key) || $this->decodeKey($key) === null) {
                    return false;
                }
            }
        }

        return true;
    }

    private function hasValidPiiVersions(): bool
    {
        $encryptionKeys = config('security.encryption_keys');
        $blindIndexKeys = config('security.blind_index_keys');
        $currentEncryptionVersion = (string) config('security.encryption_current_version');
        $currentBlindIndexVersion = (string) config('security.blind_index_current_version');
        $activeVersions = config('security.blind_index_active_versions');

        return is_array($encryptionKeys)
            && is_array($blindIndexKeys)
            && array_key_exists($currentEncryptionVersion, $encryptionKeys)
            && array_key_exists($currentBlindIndexVersion, $blindIndexKeys)
            && is_array($activeVersions)
            && $activeVersions !== []
            && in_array($currentBlindIndexVersion, $activeVersions, true)
            && collect($activeVersions)->every(
                static fn (mixed $version): bool => is_string($version) && array_key_exists($version, $blindIndexKeys),
            );
    }

    private function hasDistinctPiiKeys(): bool
    {
        $encryptionKeys = config('security.encryption_keys');
        $blindIndexKeys = config('security.blind_index_keys');

        if (! is_array($encryptionKeys) || ! is_array($blindIndexKeys)) {
            return false;
        }

        foreach ($encryptionKeys as $encryptionKey) {
            $decodedEncryptionKey = is_string($encryptionKey) ? $this->decodeKey($encryptionKey) : null;
            if ($decodedEncryptionKey === null) {
                return false;
            }

            foreach ($blindIndexKeys as $blindIndexKey) {
                $decodedBlindIndexKey = is_string($blindIndexKey) ? $this->decodeKey($blindIndexKey) : null;
                if ($decodedBlindIndexKey === null || hash_equals($decodedEncryptionKey, $decodedBlindIndexKey)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function decodeKey(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        return strlen($key) === 32 ? $key : null;
    }
}
