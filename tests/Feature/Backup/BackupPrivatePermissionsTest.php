<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupDatabaseService;
use App\Services\Backup\BackupManifest;
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
            if ($disk === 'backup_permissions' && ++$checks === 2) {
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

    #[DataProvider('permissiveUmasks')]
    public function test_new_directory_and_primary_files_remain_private_after_offsite_update(int $mask): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX backup creation must be verified on Linux.');
        }
        Config::set('filesystems.disks.offsite_test', ['driver' => 'local', 'root' => $this->root.'/offsite']);
        Storage::disk('offsite_test');
        $previous = umask($mask);
        try {
            $this->artisan('backup:database', ['--offsite-disk' => 'offsite_test', '--offsite-directory' => 'backups\\database'])->assertSuccessful();
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
        $offsite = Storage::disk('offsite_test');
        $offsiteFiles = $offsite->files('backups/database');
        $this->assertCount(3, $offsiteFiles);
        foreach ($offsiteFiles as $path) {
            clearstatcache(true, $offsite->path($path));
            $this->assertSame(0600, fileperms($offsite->path($path)) & 0777, $path);
            if (str_ends_with($path, '.json')) {
                $metadata = json_decode($offsite->get($path), true, flags: JSON_THROW_ON_ERROR);
                $this->assertTrue($metadata['offsite_copy']['copied']);
                $this->assertTrue($metadata['offsite_copy']['sha256_verified']);
            }
        }
        clearstatcache(true, $offsite->path('backups/database'));
        $this->assertSame(0700, fileperms($offsite->path('backups/database')) & 0777);
        Storage::forgetDisk('offsite_test');
    }

    public static function offsiteFailures(): array
    {
        return [
            'required enforcement failure' => [true, false],
            'optional enforcement failure' => [false, false],
            'required final verification failure' => [true, true],
            'optional final verification failure' => [false, true],
        ];
    }

    #[DataProvider('offsiteFailures')]
    public function test_local_offsite_failure_never_reports_unsafe_success(bool $required, bool $finalVerification): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Real POSIX offsite permissions require Linux.');
        }
        Config::set('filesystems.disks.offsite_test', ['driver' => 'local', 'root' => $this->root.'/offsite']);
        $offsite = Storage::disk('offsite_test');
        $offsite->put('backups/database/historical.sql', 'preserve historical backup');
        $verifier = new BackupVerificationService;
        $mock = \Mockery::mock(BackupVerificationService::class, [null])->makePartial();
        if ($finalVerification) {
            $mock->shouldReceive('assertPrivatePermissions')->andReturnUsing(function (string $disk, string $path, bool $requireProtectedRoot) use ($verifier, $offsite): void {
                if ($disk === 'offsite_test') {
                    $metadata = json_decode($offsite->get($path.'.json'), true, flags: JSON_THROW_ON_ERROR);
                    $this->assertTrue($metadata['offsite_copy']['copied']);
                    chmod($offsite->path($path.'.json'), 0644);
                }
                $verifier->assertPrivatePermissions($disk, $path, $requireProtectedRoot);
            });
        } else {
            $mock->shouldReceive('enforcePrivateLocalPath')->andReturnUsing(function (string $disk, string $path, int $mode) use ($verifier): void {
                if ($disk === 'offsite_test' && $mode === 0600) {
                    throw new RuntimeException('Injected offsite permission enforcement failure');
                }
                $verifier->enforcePrivateLocalPath($disk, $path, $mode);
            });
        }
        $this->instance(BackupVerificationService::class, $mock);
        $command = $this->artisan('backup:database', ['--offsite-disk' => 'offsite_test', '--require-offsite' => $required]);
        $command->doesntExpectOutputToContain('Off-site copy replicated to:');
        if ($required) {
            $command->expectsOutputToContain('Required off-site backup copy')->assertFailed();
        } else {
            $command->expectsOutputToContain('Off-site copy was not completed or failed.')->assertSuccessful();
        }
        $this->assertSame(['backups/database/historical.sql'], $offsite->files('backups/database'));
        $this->assertSame('preserve historical backup', $offsite->get('backups/database/historical.sql'));
        $primary = Storage::disk('backup_permissions');
        $dumps = array_values(array_filter($primary->files('managed'), fn (string $path): bool => str_ends_with($path, '.sqlite')));
        $this->assertCount(1, $dumps);
        $manifest = $verifier->verifyStorageBackup('backup_permissions', $dumps[0], requireProvenance: true);
        $verifier->assertPrivatePermissions('backup_permissions', $dumps[0], requireProtectedRoot: false);
        $this->assertFalse($manifest->offsiteCopy['copied']);
        $this->assertFalse($manifest->offsiteCopy['sha256_verified']);
        Storage::forgetDisk('offsite_test');
    }

    public function test_non_local_offsite_never_uses_posix_paths_or_permission_helpers(): void
    {
        $objects = [];
        $adapter = \Mockery::mock(\League\Flysystem\FilesystemAdapter::class);
        $storage = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $storage->shouldReceive('getAdapter')->andReturn($adapter);
        $storage->shouldNotReceive('path');
        $storage->shouldNotReceive('setVisibility');
        $storage->shouldReceive('put')->andReturnUsing(function (string $path, mixed $content, array $options) use (&$objects): bool {
            $this->assertSame('private', $options['visibility']);
            $objects[$path] = is_resource($content) ? stream_get_contents($content) : $content;

            return true;
        });
        $storage->shouldReceive('size')->andReturnUsing(function (string $path) use (&$objects): int {
            return strlen($objects[$path]);
        });
        $storage->shouldReceive('readStream')->andReturnUsing(function (string $path) use (&$objects) {
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $objects[$path]);
            rewind($stream);

            return $stream;
        });
        Storage::set('remote_offsite_test', $storage);
        $verifier = \Mockery::mock(BackupVerificationService::class, [null])->makePartial();
        $verifier->shouldNotReceive('preparePrivateLocalDirectory');
        $verifier->shouldNotReceive('enforcePrivateLocalPath');
        $verifier->shouldNotReceive('assertPrivatePermissions');
        $file = $this->root.'/fixture.sqlite';
        $manifest = BackupManifest::fromArray([
            'backup_id' => 'remote-test', 'created_at' => now('UTC')->toIso8601String(),
            'application_environment' => 'testing', 'application_git_sha' => str_repeat('a', 40),
            'database_engine' => 'sqlite', 'database_name' => 'isolated-fixture',
            'backup_filename' => 'fixture.sqlite', 'backup_format' => 'sqlite',
            'backup_size_bytes' => filesize($file), 'sha256' => hash_file('sha256', $file),
            'purpose' => 'manual', 'verification_status' => 'verified',
            'verified_at' => now('UTC')->toIso8601String(),
        ]);
        $method = new \ReflectionMethod(BackupDatabaseService::class, 'replicateToOffsite');
        $result = $method->invoke(new BackupDatabaseService($verifier), $file, 'fixture.sqlite', 'remote_offsite_test', 'managed', $manifest->sha256, $manifest, true);
        $this->assertTrue($result['copied']);
        $this->assertTrue($result['sha256_verified']);
        $this->assertCount(3, $objects);
        $final = json_decode($objects['managed/fixture.sqlite.json'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($result, $final['offsite_copy']);
        Storage::forgetDisk('remote_offsite_test');
    }

    public static function incorrectOwnerOnlyModes(): array
    {
        return [
            'executable dump' => ['', 0700],
            'read-only checksum' => ['.sha256', 0400],
            'executable final manifest' => ['.json', 0700],
            'read-only directory' => [null, 0500],
        ];
    }

    #[DataProvider('incorrectOwnerOnlyModes')]
    public function test_independent_verifier_rejects_incorrect_owner_only_modes(?string $suffix, int $mode): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Exact POSIX modes require Linux.');
        }
        $this->artisan('backup:database')->assertSuccessful();
        $storage = Storage::disk('backup_permissions');
        $dump = array_values(array_filter($storage->files('managed'), fn (string $path): bool => str_ends_with($path, '.sqlite')))[0];
        $absolute = $storage->path($suffix === null ? 'managed' : $dump.$suffix);
        try {
            chmod($absolute, $mode);
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('permissions are not private');
            (new BackupVerificationService)->assertPrivatePermissions('backup_permissions', $dump, requireProtectedRoot: false);
        } finally {
            chmod($absolute, $suffix === null ? 0700 : 0600);
        }
    }
}
