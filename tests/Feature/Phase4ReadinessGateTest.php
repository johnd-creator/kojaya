<?php

declare(strict_types=1);

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase4ReadinessGateTest extends TestCase
{
    public function test_successful_func12_regression_evidence_is_present_and_not_on_hold(): void
    {
        $report = file_get_contents(base_path('docs/phase-4/FUNC-12-full-regression-report.md'));

        $this->assertNotFalse($report);
        $this->assertStringContainsString('FUNC-12 PASS', $report);
        $this->assertStringNotContainsString('HOLD — REGRESSION FOUND', $report);
    }

    public function test_phase_four_functional_inventory_remains_available(): void
    {
        $inventoryPath = base_path('tests/Feature/Phase4FullRegressionInventoryTest.php');
        $this->assertFileExists($inventoryPath);
        $this->assertFileExists(base_path('phpunit.xml'));

        foreach (Phase4FullRegressionInventoryTest::FUNCTIONAL_EVIDENCE as $path) {
            $this->assertFileExists(base_path($path), "Missing Phase 4 functional evidence: {$path}");
        }
    }

    public function test_all_required_postgresql_suites_remain_configured_and_owned(): void
    {
        $configurationPath = base_path('phpunit.pgsql.xml');
        $document = new DOMDocument;
        $this->assertTrue($document->load($configurationPath));

        $xpath = new DOMXPath($document);
        $suiteNodes = $xpath->query('/phpunit/testsuites/testsuite');
        $suiteNames = [];
        foreach ($suiteNodes as $suiteNode) {
            $suiteNames[] = $suiteNode->getAttribute('name');
        }

        $this->assertEqualsCanonicalizing(Phase4FullRegressionInventoryTest::POSTGRESQL_SUITES, $suiteNames);

        $concurrencyNodes = $xpath->query('/phpunit/testsuites/testsuite[@name="PostgreSQLConcurrency"]/file');
        $ownedFiles = [];
        foreach ($concurrencyNodes as $fileNode) {
            $ownedFiles[] = $fileNode->textContent;
        }

        foreach (Phase4FullRegressionInventoryTest::POSTGRESQL_CONCURRENCY_EVIDENCE as $path) {
            $this->assertContains($path, $ownedFiles, "PostgreSQLConcurrency must own {$path}");
            $this->assertFileExists(base_path($path));
        }
    }

    public function test_release_preflight_command_is_registered(): void
    {
        $this->assertArrayHasKey('app:release-preflight', Artisan::all());
    }

    public function test_dedicated_readiness_job_fails_closed_on_required_upstream_jobs(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));
        $this->assertNotFalse($workflow);

        $jobStart = strpos($workflow, "\n  phase4-readiness:\n");
        $this->assertNotFalse($jobStart, 'The dedicated Phase 4 readiness job must remain in CI.');

        $job = substr($workflow, $jobStart);
        $this->assertStringContainsString('if: ${{ always() }}', $job);
        $this->assertStringContainsString('if [[ "$DOCS_ONLY" != "false" ]]', $job);
        $this->assertStringContainsString('if [[ "$result" != "success" ]]', $job);

        foreach ([
            'changes',
            'dependency-audit',
            'style',
            'frontend-build',
            'generated-drift',
            'phpunit-shard',
            'unit-feature-tests',
            'seed-integrity-gate',
            'migration-seed',
            'openapi-drift',
            'postgres-concurrency',
        ] as $dependency) {
            $this->assertStringContainsString("      - {$dependency}\n", $job);
        }
    }

    public function test_phase_four_contract_report_and_readiness_gate_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-4/FUNC-01-functional-test-contract.md'));
        $this->assertFileExists(base_path('docs/phase-4/FUNC-12-full-regression-report.md'));
        $this->assertFileExists(base_path('docs/phase-4/FUNC-13-phase4-readiness-gate.md'));
    }

    public function test_known_partial_findings_remain_explicit_in_the_func01_contract(): void
    {
        $contract = file_get_contents(base_path('docs/phase-4/FUNC-01-functional-test-contract.md'));
        $this->assertNotFalse($contract);

        foreach (['PAY-006', 'EDGE-001', 'EDGE-002', 'EDGE-004', 'EDGE-005', 'EDGE-006'] as $findingId) {
            $rows = array_filter(
                explode("\n", $contract),
                static fn (string $line): bool => str_contains($line, "**{$findingId}**"),
            );

            $this->assertNotEmpty($rows, "Missing documented finding {$findingId}.");
            $this->assertTrue(
                collect($rows)->contains(static fn (string $line): bool => str_contains($line, '**PARTIAL**')),
                "Finding {$findingId} must remain explicitly PARTIAL.",
            );
        }
    }
}
