<?php

namespace Tests\Unit\Ci;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../bin/ci/frontend-artifact';

class FrontendArtifactTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/ci-frontend-'.bin2hex(random_bytes(8));
        foreach (['public/build', 'resources/js/actions', 'resources/js/routes'] as $path) {
            mkdir($this->directory.'/'.$path, 0700, true);
        }
        file_put_contents($this->directory.'/public/build/manifest.json', '{}');
        file_put_contents($this->directory.'/public/build/app.js', 'public fixture');
        file_put_contents($this->directory.'/resources/js/routes/index.ts', 'export const fixture = {};');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_exact_source_and_unchanged_generated_inputs_can_reuse_the_build(): void
    {
        $build = $this->directory.'/public/build';
        runFrontendArtifact('create', $build, str_repeat('a', 40), $this->directory);
        runFrontendArtifact('verify', $build, str_repeat('a', 40), $this->directory);
        runFrontendArtifact('verify-generated', $build, str_repeat('a', 40), $this->directory);
        $this->assertFileExists($build.'/ci-provenance.json');
        $this->assertSame(2, count(frontendArtifactDigests($build)));
    }

    public function test_wrong_source_corruption_extra_files_and_changed_generation_fail_closed(): void
    {
        $build = $this->directory.'/public/build';
        runFrontendArtifact('create', $build, str_repeat('a', 40), $this->directory);
        $this->assertRejected(fn () => runFrontendArtifact('verify', $build, str_repeat('b', 40), $this->directory));
        file_put_contents($build.'/unexpected.js', 'extra');
        $this->assertRejected(fn () => runFrontendArtifact('verify', $build, str_repeat('a', 40), $this->directory));
        unlink($build.'/unexpected.js');
        file_put_contents($build.'/app.js', 'corrupt');
        $this->assertRejected(fn () => runFrontendArtifact('verify', $build, str_repeat('a', 40), $this->directory));
        file_put_contents($build.'/app.js', 'public fixture');
        file_put_contents($this->directory.'/resources/js/routes/index.ts', 'changed');
        $this->assertRejected(fn () => runFrontendArtifact('verify-generated', $build, str_repeat('a', 40), $this->directory));
    }

    public function test_symlinks_and_private_files_are_never_accepted(): void
    {
        $build = $this->directory.'/public/build';
        file_put_contents($build.'/.env', 'private fixture');
        $this->assertRejected(fn () => frontendArtifactDigests($build));
        unlink($build.'/.env');
        unlink($build.'/manifest.json');
        $this->assertRejected(fn () => frontendArtifactDigests($build));
        file_put_contents($build.'/manifest.json', '{}');
        symlink('/tmp', $build.'/outside');
        $this->assertRejected(fn () => frontendArtifactDigests($build));
    }

    private function assertRejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Integrity mismatch must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}
