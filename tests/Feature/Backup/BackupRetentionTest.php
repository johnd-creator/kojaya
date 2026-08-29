<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupRetentionService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    public function test_retention_defaults_to_dry_run_and_does_not_delete_files(): void
    {
        Storage::fake('local');

        $oldData = 'data-old-bytes';
        $oldSha = hash('sha256', $oldData);
        $oldCreatedAt = now('UTC')->subDays(30)->toIso8601String();
        $manifestJson = json_encode([
            'backup_id' => 'old-1',
            'created_at' => $oldCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => $oldSha,
            'verification_status' => 'verified',
        ]);

        Storage::disk('local')->put('backups/database/old-1.dump', $oldData);
        Storage::disk('local')->put('backups/database/old-1.dump.json', $manifestJson);
        Storage::disk('local')->put('backups/database/old-1.dump.sha256', "{$oldSha}  old-1.dump\n");

        $freshData = 'data-fresh-bytes';
        $freshSha = hash('sha256', $freshData);
        $freshCreatedAt = now('UTC')->toIso8601String();
        $freshManifest = json_encode([
            'backup_id' => 'fresh-1',
            'created_at' => $freshCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => $freshSha,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/fresh-1.dump', $freshData);
        Storage::disk('local')->put('backups/database/fresh-1.dump.json', $freshManifest);
        Storage::disk('local')->put('backups/database/fresh-1.dump.sha256', "{$freshSha}  fresh-1.dump\n");

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

    public function test_retention_preserves_old_valid_backup_and_never_deletes_corrupt_backup(): void
    {
        Storage::fake('local');

        // 1. Old valid backup (created 30 days ago)
        $oldData = 'valid-old-data-content';
        $oldSha = hash('sha256', $oldData);
        $oldCreatedAt = now('UTC')->subDays(30)->toIso8601String();
        $oldManifest = json_encode([
            'backup_id' => 'valid-old-1',
            'created_at' => $oldCreatedAt,
            'database_engine' => 'sqlite',
            'sha256' => $oldSha,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/valid-old-1.dump', $oldData);
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

        // 4. Invariant: The valid backup is preserved by min_keep
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump.json'));
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-old-1.dump.sha256'));

        // 5. Invariant: Corrupt backup is NEVER automatically deleted (must be manually reviewed)
        $this->assertTrue(Storage::disk('local')->exists('backups/database/corrupt-new-1.dump'));
    }

    public function test_retention_prunes_only_verified_excess_backups(): void
    {
        Storage::fake('local');

        // 1. Old verified backup 1 (30 days ago) - EXPIRED, EXCESS
        $data1 = 'valid-dump-data-1';
        $sha1 = hash('sha256', $data1);
        $manifest1 = json_encode([
            'backup_id' => 'valid-1',
            'created_at' => now('UTC')->subDays(30)->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $sha1,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/valid-1.dump', $data1);
        Storage::disk('local')->put('backups/database/valid-1.dump.json', $manifest1);
        Storage::disk('local')->put('backups/database/valid-1.dump.sha256', "{$sha1}  valid-1.dump\n");

        // 2. Old verified backup 2 (25 days ago) - EXPIRED, EXCESS
        $data2 = 'valid-dump-data-2';
        $sha2 = hash('sha256', $data2);
        $manifest2 = json_encode([
            'backup_id' => 'valid-2',
            'created_at' => now('UTC')->subDays(25)->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $sha2,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/valid-2.dump', $data2);
        Storage::disk('local')->put('backups/database/valid-2.dump.json', $manifest2);
        Storage::disk('local')->put('backups/database/valid-2.dump.sha256', "{$sha2}  valid-2.dump\n");

        // 3. Old verified backup 3 (20 days ago) - EXPIRED, PROTECTED by min_keep=1
        $data3 = 'valid-dump-data-3';
        $sha3 = hash('sha256', $data3);
        $manifest3 = json_encode([
            'backup_id' => 'valid-3',
            'created_at' => now('UTC')->subDays(20)->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $sha3,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/valid-3.dump', $data3);
        Storage::disk('local')->put('backups/database/valid-3.dump.json', $manifest3);
        Storage::disk('local')->put('backups/database/valid-3.dump.sha256', "{$sha3}  valid-3.dump\n");

        // 4. Old corrupt backup (40 days ago) - INVALID, NEVER auto-deleted
        Storage::disk('local')->put('backups/database/corrupt-old.dump', 'corrupt-old-data');

        // 5. Execute pruning with 14 day cutoff and min_keep=1
        $this->artisan('backup:prune', [
            '--days' => 14,
            '--keep' => 1,
            '--execute' => true,
        ])->assertSuccessful();

        // 6. Valid backup 3 (newest valid) protected by min_keep=1
        $this->assertTrue(Storage::disk('local')->exists('backups/database/valid-3.dump'));

        // 7. Corrupt backup is NOT deleted
        $this->assertTrue(Storage::disk('local')->exists('backups/database/corrupt-old.dump'));

        // 8. Excess expired valid backups 1 and 2 are pruned
        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-1.dump'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-1.dump.json'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-1.dump.sha256'));

        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-2.dump'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-2.dump.json'));
        $this->assertFalse(Storage::disk('local')->exists('backups/database/valid-2.dump.sha256'));
    }

    public function test_retention_validity_requires_actual_dump_sha_match(): void
    {
        Storage::fake('local');

        // Actual dump file has content 'real-content'
        $realData = 'real-content-bytes';
        $tamperedSha = hash('sha256', 'tampered-or-different-content');

        // Manifest and checksum agree on tamperedSha, but actual stored bytes hash to realSha
        $manifestJson = json_encode([
            'backup_id' => 'tampered-1',
            'created_at' => now('UTC')->subDays(30)->toIso8601String(),
            'database_engine' => 'sqlite',
            'sha256' => $tamperedSha,
            'verification_status' => 'verified',
        ]);
        Storage::disk('local')->put('backups/database/tampered.dump', $realData);
        Storage::disk('local')->put('backups/database/tampered.dump.json', $manifestJson);
        Storage::disk('local')->put('backups/database/tampered.dump.sha256', "{$tamperedSha}  tampered.dump\n");

        $retentionService = app(BackupRetentionService::class);
        $result = $retentionService->prune('local', 'backups/database', retentionDays: 14, minKeep: 1, dryRun: false);

        // Tampered backup is invalid because actual byte SHA != manifest SHA -> must NEVER be auto-deleted
        $this->assertTrue(Storage::disk('local')->exists('backups/database/tampered.dump'));
        $this->assertContains('backups/database/tampered.dump', $result['manual_review_candidates']);
        $this->assertSame(0, $result['pruned_count']);
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
