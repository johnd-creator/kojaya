<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class BackupRetentionService
{
    /**
     * Supported primary backup extensions.
     */
    private const BACKUP_EXTENSIONS = ['dump', 'sqlite', 'sqlite3', 'db', 'sql'];

    /**
     * Prune expired backups according to retention policy.
     *
     * @return array{
     *     pruned_count: int,
     *     pruned_files: array<int, string>,
     *     retained_files: array<int, string>,
     *     dry_run: bool
     * }
     */
    public function prune(
        string $disk,
        string $directory,
        ?int $retentionDays = null,
        ?int $minKeep = null,
        bool $dryRun = true,
    ): array {
        $this->validateDirectorySafety($directory);

        $storage = Storage::disk($disk);
        $retentionDays = max(1, $retentionDays ?? (int) config('operations.backup.retention_days', 14));
        $minKeep = max(1, $minKeep ?? (int) config('operations.backup.min_keep', 1));
        $cutoffTimestamp = now()->subDays($retentionDays)->getTimestamp();

        $allFiles = $storage->files($directory);

        // Group by primary backup base
        $backupFiles = [];
        foreach ($allFiles as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, self::BACKUP_EXTENSIONS, true)) {
                $backupFiles[] = [
                    'path' => $file,
                    'timestamp' => (int) $storage->lastModified($file),
                ];
            }
        }

        // Sort newest first
        usort($backupFiles, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        $totalBackups = count($backupFiles);
        $retainedFiles = [];
        $filesToPrune = [];

        foreach ($backupFiles as $index => $item) {
            $path = $item['path'];
            $timestamp = $item['timestamp'];

            // Always keep at least $minKeep backups
            if ($index < $minKeep) {
                $retainedFiles[] = $path;

                continue;
            }

            // If newer than cutoff, keep
            if ($timestamp >= $cutoffTimestamp) {
                $retainedFiles[] = $path;

                continue;
            }

            // Otherwise, candidate for pruning
            $filesToPrune[] = $path;
            // Also include companion manifest and checksum if they exist
            if ($storage->exists($path.'.json')) {
                $filesToPrune[] = $path.'.json';
            }
            if ($storage->exists($path.'.sha256')) {
                $filesToPrune[] = $path.'.sha256';
            }
        }

        $prunedCount = 0;
        if (! $dryRun) {
            foreach ($filesToPrune as $fileToDelete) {
                $storage->delete($fileToDelete);
                $prunedCount++;
            }
        } else {
            $prunedCount = count($filesToPrune);
        }

        return [
            'pruned_count' => $prunedCount,
            'pruned_files' => $filesToPrune,
            'retained_files' => $retainedFiles,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Validate that the directory path is safe from path traversal and not root.
     */
    public function validateDirectorySafety(string $directory): void
    {
        $normalized = trim($directory, '/\\');

        if ($normalized === '' || $normalized === '.' || str_contains($directory, '..')) {
            throw new InvalidArgumentException("Unsafe backup directory path [{$directory}]. Directory must be a relative, non-root namespace.");
        }

        // Reject public storage directory
        if (str_starts_with($normalized, 'public') || str_starts_with($normalized, 'app/public')) {
            throw new InvalidArgumentException("Backups cannot be stored in or pruned from public directory [{$directory}].");
        }
    }
}
