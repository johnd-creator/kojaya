<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReleasePreflightTest extends TestCase
{
    public function test_release_preflight_passes_with_valid_non_production_configuration(): void
    {
        Config::set('security.pii_allow_schema_rollback', false);

        $this->artisan('app:release-preflight')
            ->expectsOutput('application.release_version: PASS')
            ->expectsOutput('api.contract_version: PASS')
            ->expectsOutput('integrations.midtrans: DISABLED')
            ->assertExitCode(0);
    }

    public function test_strict_production_preflight_rejects_non_production_environment(): void
    {
        $this->artisan('app:release-preflight', ['--strict-production' => true])
            ->expectsOutput('application.environment: FAIL (invalid configuration)')
            ->assertExitCode(1);
    }
}
