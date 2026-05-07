<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseDOpenApiSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_spec_is_valid(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $response->assertOk();

        $spec = $response->json();

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
        $this->assertEquals('3.0.3', $spec['openapi']);
    }

    public function test_openapi_spec_has_security_scheme(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();

        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes'] ?? []);
        $this->assertEquals('bearer', $spec['components']['securitySchemes']['bearerAuth']['scheme'] ?? '');
    }

    public function test_openapi_spec_has_member_endpoints(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();
        $paths = $spec['paths'] ?? [];

        $memberPaths = array_filter(array_keys($paths), fn ($p) => str_contains($p, 'member') || str_contains($p, 'v1/'));
        $this->assertNotEmpty($memberPaths, 'No member API paths found in OpenAPI spec.');
    }

    public function test_openapi_spec_has_ess_endpoints(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();
        $paths = $spec['paths'] ?? [];

        $essPaths = array_filter(array_keys($paths), fn ($p) => str_contains($p, 'ess'));
        $this->assertNotEmpty($essPaths, 'No ESS API paths found in OpenAPI spec.');
    }

    public function test_openapi_spec_has_technician_endpoints(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();
        $paths = $spec['paths'] ?? [];

        $techPaths = array_filter(array_keys($paths), fn ($p) => str_contains($p, 'technician'));
        $this->assertNotEmpty($techPaths, 'No technician API paths found in OpenAPI spec.');
    }

    public function test_openapi_spec_all_paths_have_operation_ids(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $item) {
                $this->assertArrayHasKey(
                    'operationId',
                    $item,
                    "Missing operationId for {$method} {$path}"
                );
            }
        }
    }

    public function test_openapi_spec_pagination_schema_exists(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();
        $schemas = $spec['components']['schemas'] ?? [];

        $this->assertArrayHasKey('PaginatedResponse', $schemas, 'PaginatedResponse schema missing from OpenAPI spec.');
    }

    public function test_openapi_spec_error_schema_exists(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $spec = $response->json();
        $schemas = $spec['components']['schemas'] ?? [];

        $this->assertArrayHasKey('Error', $schemas, 'Error schema missing from OpenAPI spec.');
    }

    public function test_openapi_spec_snapshot_command_generates_file(): void
    {
        $this->artisan('openapi:snapshot')->assertSuccessful();

        $this->assertFileExists(storage_path('openapi.snapshot.json'));

        $snapshot = json_decode(file_get_contents(storage_path('openapi.snapshot.json')), true);
        $this->assertArrayHasKey('paths', $snapshot);
    }

    public function test_openapi_spec_snapshot_validate_passes_on_match(): void
    {
        $this->artisan('openapi:snapshot')->assertSuccessful();
        $this->artisan('openapi:snapshot', ['--validate' => true])->assertSuccessful();
    }

    public function test_ci_workflow_file_exists(): void
    {
        $this->assertFileExists(base_path('.github/workflows/ci.yml'));

        $content = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('openapi:snapshot', $content);
        $this->assertStringContainsString('Pint', $content);
        $this->assertStringContainsString('wayfinder:generate', $content);
        $this->assertStringContainsString('npm run build', $content);
    }
}
