<?php

namespace Tests\Feature\Backup;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SQLite3;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDbPath = storage_path('framework/testing_backup_cmd_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($this->tempDbPath));
        if (File::exists($this->tempDbPath)) {
            File::delete($this->tempDbPath);
        }

        $sqlite = new SQLite3($this->tempDbPath);
        $sqlite->exec('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $sqlite->exec("INSERT INTO test_users (name) VALUES ('Test User 1')");
        $sqlite->exec("INSERT INTO test_users (name) VALUES ('Test User 2')");
        $sqlite->close();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $this->tempDbPath);
        Config::set('operations.backup.disk', 'local');
        Config::set('operations.backup.directory', 'backups/database');
        Config::set('operations.backup.enabled', true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempDbPath)) {
            File::delete($this->tempDbPath);
        }

        parent::tearDown();
    }

    public function test_backup_creates_dump_manifest_and_checksum(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database', [
            '--purpose' => 'pre-deploy',
        ])->assertSuccessful();

        $files = Storage::disk('local')->files('backups/database');
        $this->assertCount(3, $files);

        $dumpFiles = array_values(array_filter($files, fn (string $f): bool => str_ends_with($f, '.sqlite')));
        $this->assertCount(1, $dumpFiles);
        $dumpPath = $dumpFiles[0];

        $this->assertTrue(Storage::disk('local')->exists($dumpPath.'.json'));
        $this->assertTrue(Storage::disk('local')->exists($dumpPath.'.sha256'));

        $manifestJson = Storage::disk('local')->get($dumpPath.'.json');
        $manifest = json_decode($manifestJson, true);

        $this->assertIsArray($manifest);
        $this->assertSame(1, $manifest['schema_version']);
        $this->assertSame('pre-deploy', $manifest['purpose']);
        $this->assertSame('verified', $manifest['verification_status']);
        $this->assertSame('sqlite', $manifest['database_engine']);
        $this->assertNotEmpty($manifest['sha256']);
        $this->assertNotEmpty($manifest['application_git_sha']);
        $this->assertNotEmpty($manifest['application_environment']);

        // Checksum verification in .sha256
        $shaContent = Storage::disk('local')->get($dumpPath.'.sha256');
        $this->assertStringStartsWith($manifest['sha256'], $shaContent);
    }

    public function test_manifest_contains_no_secrets_passwords_or_app_key(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database')->assertSuccessful();

        $files = Storage::disk('local')->files('backups/database');
        $jsonFiles = array_values(array_filter($files, fn (string $f): bool => str_ends_with($f, '.json')));
        $this->assertCount(1, $jsonFiles);

        $manifestJson = Storage::disk('local')->get($jsonFiles[0]);

        $this->assertStringNotContainsString('password', strtolower($manifestJson));
        $this->assertStringNotContainsString('app_key', strtolower($manifestJson));
        $this->assertStringNotContainsString('secret', strtolower($manifestJson));
        $this->assertStringNotContainsString('midtrans', strtolower($manifestJson));
        $this->assertStringNotContainsString('token', strtolower($manifestJson));
    }

    public function test_backup_rejects_public_primary_disk(): void
    {
        Storage::fake('public');

        $this->artisan('backup:database', [
            '--disk' => 'public',
        ])
            ->expectsOutputToContain('Public filesystem disk [public] cannot be used')
            ->assertFailed();

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_backup_rejects_public_offsite_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->artisan('backup:database', [
            '--offsite-disk' => 'public',
        ])
            ->expectsOutputToContain('Public filesystem disk [public] cannot be used')
            ->assertFailed();
    }

    public function test_backup_rejects_disk_with_public_visibility(): void
    {
        Storage::fake('local');
        Config::set('filesystems.disks.custom_public_disk', [
            'driver' => 'local',
            'root' => storage_path('app/custom'),
            'visibility' => 'public',
        ]);

        $this->artisan('backup:database', [
            '--disk' => 'custom_public_disk',
        ])
            ->expectsOutputToContain('has public visibility and cannot be used')
            ->assertFailed();
    }

    public function test_config_require_offsite_is_honored_without_cli_flag(): void
    {
        Storage::fake('local');
        Config::set('operations.backup.offsite_enabled', true);
        Config::set('operations.backup.offsite_disk', 'non_existent_disk');
        Config::set('operations.backup.require_offsite', true);

        // Command invoked WITHOUT --require-offsite flag must still fail closed because config is true
        $this->artisan('backup:database')
            ->expectsOutputToContain('Database backup failed')
            ->assertFailed();
    }

    public function test_backup_fails_closed_in_production_environment_with_non_postgres_driver(): void
    {
        Storage::fake('local');
        $this->app['env'] = 'production';

        $this->artisan('backup:database')
            ->expectsOutputToContain('Database backup in [production] requires PostgreSQL driver')
            ->assertFailed();
    }

    public function test_backup_fails_closed_in_staging_environment_with_non_postgres_driver(): void
    {
        Storage::fake('local');
        $this->app['env'] = 'staging';

        $this->artisan('backup:database')
            ->expectsOutputToContain('Database backup in [staging] requires PostgreSQL driver')
            ->assertFailed();
    }

    public function test_backup_rejects_unsafe_directory_path_traversal(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database', [
            '--directory' => '../escaped_dir',
        ])
            ->expectsOutputToContain('Unsafe backup directory path')
            ->assertFailed();
    }

    public function test_backup_rejects_public_storage_directory(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database', [
            '--directory' => 'public/backups',
        ])
            ->expectsOutputToContain('Backups cannot be stored in or pruned from public directory')
            ->assertFailed();
    }

    public function test_offsite_backup_replicates_to_secondary_disk_and_verifies_sha256(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        $this->artisan('backup:database', [
            '--offsite-disk' => 's3',
            '--offsite-directory' => 'offsite-backups',
        ])->assertSuccessful();

        $localFiles = Storage::disk('local')->files('backups/database');
        $offsiteFiles = Storage::disk('s3')->files('offsite-backups');

        $this->assertCount(3, $localFiles);
        $this->assertCount(3, $offsiteFiles);

        $offsiteJson = array_values(array_filter($offsiteFiles, fn (string $f): bool => str_ends_with($f, '.json')))[0];
        $manifest = json_decode(Storage::disk('s3')->get($offsiteJson), true);

        $this->assertTrue($manifest['offsite_copy']['enabled']);
        $this->assertSame('s3', $manifest['offsite_copy']['disk']);
        $this->assertTrue($manifest['offsite_copy']['copied']);
        $this->assertTrue($manifest['offsite_copy']['sha256_verified']);
    }

    public function test_source_database_remains_read_only_and_untouched(): void
    {
        Storage::fake('local');

        $sqlite = new SQLite3($this->tempDbPath);
        $initialCount = $sqlite->querySingle('SELECT COUNT(*) FROM test_users');
        $sqlite->close();

        $this->assertSame(2, $initialCount);

        $this->artisan('backup:database')->assertSuccessful();

        $sqlite = new SQLite3($this->tempDbPath);
        $afterCount = $sqlite->querySingle('SELECT COUNT(*) FROM test_users');
        $sqlite->close();

        $this->assertSame($initialCount, $afterCount);
    }

    public function test_pre_deploy_backup_aborts_in_production_if_backup_disabled(): void
    {
        Storage::fake('local');
        $this->app['env'] = 'production';
        Config::set('operations.backup.enabled', false);

        $this->artisan('backup:database', [
            '--purpose' => 'pre-deploy',
        ])
            ->expectsOutputToContain('Database backup is disabled via BACKUP_ENABLED=false, but pre-deploy backup is mandatory')
            ->assertFailed();
    }

    public function test_backup_rejects_disk_with_root_in_public_path(): void
    {
        Storage::fake('local');
        Config::set('filesystems.disks.custom_root_public', [
            'driver' => 'local',
            'root' => public_path('uploads'),
        ]);

        $this->artisan('backup:database', [
            '--disk' => 'custom_root_public',
        ])
            ->expectsOutputToContain('root is located in a public directory')
            ->assertFailed();
    }

    public function test_backup_rejects_disk_with_root_in_storage_app_public(): void
    {
        Storage::fake('local');
        Config::set('filesystems.disks.custom_root_storage_public', [
            'driver' => 'local',
            'root' => storage_path('app/public/backups'),
        ]);

        $this->artisan('backup:database', [
            '--disk' => 'custom_root_storage_public',
        ])
            ->expectsOutputToContain('root is located in a public directory')
            ->assertFailed();
    }

    public function test_offsite_sha256_mismatch_fails_offsite_verification(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        Config::set('operations.backup.require_offsite', true);

        $this->partialMock(\App\Services\Backup\BackupVerificationService::class, function ($mock) {
            $mock->shouldReceive('calculateStorageStreamSha256')
                ->andReturnUsing(function ($disk, $path) {
                    if ($disk === 'local') {
                        return hash('sha256', Storage::disk('local')->get($path));
                    }

                    return 'corrupted_mismatched_remote_hash';
                });
        });

        $this->artisan('backup:database', [
            '--offsite-disk' => 's3',
        ])
            ->expectsOutputToContain('Database backup failed')
            ->assertFailed();
    }
}
