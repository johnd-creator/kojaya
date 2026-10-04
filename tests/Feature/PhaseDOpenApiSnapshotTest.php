<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_member_paginated_response_schemas_match_real_empty_http_responses(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'employee_id' => null,
        ]);
        Sanctum::actingAs($user, ['member:read']);
        $spec = $this->getJson('/api/openapi.json')->assertOk()->json();
        $schema = $spec['components']['schemas']['PaginatedResponse'];
        $this->assertSame('array', $schema['properties']['data']['type']);

        foreach ([
            '/api/v1/member/savings/ledger' => 'PaginatedResponse',
            '/api/v1/member/dues/invoices' => 'PaginatedMemberInvoiceResponse',
            '/api/v1/member/payments' => 'PaginatedResourceResponse',
            '/api/v1/member/loans' => 'PaginatedLoanResponse',
            '/api/v1/member/notifications' => 'PaginatedResourceResponse',
        ] as $path => $schemaName) {
            $this->assertSame(
                '#/components/schemas/'.$schemaName,
                $spec['paths'][$path]['get']['responses']['200']['content']['application/json']['schema']['$ref'],
                $path,
            );
            $response = $this->getJson($path)->assertOk()->assertJsonPath('success', true);
            $this->assertSame([], $response->json('data'), $path);
            foreach (['current_page', 'last_page', 'per_page', 'total'] as $field) {
                $prefix = $schemaName === 'PaginatedResponse' ? '' : 'meta.';
                $this->assertIsInt($response->json($prefix.$field), $path.' '.$field);
            }
        }
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

        $this->assertFileExists(base_path('docs/openapi.json'));

        $snapshot = json_decode(file_get_contents(base_path('docs/openapi.json')), true);
        $this->assertArrayHasKey('paths', $snapshot);
    }

    public function test_openapi_spec_snapshot_check_passes_on_match(): void
    {
        $this->artisan('openapi:snapshot')->assertSuccessful();
        $this->artisan('openapi:snapshot', ['--check' => true])->assertSuccessful();
    }

    public function test_ci_workflow_file_exists(): void
    {
        $this->assertFileExists(base_path('.github/workflows/ci.yml'));

        $content = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('bin/openapi.sh check', $content);
        $this->assertStringContainsString('--parallel', $content);
        $this->assertStringContainsString('shard: ${{ fromJSON(needs.changes.outputs.shard_matrix) }}', $content);
        $this->assertStringContainsString('php bin/ci/phpunit-shard verify --total=${{ needs.changes.outputs.shard_count }}', $content);
        $this->assertStringContainsString('shard_count: ${{ steps.shards.outputs.shard_count }}', $content);
        $this->assertStringContainsString('phpunit-aggregate', $content);
        $this->assertStringContainsString('--min-coverage=60', $content);
        $this->assertStringContainsString('--min-tests=2211', $content);
        $this->assertStringContainsString('Pint', $content);
        $this->assertStringContainsString('wayfinder:generate', $content);
        $this->assertStringContainsString('npm run build', $content);
    }
}
