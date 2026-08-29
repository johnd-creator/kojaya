<?php

namespace Tests\Feature\Backup;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    public function test_retention_defaults_to_dry_run_and_does_not_delete_files(): void
    {
        Storage::fake('local');

        $oldTime = now()->subDays(30)->getTimestamp();
        Storage::disk('local')->put('backups/database/old-1.dump', 'data');
        Storage::disk('local')->put('backups/database/old-1.dump.json', '{}');
        Storage::disk('local')->put('backups/database/old-1.dump.sha256', 'hash');
        touch(Storage::disk('local')->path('backups/database/old-1.dump'), $oldTime);

        $freshTime = now()->getTimestamp();
        Storage::disk('local')->put('backups/database/fresh-1.dump', 'data');
        touch(Storage::disk('local')->path('backups/database/fresh-1.dump'), $freshTime);

        $this->artisan('backup:prune', [
            '--days' => 14,
            '--keep' => 1,
        ])
            ->expectsOutputToContain('[DRY-RUN MODE]')
            ->expectsOutputToContain('Would prune')
            ->assertSuccessful();

        // Files still exist after dry run
        $this->assertTrue(Storage::disk('local')->exists('backups/database/old-1.dump'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/old-1.dump.json'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/old-1.dump.sha256'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/fresh-1.dump'));
    }

    public function test_retention_executes_deletion_when_execute_flag_is_provided(): void
    {
        Storage::fake('local');

        $oldTime = now()->subDays(30)->getTimestamp();
        Storage::disk('local')->put('backups/database/old-1.dump', 'data');
        Storage::disk('local')->put('backups/database/old-1.dump.json', '{}');
        Storage::disk('local')->put('backups/database/old-1.dump.sha256', 'hash');
        touch(Storage::disk('local')->path('backups/database/old-1.dump'), $oldTime);

        $freshTime = now()->getTimestamp();
        Storage::disk('local')->put('backups/database/fresh-1.dump', 'data');
        touch(Storage::disk('local')->path('backups/database/fresh-1.dump'), $freshTime);

        $this->artisan('backup:prune', [
            '--days' => 14,
            '--keep' => 1,
            '--execute' => true,
        ])
            ->expectsOutputToContain('Pruned 3 artifact(s)')
            ->assertSuccessful();

        $this->assertFalse(Storage::disk('local')->exists('backups/database/old-1.dump'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/old-1.dump.json'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/old-1.dump.sha256'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/fresh-1.dump'));
    }

    public function test_retention_protects_latest_backup_even_if_older_than_retention_cutoff(): void
    {
        Storage::fake('local');

        $oldTime = now()->subDays(60)->getTimestamp();
        Storage::disk('local')->put('backups/database/lone-old-backup.dump', 'data');
        touch(Storage::disk('local')->path('backups/database/lone-old-backup.dump'), $oldTime);

        $this->artisan('backup:prune', [
            '--days' => 14,
            '--keep' => 1,
            '--execute' => true,
        ])
            ->expectsOutputToContain('No expired backup artifacts found to prune')
            ->assertSuccessful();

        // The only backup must never be deleted
        $this->assertTrue(Storage::disk('local')->exists('backups/database/lone-old-backup.dump'));
    }

    public function test_retention_rejects_unsafe_directory_path(): void
    {
        Storage::fake('local');

        $this->artisan('backup:prune', [
            '--directory' => '../escaped_path',
        ])
            ->expectsOutputToContain('Unsafe backup directory path')
            ->assertFailed();
    }
}
