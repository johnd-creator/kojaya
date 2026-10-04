<?php

namespace Tests\Unit;

use App\Support\QaRuntimeFileCreationMask;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class QaRuntimeFileCreationMaskTest extends TestCase
{
    private int $originalMask;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMask = umask();
        $this->directory = sys_get_temp_dir().'/qa-runtime-mask-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        umask($this->originalMask);
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_qa_fpm_creates_shared_private_cache_files_and_traversable_group_writable_directories(): void
    {
        $cacheDirectory = $this->directory.'/cache';
        mkdir($cacheDirectory, 02770);
        chmod($cacheDirectory, 02770);
        umask(0022);

        QaRuntimeFileCreationMask::apply('qa', 'fpm-fcgi');
        $store = new FileStore(new Filesystem, $cacheDirectory);
        $this->assertTrue($store->put('qa-runtime-permission-regression', 'non-secret fixture', 300));
        $this->assertSame('non-secret fixture', $store->get('qa-runtime-permission-regression'));
        $this->assertSame(0007, umask());

        $directories = 0;
        $files = 0;
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDirectory, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($entries as $entry) {
            $this->assertSame(filegroup($cacheDirectory), $entry->getGroup());
            $this->assertSame($entry->isDir() ? 02770 : 0660, $entry->getPerms() & 07777);
            if ($entry->isDir()) {
                $directories++;
            } else {
                $files++;
            }
        }
        $this->assertSame(2, $directories);
        $this->assertSame(1, $files);
    }

    #[DataProvider('unaffectedContexts')]
    public function test_other_environments_and_qa_cli_preserve_the_existing_private_creation_mask(string $environment, string $sapi): void
    {
        umask(0077);

        QaRuntimeFileCreationMask::apply($environment, $sapi);
        file_put_contents($this->directory.'/private-input', 'non-secret protected fixture');

        $this->assertSame(0077, umask());
        $this->assertSame(0600, fileperms($this->directory.'/private-input') & 07777);
    }

    public static function unaffectedContexts(): array
    {
        return [
            'QA deployment CLI' => ['qa', 'cli'],
            'QA non-FPM CGI' => ['qa', 'cgi-fcgi'],
            'production FPM' => ['production', 'fpm-fcgi'],
            'local FPM' => ['local', 'fpm-fcgi'],
            'testing FPM' => ['testing', 'fpm-fcgi'],
        ];
    }
}
