<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CooperativePaymentGatewayReferenceIndexMigrationTest extends TestCase
{
    public function test_gateway_reference_unique_index_migration_uses_expected_partial_index(): void
    {
        $migration = file_get_contents(__DIR__.'/../../database/migrations/2026_06_30_000001_add_unique_gateway_reference_index_to_cooperative_payments.php');

        $this->assertIsString($migration);
        $this->assertStringContainsString('CREATE UNIQUE INDEX cooperative_payments_gateway_provider_reference_unique', $migration);
        $this->assertStringContainsString('ON cooperative_payments (gateway_provider, gateway_reference)', $migration);
        $this->assertStringContainsString('WHERE gateway_reference IS NOT NULL', $migration);
        $this->assertStringContainsString('DROP INDEX IF EXISTS cooperative_payments_gateway_provider_reference_unique', $migration);
    }
}
