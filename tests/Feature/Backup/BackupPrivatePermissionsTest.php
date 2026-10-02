<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupVerificationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SQLite3;
use Tests\TestCase;

class BackupPrivatePermissionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('app/private/backup-permissions-test-'.uniqid());
        File::ensureDirectoryExists($this->root);
        $database = $this->root.'/fixture.sqlite';
        $sqlite = new SQLite3($database);
        $sqlite->exec('CREATE TABLE fixture (id INTEGER PRIMARY KEY)');
        $sqlite->exec('INSERT INTO fixture VALUES (1)');
        $sqlite->close();

        Config::set('database.default', 'backup_permissions');
        Config::set('database.connections.backup_permissions', ['driver' => 'sqlite', 'database' => $database]);
        Config::set('filesystems.disks.backup_permissions', [
            'driver' => 'local', 'root' => $this->root, 'throw' => true,
        ]);
        Config::set('operations.backup.enabled', true);
        Config::set('operations.backup.disk', 'backup_permissions');
        Config::set('operations.backup.directory', 'managed');
        Config::set('operations.backup.offsite_enabled', false);
        Config::set('operations.backup.offsite_disk', null);
        Config::set('operations.backup.require_offsite', false);
    }

    protected function tearDown(): void
    {
        Config::set('database.default', 'sqlite');
        DB::purge('backup_permissions');
        Storage::forgetDisk('backup_permissions');
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public static function permissiveUmasks(): array
    {
        return ['no restriction' => [0000], 'usual shell' => [0022], 'all creation bits masked' => [0777]];
    }

    #[DataProvider('permissiveUmasks')]
    public function test_real_local_artifacts_and_existing_directory_are_private_without_shell_umask(int $mask): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX modes require Linux; NTFS mode bits are not a privacy proof.');
        }
        File::ensureDirectoryExists($this->root.'/managed');
        chmod($this->root.'/managed', 0777);
        $previous = umask($mask);
        try {
            $this->artisan('backup:database')->assertSuccessful();
        } finally {
            umask($previous);
        }

        $files = Storage::disk('backup_permissions')->files('managed');
        $this->assertCount(3, $files);
        foreach ($files as $path) {
            $absolute = Storage::disk('backup_permissions')->path($path);
            clearstatcache(true, $absolute);
            $this->assertSame(0600, fileperms($absolute) & 0777, $path);
        }
        clearstatcache(true, $this->root.'/managed');
        $this->assertSame(0700, fileperms($this->root.'/managed') & 0777);
        $dump = array_values(array_filter($files, fn (string $path): bool => str_ends_with($path, '.sqlite')))[0];
        $this->artisan('backup:verify', [
            'path' => $dump, '--disk' => 'backup_permissions', '--require-private-permissions' => true,
        ])->assertSuccessful();
    }

    public function test_permission_verification_failure_fails_command_and_cleans_only_new_artifacts(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        Storage::disk('backup_permissions')->put('managed/historical.sql', 'historical backup');
        $this->partialMock(BackupVerificationService::class, function ($mock): void {
            $mock->shouldReceive('assertPrivatePermissions')->andThrow(new RuntimeException('Injected permission verification failure'));
        });
        $this->artisan('backup:database')
            ->expectsOutputToContain('Injected permission verification failure')
            ->doesntExpectOutputToContain('Backup successfully created and verified')
            ->assertFailed();
        $this->assertSame(['managed/historical.sql'], Storage::disk('backup_permissions')->files('managed'));
        $this->assertSame('historical backup', Storage::disk('backup_permissions')->get('managed/historical.sql'));
    }

    public function test_visibility_enforcement_failure_fails_closed(): void
    {
        $storage = Storage::disk('backup_permissions');
        $mock = \Mockery::mock($storage)->makePartial();
        $mock->shouldReceive('setVisibility')->andReturn(false);
        Storage::set('backup_permissions', $mock);
        $this->artisan('backup:database')
            ->expectsOutputToContain('Unable to establish private backup visibility')
            ->doesntExpectOutputToContain('Backup successfully created and verified')
            ->assertFailed();
        $this->assertEmpty($storage->files('managed'));
    }

    #[DataProvider('permissiveUmasks')]
    public function test_new_nested_directory_is_private_independently_of_umask(int $mask): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        Config::set('operations.backup.directory', 'nested/managed');
        $previous = umask($mask);
        try {
            $this->artisan('backup:database')->assertSuccessful();
        } finally {
            umask($previous);
        }
        foreach (['nested', 'nested/managed'] as $directory) {
            clearstatcache(true, $this->root.'/'.$directory);
            $this->assertSame(0700, fileperms($this->root.'/'.$directory) & 0777);
        }
        $this->assertCount(3, Storage::disk('backup_permissions')->files('nested/managed'));
    }

    public function test_directory_separator_alias_uses_the_same_private_local_namespace(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        $this->artisan('backup:database', ['--directory' => 'nested\\managed'])->assertSuccessful();
        $files = Storage::disk('backup_permissions')->files('nested/managed');
        $this->assertCount(3, $files);
        foreach ($files as $path) {
            clearstatcache(true, $this->root.'/'.$path);
            $this->assertSame(0600, fileperms($this->root.'/'.$path) & 0777);
        }
    }

    public function test_final_privacy_check_after_offsite_manifest_update_fails_closed(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        Config::set('filesystems.disks.offsite_test', ['driver' => 'local', 'root' => $this->root.'/offsite']);
        $verifier = new BackupVerificationService;
        $checks = 0;
        $mock = \Mockery::mock(BackupVerificationService::class, [null])->makePartial();
        $mock->shouldReceive('assertPrivatePermissions')->andReturnUsing(function (string $disk, string $path, bool $requireProtectedRoot) use ($verifier, &$checks): void {
            if (++$checks === 2) {
                throw new RuntimeException('Injected final permission verification failure');
            }
            $verifier->assertPrivatePermissions($disk, $path, $requireProtectedRoot);
        });
        $this->instance(BackupVerificationService::class, $mock);
        $this->artisan('backup:database', ['--offsite-disk' => 'offsite_test'])
            ->expectsOutputToContain('Injected final permission verification failure')
            ->doesntExpectOutputToContain('Backup successfully created and verified')
            ->assertFailed();
        $this->assertSame(2, $checks);
        $this->assertEmpty(Storage::disk('backup_permissions')->files('managed'));
        Storage::forgetDisk('offsite_test');
    }

    public function test_new_directory_and_primary_files_remain_private_after_offsite_update(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        Config::set('filesystems.disks.offsite_test', ['driver' => 'local', 'root' => $this->root.'/offsite']);
        $previous = umask(0000);
        try {
            $this->artisan('backup:database', ['--offsite-disk' => 'offsite_test'])->assertSuccessful();
        } finally {
            umask($previous);
        }
        $files = Storage::disk('backup_permissions')->files('managed');
        $this->assertCount(3, $files);
        foreach ($files as $path) {
            clearstatcache(true, $this->root.'/'.$path);
            $this->assertSame(0600, fileperms($this->root.'/'.$path) & 0777);
        }
        clearstatcache(true, $this->root.'/managed');
        $this->assertSame(0700, fileperms($this->root.'/managed') & 0777);
        Storage::forgetDisk('offsite_test');
    }
}
