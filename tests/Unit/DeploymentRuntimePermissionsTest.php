<?php

namespace Tests\Unit;

use App\Support\DeploymentRuntimePermissions;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class DeploymentRuntimePermissionsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/qa-runtime-permissions-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        foreach (['app', 'bootstrap/cache', 'public/build', 'vendor/bin', 'storage/app/private/backups/database', 'storage/framework/views', 'storage/logs'] as $path) {
            mkdir($this->directory.'/'.$path, 0700, true);
        }
        file_put_contents($this->directory.'/app/Example.php', '<?php return true;');
        file_put_contents($this->directory.'/bootstrap/app.php', '<?php return true;');
        $this->command(['git', 'init', '-q']);
        $this->command(['git', 'add', 'app', 'bootstrap/app.php']);
        $this->command(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.invalid', 'commit', '-qm', 'fixture']);
        unlink($this->directory.'/app/Example.php');
        unlink($this->directory.'/bootstrap/app.php');
        $this->command(['bash', '-c', 'umask 077; git checkout -- app bootstrap/app.php']);
        foreach (['.env', 'bootstrap/cache/config.php', 'bootstrap/cache/packages.php', 'vendor/autoload.php', 'vendor/bin/tool', 'public/build/app.js', 'storage/framework/views/view.php', 'storage/logs/laravel.log', 'storage/app/private/backups/database/evidence.dump'] as $path) {
            file_put_contents($this->directory.'/'.$path, 'non-secret fixture');
            chmod($this->directory.'/'.$path, $path === '.env' ? 0640 : 0600);
        }
        chmod($this->directory.'/vendor/bin/tool', 0700);
        chmod($this->directory.'/bootstrap/cache/packages.php', 0700);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_real_restrictive_git_checkout_and_generated_files_become_runtime_accessible_without_exposing_private_artifacts(): void
    {
        $this->assertMode('bootstrap/app.php', 0600);
        $environment = file_get_contents($this->directory.'/.env');
        (new DeploymentRuntimePermissions)->apply($this->directory, filegroup($this->directory.'/.env'));

        foreach (['app/Example.php', 'bootstrap/app.php', 'vendor/autoload.php', 'public/build/app.js'] as $path) {
            $this->assertMode($path, 0644);
        }
        foreach (['app', 'bootstrap', 'vendor', 'vendor/bin', 'public', 'public/build'] as $path) {
            $this->assertMode($path, 0755);
        }
        $this->assertMode('vendor/bin/tool', 0755);
        foreach (['bootstrap/cache/config.php', 'bootstrap/cache/packages.php'] as $path) {
            $this->assertMode($path, 0640);
            $this->assertSame(filegroup($this->directory.'/.env'), filegroup($this->directory.'/'.$path));
        }
        foreach (['storage', 'storage/app', 'storage/app/private', 'storage/framework', 'storage/framework/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $path) {
            $this->assertMode($path, 02770);
            $this->assertSame(filegroup($this->directory.'/.env'), filegroup($this->directory.'/'.$path));
        }
        $this->assertMode('storage/framework/views/view.php', 0660);
        $this->assertMode('storage/logs/laravel.log', 0660);
        $this->assertMode('.env', 0640);
        $this->assertSame($environment, file_get_contents($this->directory.'/.env'));
        $this->assertMode('storage/app/private/backups/database', 0700);
        $this->assertMode('storage/app/private/backups/database/evidence.dump', 0600);
        $this->assertSame('', $this->command(['git', 'status', '--porcelain', '--untracked-files=no']));
    }

    public function test_insecure_environment_fails_before_any_application_permission_change(): void
    {
        chmod($this->directory.'/.env', 0644);
        try {
            (new DeploymentRuntimePermissions)->apply($this->directory, filegroup($this->directory.'/.env'));
            $this->fail('Insecure environment must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('environment security metadata', $exception->getMessage());
        }
        $this->assertMode('bootstrap/app.php', 0600);
        $this->assertMode('.env', 0644);
    }

    public function test_symlinked_runtime_target_fails_without_changing_external_private_file(): void
    {
        $outside = $this->directory.'/private-credential';
        file_put_contents($outside, 'private fixture');
        chmod($outside, 0600);
        symlink($outside, $this->directory.'/vendor/credential-link');
        try {
            (new DeploymentRuntimePermissions)->apply($this->directory, filegroup($this->directory.'/.env'));
            $this->fail('Symlink must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('symlink', $exception->getMessage());
        }
        $this->assertMode('private-credential', 0600);
    }

    private function command(array $arguments): string
    {
        $process = new Process($arguments, $this->directory);
        $process->mustRun();

        return $process->getOutput();
    }

    private function assertMode(string $relative, int $mode): void
    {
        clearstatcache();
        $this->assertSame($mode, fileperms($this->directory.'/'.$relative) & 07777, $relative);
    }
}
