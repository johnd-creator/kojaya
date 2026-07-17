<?php

namespace Tests\Feature;

use App\Services\Security\PiiCryptoService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReleasePreflightTest extends TestCase
{
    public function test_release_preflight_accepts_the_valid_local_development_baseline(): void
    {
        $this->configureBaseline();

        $this->artisan('app:release-preflight')
            ->expectsOutput('application.release_version: PASS')
            ->expectsOutput('api.contract_version: PASS')
            ->expectsOutput('integrations.midtrans: DISABLED')
            ->assertExitCode(0);
    }

    public function test_strict_production_preflight_rejects_a_non_production_environment(): void
    {
        $this->configureBaseline();
        Config::set([
            'app.env' => 'local',
            'app.debug' => true,
            'app.version' => '0.1.0',
        ]);

        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('application.environment: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }

    public function test_strict_production_preflight_succeeds_with_valid_production_configuration(): void
    {
        $this->configureStrictProductionBaseline();

        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('application.release_version: PASS')
            ->expectsOutput('application.environment: PASS')
            ->expectsOutput('application.debug: PASS')
            ->expectsOutput('application.key: PASS')
            ->expectsOutput('pii.service_resolution: PASS')
            ->expectsOutput('integrations.midtrans: DISABLED')
            ->assertExitCode(0);
    }

    public function test_strict_production_preflight_rejects_a_development_application_version(): void
    {
        $this->configureStrictProductionBaseline();
        Config::set('app.version', '0.1.0-dev');

        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('application.release_version: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }

    public function test_strict_production_preflight_rejects_an_invalid_application_key(): void
    {
        $this->configureStrictProductionBaseline();
        Config::set('app.key', 'invalid-application-key');

        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('application.key: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }

    public function test_empty_midtrans_credentials_are_reported_as_disabled(): void
    {
        $this->configureBaseline();

        $this->artisan('app:release-preflight')
            ->expectsOutput('integrations.midtrans: DISABLED')
            ->assertExitCode(0);
    }

    public function test_fully_configured_midtrans_credentials_are_reported_as_configured(): void
    {
        $this->configureBaseline();
        Config::set([
            'services.midtrans.merchant_id' => 'merchant-test-only',
            'services.midtrans.server_key' => 'server-test-only',
            'services.midtrans.client_key' => 'client-test-only',
        ]);

        $this->artisan('app:release-preflight')
            ->expectsOutput('integrations.midtrans: CONFIGURED')
            ->assertExitCode(0);
    }

    public function test_partially_configured_midtrans_credentials_fail_preflight(): void
    {
        $this->configureBaseline();
        Config::set('services.midtrans.merchant_id', 'merchant-test-only');

        $this->artisan('app:release-preflight')
            ->expectsOutput('integrations.midtrans: FAIL (partial configuration)')
            ->assertExitCode(1);
    }

    public function test_partially_configured_whatsapp_credentials_fail_preflight(): void
    {
        $this->configureBaseline();
        Config::set('services.whatsapp.access_token', 'whatsapp-test-only');

        $this->artisan('app:release-preflight')
            ->expectsOutput('integrations.whatsapp: FAIL (partial configuration)')
            ->assertExitCode(1);
    }

    public function test_equal_encryption_and_blind_index_keys_fail_preflight(): void
    {
        $this->configureBaseline();
        $sharedKey = $this->testKey('Z');
        Config::set([
            'security.encryption_keys' => ['v1' => $sharedKey],
            'security.blind_index_keys' => ['v1' => $sharedKey],
        ]);
        $this->app->forgetInstance(PiiCryptoService::class);

        $this->artisan('app:release-preflight')
            ->expectsOutput('pii.keys_distinct: FAIL (invalid configuration)')
            ->expectsOutput('pii.service_resolution: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }

    public function test_expired_legacy_ability_fallback_fails_preflight(): void
    {
        $this->configureBaseline();
        Config::set([
            'security.legacy_ability_fallback_enabled' => true,
            'security.legacy_ability_fallback_expires_at' => '2000-01-01T00:00:00+00:00',
        ]);

        $this->artisan('app:release-preflight')
            ->expectsOutput('ability.legacy_fallback_expiry: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }

    public function test_failure_output_does_not_expose_credentials_or_key_values(): void
    {
        $this->configureStrictProductionBaseline();
        $applicationKey = 'invalid-application-key-secret';
        $merchantId = 'merchant-test-only';
        $serverKey = 'server-test-only';

        Config::set([
            'app.key' => $applicationKey,
            'services.midtrans.merchant_id' => $merchantId,
            'services.midtrans.server_key' => $serverKey,
        ]);

        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('integrations.midtrans: FAIL (partial configuration)')
            ->doesntExpectOutputToContain($applicationKey)
            ->doesntExpectOutputToContain($merchantId)
            ->doesntExpectOutputToContain($serverKey)
            ->doesntExpectOutputToContain($this->testKey('B'))
            ->doesntExpectOutputToContain($this->testKey('C'))
            ->assertExitCode(1);
    }

    private function configureBaseline(): void
    {
        Config::set([
            'app.env' => 'testing',
            'app.debug' => true,
            'app.key' => $this->testKey('A'),
            'app.version' => '0.1.0-dev',
            'app.api_contract_version' => '1.0.0',
            'security.encryption_keys' => ['v1' => $this->testKey('B')],
            'security.encryption_current_version' => 'v1',
            'security.legacy_encryption_key' => $this->testKey('D'),
            'security.blind_index_keys' => ['v1' => $this->testKey('C')],
            'security.blind_index_current_version' => 'v1',
            'security.blind_index_active_versions' => ['v1'],
            'security.rollout_phase' => PiiCryptoService::ROLLOUT_DUAL_WRITE,
            'security.pii_allow_schema_rollback' => false,
            'security.ability_cutover_phase' => 'instrument',
            'security.legacy_ability_fallback_enabled' => false,
            'security.legacy_ability_fallback_expires_at' => null,
        ]);

        Config::set([
            'services.midtrans.merchant_id' => null,
            'services.midtrans.server_key' => null,
            'services.midtrans.client_key' => null,
            'services.whatsapp.access_token' => null,
            'services.whatsapp.phone_number_id' => null,
            'services.fcm.server_key' => null,
        ]);

        $this->app->forgetInstance(PiiCryptoService::class);
    }

    private function configureStrictProductionBaseline(): void
    {
        $this->configureBaseline();
        Config::set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => $this->testKey('A'),
            'app.version' => '0.1.0',
        ]);
        $this->app->forgetInstance(PiiCryptoService::class);
    }

    private function testKey(string $character): string
    {
        return 'base64:'.base64_encode(str_repeat($character, 32));
    }
}
