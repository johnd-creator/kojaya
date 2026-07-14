<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionEndpointTest extends TestCase
{
    public function test_version_endpoint_returns_safe_metadata(): void
    {
        $response = $this->getJson('/api/version')->assertOk();

        $response->assertJsonStructure([
            'service',
            'git_sha',
            'built_at',
            'environment',
        ]);

        $this->assertSame('kojaya-backend', $response->json('service'));
    }

    public function test_version_endpoint_does_not_expose_secrets(): void
    {
        $response = $this->getJson('/api/version')->assertOk();

        $body = $response->getContent();
        $this->assertStringNotContainsString('DB_PASSWORD', $body);
        $this->assertStringNotContainsString('APP_KEY', $body);
        $this->assertStringNotContainsString('client_secret', $body);
        $this->assertStringNotContainsString('MIDTRANS_SERVER_KEY', $body);
    }
}
