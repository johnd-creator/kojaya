<?php

namespace Tests\Unit;

use Tests\TestCase;

class PosSprint6OpenApiContractTest extends TestCase
{
    public function test_openapi_json_is_valid_and_contains_pos_sync_paths(): void
    {
        $path = base_path('docs/openapi.json');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $decoded = json_decode($contents, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('paths', $decoded);

        $required = [
            '/api/v1/pos/sync/catalog',
            '/api/v1/pos/sync/enqueue',
            '/api/v1/pos/sync/process/{idempotency_key}',
            '/api/v1/pos/sync/batch',
            '/api/v1/pos/sync/status/{idempotency_key}',
        ];

        foreach ($required as $path) {
            $this->assertArrayHasKey($path, $decoded['paths'], "OpenAPI must define {$path}");
        }
    }

    public function test_openapi_pos_returns_payload_requires_pos_transaction_id(): void
    {
        $path = base_path('docs/openapi.json');
        $contents = file_get_contents($path);
        $decoded = json_decode($contents, true);

        $returnsPath = $decoded['paths']['/api/v1/pos/returns'];
        $schema = $returnsPath['post']['requestBody']['content']['application/json']['schema'];
        $this->assertContains('pos_transaction_id', $schema['required']);
    }

    public function test_openapi_pos_sync_enqueue_schema_lists_supported_endpoint(): void
    {
        $path = base_path('docs/openapi.json');
        $contents = file_get_contents($path);
        $decoded = json_decode($contents, true);

        $enqueue = $decoded['paths']['/api/v1/pos/sync/enqueue'];
        $endpointSchema = $enqueue['post']['requestBody']['content']['application/json']['schema']['properties']['endpoint'];
        $this->assertSame(['pos.transactions.store'], $endpointSchema['enum']);
    }
}
