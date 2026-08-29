<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupStatusService
{
    public function __construct(
        private readonly BackupVerificationService $verificationService = new BackupVerificationService,
        private readonly BackupRetentionService $retentionService = new BackupRetentionService,
    ) {}

    /**
     * @return array{
     *     status: 'healthy'|'stale'|'missing'|'corrupt',
     *     is_healthy: bool,
     *     latest_backup: ?string,
     *     manifest: ?BackupManifest,
     *     age_hours: ?float,
     *     max_age_hours: int,
     *     message: string
     * }
     */
    public function checkStatus(
        string $disk,
        string $directory,
        ?int $maxAgeHours = null,
    ): array {
        $this->retentionService->validateDirectorySafety($directory);

        $storage = Storage::disk($disk);
        $maxAgeHours = max(1, $maxAgeHours ?? (int) config('operations.backup.max_age_hours', 26));

        $allFiles = $storage->files($directory);
        $backupFiles = [];

        foreach ($allFiles as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['dump', 'sqlite', 'sqlite3', 'db', 'sql'], true)) {
                $backupFiles[] = [
                    'path' => $file,
                    'timestamp' => (int) $storage->lastModified($file),
                ];
            }
        }

        if (empty($backupFiles)) {
            return [
                'status' => 'missing',
                'is_healthy' => false,
                'latest_backup' => null,
                'manifest' => null,
                'age_hours' => null,
                'max_age_hours' => $maxAgeHours,
                'message' => "No backup files found on disk [{$disk}:{$directory}].",
            ];
        }

        usort($backupFiles, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
        $latest = $backupFiles[0];
        $latestPath = $latest['path'];
        $lastModified = $latest['timestamp'];
        $ageSeconds = max(0, time() - $lastModified);
        $ageHours = round($ageSeconds / 3600, 2);

        try {
            $manifest = $this->verificationService->verifyStorageBackup($disk, $latestPath);
        } catch (Throwable $e) {
            return [
                'status' => 'corrupt',
                'is_healthy' => false,
                'latest_backup' => $latestPath,
                'manifest' => null,
                'age_hours' => $ageHours,
                'max_age_hours' => $maxAgeHours,
                'message' => "Latest backup [{$latestPath}] failed verification: {$e->getMessage()}",
            ];
        }

        if ($ageHours > $maxAgeHours) {
            return [
                'status' => 'stale',
                'is_healthy' => false,
                'latest_backup' => $latestPath,
                'manifest' => $manifest,
                'age_hours' => $ageHours,
                'max_age_hours' => $maxAgeHours,
                'message' => "Latest backup [{$latestPath}] is stale ({$ageHours} hours old; SLA is max {$maxAgeHours} hours).",
            ];
        }

        return [
            'status' => 'healthy',
            'is_healthy' => true,
            'latest_backup' => $latestPath,
            'manifest' => $manifest,
            'age_hours' => $ageHours,
            'max_age_hours' => $maxAgeHours,
            'message' => "Latest backup [{$latestPath}] is verified and healthy ({$ageHours} hours old).",
        ];
    }
}
