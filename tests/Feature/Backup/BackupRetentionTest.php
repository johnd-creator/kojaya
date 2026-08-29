<?php

namespace Tests\Feature\Backup;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    public function test_retention_defaults_to_dry_run_and_does_not_delete_files(): void
    {
        Storage::fake('local');

        $oldCreatedAt = now('UTC')->subDays(30)->toIso8601String();
        $manifestJson = json_encode([
            'backup_id' => 'old-1',
            'created_at' => $oldCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => '4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82',
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/database/old-1.dump', 'data-old');
        Storage::disk('local')->put('backups/database/old-1.dump.json', $manifestJson);
        Storage::disk('local')->put('backups/database/old-1.dump.sha256', "4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82  old-1.dump\n");

        $freshCreatedAt = now('UTC')->toIso8601String();
        $freshManifest = json_encode([
            'backup_id' => 'fresh-1',
            'created_at' => $freshCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => '5a88e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82',
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/fresh-1.dump', 'data-fresh');
        Storage::disk('local')->put('backups/database/fresh-1.dump.json', $freshManifest);
        Storage::disk('local')->put('backups/database/fresh-1.dump.sha256', "5a88e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82  fresh-1.dump\n");

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

    public function test_retention_preserves_old_valid_backup_when_new_backup_is_corrupt(): void
    {
        Storage::fake('local');

        // 1. Old valid backup (created 30 days ago)
        $oldCreatedAt = now('UTC')->subDays(30)->toIso8601String();
        $oldSha = '4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82';
        $oldManifest = json_encode([
            'backup_id' => 'valid-old-1',
            'created_at' => $oldCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => $oldSha,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/valid-old-1.dump', 'valid-old-data');
        Storage::disk('local')->put('backups/database/valid-old-1.dump.json', $oldManifest);
        Storage::disk('local')->put('backups/database/valid-old-1.dump.sha256', "{$oldSha}  valid-old-1.dump\n");

        // 2. Newest corrupt backup (created today, missing manifest or corrupt)
        Storage::disk('local')->put('backups/database/corrupt-new-1.dump', 'corrupted-data');
        // No valid manifest or checksum for corrupt-new-1

        // 3. Execute pruning with 14 day cutoff and min_keep=1
        $this->artisan('backup:prune', [
            '--days' => 14,
            '--keep' => 1,
            '--execute' => true,
        ])->assertSuccessful();

        // 4. Invariant: The ONLY valid backup MUST be preserved, never deleted
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump.json'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump.sha256'));
    }

    public function test_retention_rejects_public_disk(): void
    {
        Storage::fake('public');

        $this->artisan('backup:prune', [
            '--disk' => 'public',
        ])
            ->expectsOutputToContain('Public filesystem disk [public] cannot be used')
            ->assertFailed();
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
