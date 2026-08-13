<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicWebRootSecurityTest extends TestCase
{
    public function test_public_web_root_contains_only_the_laravel_front_controller(): void
    {
        $phpFiles = glob(public_path('*.php'));

        $fileNames = array_map(
            static fn (string $path): string => basename($path),
            $phpFiles === false ? [] : $phpFiles,
        );

        sort($fileNames);

        $this->assertSame(['index.php'], $fileNames);
    }

    public function test_removed_administrative_script_urls_are_not_routable(): void
    {
        foreach ([
            '/db_test4.php',
            '/db_test5.php',
            '/db_test6.php',
            '/test-auth.php',
        ] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
