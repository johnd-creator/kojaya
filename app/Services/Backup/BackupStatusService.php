<?php

namespace App\Services\Backup;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupStatusService
{
    private readonly BackupVerificationService $verificationService;

    private readonly BackupRetentionService $retentionService;

    public function __construct(
        ?BackupVerificationService $verificationService = null,
        ?BackupRetentionService $retentionService = null,
    ) {
        $this->verificationService = $verificationService ?? app(BackupVerificationService::class);
        $this->retentionService = $retentionService ?? app(BackupRetentionService::class);
    }

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
        $this->retentionService->validateDiskSafety($disk);
        $this->retentionService->validateDirectorySafety($directory);

        $storage = Storage::disk($disk);
        $maxAgeHours = max(1, $maxAgeHours ?? (int) config('operations.backup.max_age_hours', 26));

        $allFiles = $storage->files($directory);
        $backupFiles = [];

        foreach ($allFiles as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['dump', 'sqlite', 'sqlite3', 'db', 'sql'], true)) {
                $manifestPath = $file.'.json';
                $manifestCreatedAt = null;
                if ($storage->exists($manifestPath)) {
                    try {
                        $data = json_decode((string) $storage->get($manifestPath), true);
                        if (is_array($data) && isset($data['created_at'])) {
                            $manifestCreatedAt = Carbon::parse((string) $data['created_at'], 'UTC')->getTimestamp();
                        }
                    } catch (Throwable) {
                        $manifestCreatedAt = null;
                    }
                }

                $timestamp = $manifestCreatedAt ?? (int) $storage->lastModified($file);

                $backupFiles[] = [
                    'path' => $file,
                    'timestamp' => $timestamp,
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

        try {
            // Require strict cryptographic provenance
            $manifest = $this->verificationService->verifyStorageBackup($disk, $latestPath, requireProvenance: true);
        } catch (Throwable $e) {
            return [
                'status' => 'corrupt',
                'is_healthy' => false,
                'latest_backup' => $latestPath,
                'manifest' => null,
                'age_hours' => null,
                'max_age_hours' => $maxAgeHours,
                'message' => "Latest backup [{$latestPath}] failed verification: {$e->getMessage()}",
            ];
        }

        // Authoritative backup creation time from manifest (strictly required)
        if (trim($manifest->createdAt) === '') {
            return [
                'status' => 'corrupt',
                'is_healthy' => false,
                'latest_backup' => $latestPath,
                'manifest' => $manifest,
                'age_hours' => null,
                'max_age_hours' => $maxAgeHours,
                'message' => "Latest backup [{$latestPath}] has missing created_at timestamp in manifest.",
            ];
        }

        try {
            $createdAt = Carbon::parse($manifest->createdAt, 'UTC');
            $ageSeconds = max(0, now('UTC')->getTimestamp() - $createdAt->getTimestamp());
            $ageHours = round($ageSeconds / 3600, 2);
        } catch (Throwable) {
            return [
                'status' => 'corrupt',
                'is_healthy' => false,
                'latest_backup' => $latestPath,
                'manifest' => $manifest,
                'age_hours' => null,
                'max_age_hours' => $maxAgeHours,
                'message' => "Latest backup [{$latestPath}] has unparseable created_at timestamp in manifest.",
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
