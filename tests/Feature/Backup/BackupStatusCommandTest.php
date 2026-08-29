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

        Storage::disk('local')->put('backups/database/fresh.sqlite', $content);
        Storage::disk('local')->put('backups/database/fresh.sqlite.sha256', "{$sha}  fresh.sqlite\n");
        touch(Storage::disk('local')->path('backups/database/fresh.sqlite'), now()->subHours(2)->getTimestamp());

        $this->artisan('backup:status', [
            '--max-age' => 26,
        ])
            ->expectsOutputToContain('is verified and healthy')
            ->assertSuccessful();

        File::delete($dbPath);
    }

    public function test_status_returns_failure_when_backup_is_stale(): void
    {
        Storage::fake('local');

        $dbPath = storage_path('framework/test_status_stale_'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($dbPath));

        $sqlite = new SQLite3($dbPath);
        $sqlite->exec('CREATE TABLE t (id INT)');
        $sqlite->close();

        $content = File::get($dbPath);
        $sha = hash('sha256', $content);

        Storage::disk('local')->put('backups/database/old.sqlite', $content);
        Storage::disk('local')->put('backups/database/old.sqlite.sha256', "{$sha}  old.sqlite\n");
        touch(Storage::disk('local')->path('backups/database/old.sqlite'), now()->subHours(30)->getTimestamp());

        $this->artisan('backup:status', [
            '--max-age' => 24,
        ])
            ->expectsOutputToContain('is stale')
            ->assertFailed();

        File::delete($dbPath);
    }

    public function test_status_returns_failure_when_no_backups_exist(): void
    {
        Storage::fake('local');

        $this->artisan('backup:status')
            ->expectsOutputToContain('No backup files found')
            ->assertFailed();
    }
}
