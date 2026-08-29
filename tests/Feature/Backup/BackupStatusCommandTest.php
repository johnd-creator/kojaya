<?php

namespace Tests\Feature\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SQLite3;
use Tests\TestCase;

class BackupStatusCommandTest extends TestCase
{
    public function test_status_returns_success_when_backup_is_fresh_and_healthy(): void
    {
        Storage::fake('local');

        $dbPath = storage_path('framework/test_status_fresh_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        $content = File::get($dbPath);
        $sha = hash('sha256', $content);
        $createdAt = now('UTC')->subHours(2)->toIso8601String();

        $manifestJson = json_encode([
            'backup_id' => 'fresh-1',
            'created_at' => $createdAt,
            'database_engine' => 'sqlite',
            'sha256' => $sha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/database/fresh.sqlite', $content);
        Storage::disk('local')->put('backups/database/fresh.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/database/fresh.sqlite.sha256', "{$sha}  fresh.sqlite\n");
        touch(Storage::disk('local')->path('backups/database/fresh.sqlite'), now()->subHours(2)->getTimestamp());

        $this->artisan('backup:status', [
            '--max-age' => 26,
        ])
            ->expectsOutputToContain('is verified and healthy')
            ->assertSuccessful();

        File::delete($dbPath);
    }

    public function test_status_uses_manifest_created_at_and_reports_stale_even_if_filesystem_mtime_is_fresh(): void
    {
        Storage::fake('local');

        $dbPath = storage_path('framework/test_status_stale_manifest_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        $content = File::get($dbPath);
        $sha = hash('sha256', $content);
        $oldCreatedAt = now('UTC')->subHours(30)->toIso8601String();

        $manifestJson = json_encode([
            'backup_id' => 'stale-manifest-1',
            'created_at' => $oldCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => $sha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/database/old_manifest.sqlite', $content);
        Storage::disk('local')->put('backups/database/old_manifest.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/database/old_manifest.sqlite.sha256', "{$sha}  old_manifest.sqlite\n");

        // Filesystem mtime is set to current (0 hours old) to simulate fresh touch/copy
        touch(Storage::disk('local')->path('backups/database/old_manifest.sqlite'), now()->getTimestamp());

        $this->artisan('backup:status', [
            '--max-age' => 26,
        ])
            ->expectsOutputToContain('is stale')
            ->assertFailed();

        File::delete($dbPath);
    }

    public function test_status_fails_if_cryptographic_manifest_or_checksum_is_missing(): void
    {
        Storage::fake('local');

        $dbPath = storage_path('framework/test_status_missing_meta_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        $content = File::get($dbPath);
        Storage::disk('local')->put('backups/database/unmanaged.sqlite', $content);
        // Do NOT put .json or .sha256 metadata

        $this->artisan('backup:status')
            ->expectsOutputToContain('missing required cryptographic provenance')
            ->assertFailed();

        File::delete($dbPath);
    }

    public function test_status_rejects_public_disk(): void
    {
        Storage::fake('public');

        $this->artisan('backup:status', [
            '--disk' => 'public',
        ])
            ->expectsOutputToContain('Public filesystem disk [public] cannot be used')
            ->assertFailed();
    }

    public function test_status_returns_failure_when_no_backups_exist(): void
    {
        Storage::fake('local');

        $this->artisan('backup:status')
            ->expectsOutputToContain('No backup files found')
            ->assertFailed();
    }

    public function test_status_fails_corrupt_when_manifest_created_at_is_invalid_even_if_mtime_fresh(): void
    {
        Storage::fake('local');

        $dbPath = storage_path('framework/test_status_invalid_ts_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        $content = File::get($dbPath);
        $sha = hash('sha256', $content);

        // Manifest has unparseable/invalid timestamp
        $manifestJson = json_encode([
            'backup_id' => 'invalid-ts-1',
            'created_at' => 'NOT_A_VALID_DATE_STRING',
            'database_engine' => 'sqlite',
            'sha256' => $sha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/database/invalid_ts.sqlite', $content);
        Storage::disk('local')->put('backups/database/invalid_ts.sqlite.json', $manifestJson);
        Storage::disk('local')->put('backups/database/invalid_ts.sqlite.sha256', "{$sha}  invalid_ts.sqlite\n");

        // Filesystem mtime is fresh
        touch(Storage::disk('local')->path('backups/database/invalid_ts.sqlite'), now()->getTimestamp());

        $this->artisan('backup:status')
            ->expectsOutputToContain('unparseable created_at timestamp')
            ->assertFailed();

        File::delete($dbPath);
    }
}
