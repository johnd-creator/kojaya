<?php

namespace Tests\Unit\Ci;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/../../../bin/ci/readiness-results';

class CiWorkflowTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private function root(): string
    {
        return dirname(__DIR__, 3);
    }

    public function test_documentation_matching_stays_narrow_and_tooling_requires_full_ci(): void
    {
        foreach ([['true', 'docs/example.md'], ['true', 'README.md', 'docs/nested/file.md'], ['false'], ['false', '.github/workflows/ci.yml'], ['false', 'bin/ci/phpunit-shard'], ['false', 'tests/Unit/Ci/CiWorkflowTest.php'], ['false', 'docs/example.mdx'], ['false', 'docs/file.md', 'app/Example.php'], ['false', 'docs/openapi.json'], ['false', 'composer.lock'], ['false', 'resources/js/app.ts']] as $case) {
            $expected = array_shift($case);
            $process = new Process(['bash', $this->root().'/bin/ci/classify-changes', '--paths', ...$case]);
            $process->mustRun();
            $this->assertSame($expected, trim($process->getOutput()));
        }
    }

    public function test_unknown_or_unresolvable_git_comparison_runs_full_ci(): void
    {
        foreach (['', 'malformed', str_repeat('0', 40)] as $base) {
            $process = new Process(['bash', $this->root().'/bin/ci/classify-changes'], $this->root(), ['BEFORE_SHA' => $base, 'CURRENT_SHA' => str_repeat('f', 40), 'EVENT_NAME' => 'push']);
            $process->mustRun();
            $this->assertSame('false', trim($process->getOutput()));
        }
    }

    public function test_readiness_runs_in_parallel_and_final_decision_keeps_all_mandatory_gates(): void
    {
        $workflow = Yaml::parseFile($this->root().'/.github/workflows/ci.yml');
        $jobs = $workflow['jobs'];
        $this->assertSame(['changes', 'frontend-build'], $jobs['phase4-execution']['needs']);
        $this->assertSame('Phase 4 Readiness Gate', $jobs['phase4-readiness']['name']);
        $this->assertSame('${{ always() }}', $jobs['phase4-readiness']['if']);
        $this->assertCount(12, $jobs['phase4-readiness']['needs']);
        $this->assertSame('PHPUnit Parallel', $jobs['unit-feature-tests']['name']);
        $this->assertFalse($jobs['phpunit-shard']['strategy']['fail-fast']);
        $this->assertSame([1, 2, 3, 4], $jobs['phpunit-shard']['strategy']['matrix']['shard']);
        $commands = implode('\n', array_column($jobs['unit-feature-tests']['steps'], 'run'));
        $this->assertStringContainsString('--min-coverage=60 --min-tests=2211', $commands);
        $this->assertStringNotContainsString('continue-on-error', file_get_contents($this->root().'/.github/workflows/ci.yml'));
        foreach (['PostgreSQLConcurrency', 'Document05PostgreSQL', 'BackupRestoreDrill'] as $suite) {
            $this->assertStringContainsString($suite, implode('\n', array_column($jobs['postgres-concurrency']['steps'], 'run')));
        }
        foreach (['SEED-09', 'migrate:fresh --seed', 'ui:verify-baselines', 'ui:a11y', 'verify-func13-accessibility', 'pint --test --parallel'] as $contract) {
            $this->assertStringContainsString($contract, file_get_contents($this->root().'/.github/workflows/ci.yml'));
        }
    }

    public function test_full_and_docs_readiness_fail_closed_for_every_unsuccessful_upstream(): void
    {
        $workflow = Yaml::parseFile($this->root().'/.github/workflows/ci.yml');
        $results = array_fill_keys($workflow['jobs']['phase4-readiness']['needs'], 'success');
        foreach (['true', 'false'] as $classification) {
            verifyReadinessResults($classification, $results);
            $this->assertCount(12, $results);
            foreach (array_keys($results) as $job) {
                foreach (['failure', 'cancelled', 'skipped', ''] as $outcome) {
                    try {
                        verifyReadinessResults($classification, array_replace($results, [$job => $outcome]));
                        $this->fail('Unsuccessful mandatory job must fail closed.');
                    } catch (RuntimeException $exception) {
                        $this->assertStringContainsString($job, $exception->getMessage());
                    }
                }
            }
        }
        foreach (['', 'unknown'] as $classification) {
            try {
                verifyReadinessResults($classification, $results);
                $this->fail('Unknown classification must fail closed.');
            } catch (RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        unset($results['phase4-execution']);
        $this->expectException(RuntimeException::class);
        verifyReadinessResults('false', $results);
    }

    public function test_playwright_keeps_its_distinct_build_even_when_public_environment_values_match(): void
    {
        $contexts = [];
        foreach (['.env.example', '.env.playwright.example'] as $file) {
            $text = file_get_contents($this->root().'/'.$file);
            preg_match('/^APP_NAME=(.+)$/m', $text, $name);
            preg_match_all('/^(VITE_[A-Z_]+)=(.+)$/m', $text, $matches, PREG_SET_ORDER);
            $values = [];
            foreach ($matches as $match) {
                $values[$match[1]] = str_replace('${APP_NAME}', trim($name[1], '"'), trim($match[2], '"'));
            }
            $contexts[] = $values;
        }
        $workflow = Yaml::parseFile($this->root().'/.github/workflows/ci.yml');
        $steps = $workflow['jobs']['phase4-execution']['steps'];
        $build = array_values(array_filter($steps, fn (array $step): bool => ($step['name'] ?? '') === 'Build frontend for the distinct Playwright environment'));
        $this->assertCount(1, $build);
        $this->assertSame('playwright', $build[0]['env']['APP_ENV']);
        $this->assertStringContainsString('npm run build', $build[0]['run']);
        $this->assertSame($contexts[0], $contexts[1]);
        $this->assertSame(['VITE_APP_NAME' => 'Kojaya'], $contexts[0]);
        $this->assertStringContainsString("environment(['testing', 'playwright'])", file_get_contents($this->root().'/routes/web.php'));
    }
}
