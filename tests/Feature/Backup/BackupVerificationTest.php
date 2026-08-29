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

        $manifestJson = json_encode([
            'backup_id' => 'test-1',
            'created_at' => now('UTC')->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $sha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/test.sqlite', $content);
        Storage::disk('local')->put('backups/test.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/test.sqlite.sha256', "{$sha}  test.sqlite\n");

        $this->artisan('backup:verify', [
            'path' => 'backups/test.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Backup verified successfully')
            ->assertSuccessful();

        File::delete($dbPath);
    }

    public function test_verify_rejects_public_disk(): void
    {
        Storage::fake('public');

        $this->artisan('backup:verify', [
            'path' => 'backups/test.sqlite',
            '--disk' => 'public',
        ])
            ->expectsOutputToContain('Public filesystem disk [public] cannot be used')
            ->assertFailed();
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

        $content = File::get($dbPath);
        $manifestJson = json_encode([
            'backup_id' => 'test-mismatch',
            'created_at' => now('UTC')->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => 'expected_good_hash',
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/test.sqlite', $content);
        Storage::disk('local')->put('backups/test.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/test.sqlite.sha256', "bad_checksum_hash  test.sqlite\n");

        $this->artisan('backup:verify', [
            'path' => 'backups/test.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Storage SHA-256 mismatch')
            ->assertFailed();

        File::delete($dbPath);
    }

    public function test_verify_fails_on_corrupted_sqlite_file(): void
    {
        Storage::fake('local');
        $corruptData = 'NOT A SQLITE DATABASE HEADER STRING';
        $sha = hash('sha256', $corruptData);
        $manifestJson = json_encode([
            'backup_id' => 'test-corrupt',
            'created_at' => now('UTC')->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $sha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/corrupted.sqlite', $corruptData);
        Storage::disk('local')->put('backups/corrupted.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/corrupted.sqlite.sha256', "{$sha}  corrupted.sqlite\n");

        $this->artisan('backup:verify', [
            'path' => 'backups/corrupted.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('SQLite backup verification failed')
            ->assertFailed();
    }
}
