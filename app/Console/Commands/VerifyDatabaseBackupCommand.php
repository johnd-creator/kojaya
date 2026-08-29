<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupRetentionService;
use App\Services\Backup\BackupVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerifyDatabaseBackupCommand extends Command
{
    protected $signature = 'backup:verify
        {path? : Specific backup path inside the selected disk. Defaults to the latest file in the backup directory}
        {--disk= : Filesystem disk containing the backup}
        {--directory= : Directory inside the backup disk}';

    protected $description = 'Verify the integrity, checksum, and archive contents of a database backup';

    public function __construct(
        private readonly BackupVerificationService $verificationService = new BackupVerificationService,
        private readonly BackupRetentionService $retentionService = new BackupRetentionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('operations.backup.disk', 'local'));
        $directory = trim((string) ($this->option('directory') ?: config('operations.backup.directory', 'backups/database')), '/\\');

        try {
            $this->retentionService->validateDirectorySafety($directory);
        } catch (Throwable $e) {
            $this->error("Directory validation error: {$e->getMessage()}");

            return self::FAILURE;
        }

        $path = (string) ($this->argument('path') ?: $this->latestBackupPath($disk, $directory));

        if ($path === '') {
            $this->error("No backup files found in [{$disk}:{$directory}].");

            return self::FAILURE;
        }

        $this->info("Verifying database backup: {$disk}:{$path}");

        try {
            $manifest = $this->verificationService->verifyStorageBackup($disk, $path);

            $this->info("Backup verified successfully: {$disk}:{$path}");
            $this->line("Backup ID:   {$manifest->backupId}");
            $this->line("Format:      {$manifest->backupFormat}");
            $this->line("Size:        {$manifest->backupSizeBytes} bytes");
            $this->line("SHA-256:     {$manifest->sha256}");
            $this->line("Created:     {$manifest->createdAt}");
            $this->line("Status:      {$manifest->verificationStatus}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Backup verification failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function latestBackupPath(string $disk, string $directory): ?string
    {
        $storage = Storage::disk($disk);
        $files = $storage->files($directory);
        $backups = [];

        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['dump', 'sqlite', 'sqlite3', 'db', 'sql'], true)) {
                $backups[] = [
                    'path' => $file,
                    'timestamp' => (int) $storage->lastModified($file),
                ];
            }
        }

        if (empty($backups)) {
            return null;
        }

        usort($backups, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return $backups[0]['path'];
    }
}
