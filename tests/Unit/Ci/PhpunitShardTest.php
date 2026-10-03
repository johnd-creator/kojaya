<?php

namespace Tests\Unit\Ci;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

if (! defined('PHPUNIT_SHARD_TESTING')) {
    define('PHPUNIT_SHARD_TESTING', true);
}
require_once __DIR__.'/../../../bin/ci/phpunit-shard';

class PhpunitShardTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/ci-timings-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/tests/Unit', 0700, true);
        foreach (['ATest.php', 'BTest.php', 'CTest.php', 'NewTest.php'] as $file) {
            file_put_contents($this->directory.'/tests/Unit/'.$file, '<?php public function test_fixture(): void {}');
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_runtime_weights_are_deterministic_and_mece_for_supported_counts(): void
    {
        $files = getCanonicalTestFiles($this->directory, []);
        $history = ['fallback_ms_per_weight' => 10, 'files' => []];
        foreach ($files as $index => $path) {
            $history['files'][$path] = ['milliseconds' => 1000 * ($index + 1), 'sha256' => hash_file('sha256', $this->directory.'/'.$path)];
        }
        foreach ([2, 4, 5, 6] as $total) {
            $first = partitionFiles($files, $total, $this->directory, $history);
            $this->assertSame($first, partitionFiles(array_reverse($files), $total, $this->directory, $history));
            $assigned = array_merge(...array_column($first, 'files'));
            $this->assertEqualsCanonicalizing($files, $assigned);
            $this->assertCount(count($files), array_unique($assigned));
            $this->assertSame(10000, array_sum(array_column($first, 'weight')));
        }
    }

    public function test_new_or_changed_files_receive_the_conservative_static_fallback(): void
    {
        $path = 'tests/Unit/NewTest.php';
        $history = ['fallback_ms_per_weight' => 10, 'files' => []];
        $expected = calculateFileWeight($this->directory.'/'.$path)['weight'] * 10;
        $this->assertSame($expected, partitionFiles([$path], 1, $this->directory, $history)[1]['weight']);
        $history['files'][$path] = ['milliseconds' => 1, 'sha256' => str_repeat('0', 64)];
        $this->assertSame($expected, partitionFiles([$path], 1, $this->directory, $history)[1]['weight']);
    }

    public function test_junit_manifest_counts_real_cases_without_nested_suite_inflation(): void
    {
        $canonical = ['tests/Unit/ATest.php'];
        $report = $this->directory.'/junit.xml';
        file_put_contents($report, '<testsuites tests="99"><testsuite file="/runner/tests/Unit/ATest.php"><testcase class="A" name="one" time="1.25"/><testcase class="A" name="two" time="0.75"/></testsuite></testsuites>');
        $data = buildPhpunitTimings([$report], $canonical, $this->directory, 'fixture');
        $this->assertSame(2000, $data['files'][$canonical[0]]['milliseconds']);
        $this->assertSame(2, $data['files'][$canonical[0]]['cases']);
        $this->assertSame(hash_file('sha256', $this->directory.'/'.$canonical[0]), $data['files'][$canonical[0]]['sha256']);
        $path = $this->directory.'/timings.json';
        file_put_contents($path, json_encode($data));
        $this->assertSame($data, loadPhpunitTimings($path));
    }

    public function test_unsuccessful_missing_duplicate_and_malformed_timing_inputs_fail_closed(): void
    {
        $report = $this->directory.'/junit.xml';
        foreach (['<invalid', '<testsuites/>', '<testsuites><testcase file="tests/Unit/ATest.php" name="one" time="NaN"/></testsuites>', '<testsuites><testcase file="tests/Unit/ATest.php" name="one" time="1"><skipped/></testcase></testsuites>', '<testsuites><testcase file="tests/Unit/ATest.php" name="one" time="1"/><testcase file="tests/Unit/ATest.php" name="one" time="1"/></testsuites>'] as $xml) {
            file_put_contents($report, $xml);
            try {
                buildPhpunitTimings([$report], ['tests/Unit/ATest.php'], $this->directory, 'fixture');
                $this->fail('Invalid timing reports must fail closed.');
            } catch (RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        foreach (['{}', '{', '{"version":1,"fallback_ms_per_weight":-1,"files":{}}', '{"version":1,"fallback_ms_per_weight":1,"files":{"../ATest.php":{"milliseconds":1,"cases":1,"sha256":"bad"}}}'] as $content) {
            $path = $this->directory.'/invalid.json';
            file_put_contents($path, $content);
            try {
                loadPhpunitTimings($path);
                $this->fail('Invalid manifests must fail closed.');
            } catch (\Throwable $exception) {
                $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $exception);
            }
        }
    }

    public function test_canonical_discovery_respects_the_existing_exclusions(): void
    {
        $files = getCanonicalTestFiles($this->directory, ['tests/Unit/ATest.php']);
        $this->assertCount(3, $files);
        $this->assertNotContains('tests/Unit/ATest.php', $files);
    }
}
