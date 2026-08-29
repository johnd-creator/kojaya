<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRetentionService;
use Illuminate\Console\Command;
use Throwable;

class PruneDatabaseBackupsCommand extends Command
{
    protected $signature = 'backup:prune
        {--disk= : Filesystem disk containing backups}
        {--directory= : Directory inside the disk}
        {--days= : Retention threshold in days}
        {--keep= : Minimum number of recent backups to keep}
        {--dry-run : Simulate pruning without deleting files}
        {--execute : Perform actual deletion}';

    protected $description = 'Prune old database backups according to retention policy (defaults to dry-run)';

    public function handle(BackupRetentionService $retentionService): int
    {
        $disk = (string) ($this->option('disk') ?: config('operations.backup.disk', 'local'));
        $directory = trim((string) ($this->option('directory') ?: config('operations.backup.directory', 'backups/database')), '/\\');
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;
        $keep = $this->option('keep') !== null ? (int) $this->option('keep') : null;

        // Default to dry-run unless --execute is explicitly supplied and --dry-run is not forced
        $isDryRun = ! $this->option('execute') || (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('[DRY-RUN MODE] Evaluating backups for pruning without deleting files. Use --execute to delete.');
        }

        try {
            $result = $retentionService->prune(
                disk: $disk,
                directory: $directory,
                retentionDays: $days,
                minKeep: $keep,
                dryRun: $isDryRun,
            );

            $this->line("Disk:      {$disk}");
            $this->line("Directory: {$directory}");
            $this->line('Retained:  '.count($result['retained_files']).' backup(s)');

            if (empty($result['pruned_files'])) {
                $this->info('No expired backup artifacts found to prune.');
            } else {
                $action = $isDryRun ? 'Would prune' : 'Pruned';
                $this->info("{$action} {$result['pruned_count']} artifact(s):");
                foreach ($result['pruned_files'] as $file) {
                    $this->line("  - {$file}");
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Backup pruning failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
