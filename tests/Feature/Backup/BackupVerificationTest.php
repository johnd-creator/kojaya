<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupVerificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SQLite3;
use Tests\TestCase;

class BackupVerificationTest extends TestCase
{
    private BackupVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BackupVerificationService;
    }

    public function test_verify_valid_sqlite_backup_succeeds(): void
    {
        Storage::fake('local');
        $dbPath = storage_path('framework/test_verify_valid_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE items (id INTEGER PRIMARY KEY, title TEXT)');
        $sqlite->exec("INSERT INTO items (title) VALUES ('item 1')");
        $sqlite->close();

        $content = File::get($dbPath);
        $sha = hash('sha256', $content);

        Storage::disk('local')->put('backups/test.sqlite', $content);
        Storage::disk('local')->put('backups/test.sqlite.sha256', "{$sha}  test.sqlite\n");

        $this->artisan('backup:verify', [
            'path' => 'backups/test.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Backup verified successfully')
            ->assertSuccessful();

        File::delete($dbPath);
    }

    public function test_verify_fails_when_file_is_missing(): void
    {
        Storage::fake('local');

        $this->artisan('backup:verify', [
            'path' => 'backups/non_existent.dump',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Backup verification failed')
            ->assertFailed();
    }

    public function test_verify_fails_on_zero_byte_empty_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/empty.sqlite', '');

        $this->artisan('backup:verify', [
            'path' => 'backups/empty.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('is empty (0 bytes)')
            ->assertFailed();
    }

    public function test_verify_fails_on_checksum_mismatch(): void
    {
        Storage::fake('local');
        $dbPath = storage_path('framework/test_verify_mismatch_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        Storage::disk('local')->put('backups/test.sqlite', File::get($dbPath));
        Storage::disk('local')->put('backups/test.sqlite.sha256', "bad_checksum_hash  test.sqlite\n");

        $this->artisan('backup:verify', [
            'path' => 'backups/test.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Checksum mismatch')
            ->assertFailed();

        File::delete($dbPath);
    }

    public function test_verify_fails_on_corrupted_sqlite_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/corrupted.sqlite', 'NOT A SQLITE DATABASE HEADER STRING');

        $this->artisan('backup:verify', [
            'path' => 'backups/corrupted.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('SQLite integrity check failed')
            ->assertFailed();
    }
}
