<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupDatabaseService;
use App\Services\Backup\BackupRetentionService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database
        {--disk= : Filesystem disk for backup output}
        {--directory= : Directory inside the backup disk}
        {--purpose=manual : Purpose of the backup (manual, scheduled, pre-deploy, restore-drill)}
        {--offsite-disk= : Off-site filesystem disk for backup copy}
        {--offsite-directory= : Directory on off-site disk}
        {--require-offsite : Fail closed if off-site backup copy fails}
        {--prune : Prune expired backups according to retention policy}';

    protected $description = 'Create a verified database backup with cryptographic manifest, checksum, and optional offsite replication';

    public function handle(BackupDatabaseService $backupService, BackupRetentionService $retentionService): int
    {
        $disk = $this->option('disk') ? (string) $this->option('disk') : null;
        $directory = $this->option('directory') ? (string) $this->option('directory') : null;
        $purpose = (string) ($this->option('purpose') ?: 'manual');
        $offsiteDisk = $this->option('offsite-disk') ? (string) $this->option('offsite-disk') : null;
        $offsiteDirectory = $this->option('offsite-directory') ? (string) $this->option('offsite-directory') : null;
        $requireOffsite = $this->option('require-offsite') ? true : null;

        $this->info("Initiating database backup [purpose: {$purpose}]...");

        try {
            $result = $backupService->backup(
                disk: $disk,
                directory: $directory,
                purpose: $purpose,
                offsiteDisk: $offsiteDisk,
                offsiteDirectory: $offsiteDirectory,
                requireOffsite: $requireOffsite,
            );

            $manifest = $result['manifest'];
            $targetDisk = $result['disk'];
            $targetPath = $result['path'];

            $this->info("Backup successfully created and verified: {$targetDisk}:{$targetPath}");
            $this->line("Backup ID:   {$manifest->backupId}");
            $this->line("Format:      {$manifest->backupFormat}");
            $this->line("Size:        {$manifest->backupSizeBytes} bytes");
            $this->line("SHA-256:     {$manifest->sha256}");
            $this->line("Environment: {$manifest->applicationEnvironment}");
            $this->line("Git SHA:     {$manifest->applicationGitSha}");

            if (! empty($manifest->offsiteCopy['enabled'])) {
                if (! empty($manifest->offsiteCopy['copied'])) {
                    $this->info("Off-site copy replicated to: {$manifest->offsiteCopy['disk']}:{$manifest->offsiteCopy['directory']}");
                } else {
                    $this->warn('Off-site copy was not completed or failed.');
                }
            }

            if ($this->option('prune')) {
                $pruneResult = $retentionService->prune(
                    disk: $targetDisk,
                    directory: dirname($targetPath),
                    dryRun: false,
                );
                $this->info("Pruned {$pruneResult['pruned_count']} expired backup artifact(s).");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Database backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
