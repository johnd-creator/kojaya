<?php

namespace Tests\Unit\Ci;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Symfony\Component\Process\Process;

if (! defined('PHPUNIT_AGGREGATE_TESTING')) {
    define('PHPUNIT_AGGREGATE_TESTING', true);
}
require_once __DIR__.'/../../../bin/ci/phpunit-aggregate';

class PhpunitAggregateTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/phpunit_agg_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__, 3).'/bin/ci/phpunit-aggregate';
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_case_a_nested_testsuites_do_not_double_count_tests_or_assertions(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites tests="10" assertions="20">
    <testsuite name="parent" tests="10" assertions="20">
        <testsuite name="child-a" tests="4" assertions="8">
            <testcase name="t1" class="Tests\Feature\A" assertions="2"/>
            <testcase name="t2" class="Tests\Feature\A" assertions="2"/>
            <testcase name="t3" class="Tests\Feature\A" assertions="2"/>
            <testcase name="t4" class="Tests\Feature\A" assertions="2"/>
        </testsuite>
        <testsuite name="child-b" tests="6" assertions="12">
            <testcase name="t5" class="Tests\Feature\B" assertions="2"/>
            <testcase name="t6" class="Tests\Feature\B" assertions="2"/>
            <testcase name="t7" class="Tests\Feature\B" assertions="2"/>
            <testcase name="t8" class="Tests\Feature\B" assertions="2"/>
            <testcase name="t9" class="Tests\Feature\B" assertions="2"/>
            <testcase name="t10" class="Tests\Feature\B" assertions="2"/>
        </testsuite>
    </testsuite>
</testsuites>
XML;

        $xml = new SimpleXMLElement($xmlContent);
        $result = parseShardJunitXml($xml, 1);

        $this->assertSame(10, $result['tests'], 'Test count must match actual testcase elements (10), not doubled parent+children (20)');
        $this->assertSame(20, $result['assertions'], 'Assertion count must match actual assertions (20), not doubled parent+children (40)');
        $this->assertSame(0, $result['errors']);
        $this->assertSame(0, $result['failures']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_case_b_testcase_failure_is_detected(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Suite">
        <testcase name="test_ok" class="Tests\Feature\A" assertions="1"/>
        <testcase name="test_fail" class="Tests\Feature\A" assertions="1">
            <failure message="Failed asserting that false is true." type="PHPUnit\Framework\ExpectationFailedException">Stack trace...</failure>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $xml = new SimpleXMLElement($xmlContent);
        $result = parseShardJunitXml($xml, 1);

        $this->assertSame(2, $result['tests']);
        $this->assertSame(1, $result['failures']);
        $this->assertSame(0, $result['errors']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_case_c_testcase_error_is_detected(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Suite">
        <testcase name="test_ok" class="Tests\Feature\A" assertions="1"/>
        <testcase name="test_err" class="Tests\Feature\A" assertions="0">
            <error message="Call to undefined method" type="Error">Stack trace...</error>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $xml = new SimpleXMLElement($xmlContent);
        $result = parseShardJunitXml($xml, 1);

        $this->assertSame(2, $result['tests']);
        $this->assertSame(0, $result['failures']);
        $this->assertSame(1, $result['errors']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_case_d_testcase_skip_is_detected_for_zero_skip_policy(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Suite">
        <testcase name="test_ok" class="Tests\Feature\A" assertions="1"/>
        <testcase name="test_skipped" class="Tests\Feature\A" assertions="0">
            <skipped/>
        </testcase>
    </testsuite>
</testsuites>
XML;

        $xml = new SimpleXMLElement($xmlContent);
        $result = parseShardJunitXml($xml, 1);

        $this->assertSame(2, $result['tests']);
        $this->assertSame(0, $result['failures']);
        $this->assertSame(0, $result['errors']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['skipped_details']);
        $this->assertStringContainsString('test_skipped', $result['skipped_details'][0]);
    }

    public function test_case_e_inflated_parent_suite_tests_attribute_does_not_inflate_test_count(): void
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites tests="3000" assertions="6000">
    <testsuite name="parent" tests="3000" assertions="6000">
        <testsuite name="child" tests="3000" assertions="6000">
            <testcase name="t1" class="Tests\Feature\A" assertions="2"/>
            <testcase name="t2" class="Tests\Feature\A" assertions="2"/>
            <testcase name="t3" class="Tests\Feature\A" assertions="2"/>
        </testsuite>
    </testsuite>
</testsuites>
XML;

        $xml = new SimpleXMLElement($xmlContent);
        $result = parseShardJunitXml($xml, 1);

        $this->assertSame(3, $result['tests'], 'Test count must reflect actual testcases (3), rejecting the inflated 3000');
        $this->assertSame(6, $result['assertions'], 'Assertion count must reflect actual assertions (6), rejecting the inflated 6000');
    }

    public function test_case_f_malformed_xml_fails_closed(): void
    {
        file_put_contents($this->tempDir.'/shard-1.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-1.xml', '<testsuites><testsuite tests="1"><testcase name="t1"/></testsuite></testsuites>');

        file_put_contents($this->tempDir.'/shard-2.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-2.xml', '<testsuites><testsuite name="unclosed"');

        $process = new Process([
            PHP_BINARY,
            $this->scriptPath(),
            '--total=2',
            '--artifacts-dir='.$this->tempDir,
            '--min-tests=1',
        ]);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Aggregator must fail closed on malformed XML');
        $this->assertStringContainsString('[FAIL-CLOSED] Malformed JUnit XML', $process->getErrorOutput().$process->getOutput());
    }

    public function test_case_g_missing_artifact_fails_closed(): void
    {
        file_put_contents($this->tempDir.'/shard-1.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-1.xml', '<testsuites><testcase name="t1"/></testsuites>');

        $process = new Process([
            PHP_BINARY,
            $this->scriptPath(),
            '--total=2',
            '--artifacts-dir='.$this->tempDir,
            '--min-tests=1',
        ]);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Aggregator must fail closed when an artifact is missing');
        $this->assertStringContainsString('[FAIL-CLOSED] Missing or empty', $process->getErrorOutput().$process->getOutput());
    }

    public function test_cli_fails_closed_when_test_fails(): void
    {
        file_put_contents($this->tempDir.'/shard-1.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-1.xml', <<<'XML'
<testsuites>
    <testsuite name="Suite">
        <testcase name="t_fail" class="Tests\Feature\A">
            <failure message="assertion failed"/>
        </testcase>
    </testsuite>
</testsuites>
XML);

        $process = new Process([
            PHP_BINARY,
            $this->scriptPath(),
            '--total=1',
            '--artifacts-dir='.$this->tempDir,
            '--min-tests=1',
        ]);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Aggregator must fail closed on test failure');
        $this->assertStringContainsString('0 errors, 1 failures encountered', $process->getErrorOutput().$process->getOutput());
    }

    public function test_cli_fails_closed_when_test_is_skipped(): void
    {
        file_put_contents($this->tempDir.'/shard-1.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-1.xml', <<<'XML'
<testsuites>
    <testsuite name="Suite">
        <testcase name="t_skip" class="Tests\Feature\A">
            <skipped/>
        </testcase>
    </testsuite>
</testsuites>
XML);

        $process = new Process([
            PHP_BINARY,
            $this->scriptPath(),
            '--total=1',
            '--artifacts-dir='.$this->tempDir,
            '--min-tests=1',
        ]);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Aggregator must fail closed on skipped test');
        $this->assertStringContainsString('Found 1 skipped tests! Zero-skip contract violated.', $process->getErrorOutput().$process->getOutput());
    }

    public function test_cli_fails_closed_when_test_count_below_min_tests_even_with_inflated_parent(): void
    {
        file_put_contents($this->tempDir.'/shard-1.cov', 'dummy');
        file_put_contents($this->tempDir.'/shard-1.xml', <<<'XML'
<testsuites tests="5000">
    <testsuite name="parent" tests="5000">
        <testcase name="t1" class="Tests\Feature\A"/>
        <testcase name="t2" class="Tests\Feature\A"/>
    </testsuite>
</testsuites>
XML);

        $process = new Process([
            PHP_BINARY,
            $this->scriptPath(),
            '--total=1',
            '--artifacts-dir='.$this->tempDir,
            '--min-tests=2211',
        ]);
        $process->run();

        $this->assertNotSame(0, $process->getExitCode(), 'Aggregator must fail closed when actual test count is below --min-tests, ignoring inflated parent suite attribute');
        $this->assertStringContainsString('Total tests (2) is less than expected minimum (2211)', $process->getErrorOutput().$process->getOutput());
    }
}
