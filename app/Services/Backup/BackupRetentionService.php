<?php

namespace App\Services\Backup;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

class BackupRetentionService
{
    /**
     * Supported primary backup extensions.
     */
    private const BACKUP_EXTENSIONS = ['dump', 'sqlite', 'sqlite3', 'db', 'sql'];

    public function __construct(
        private readonly ?BackupVerificationService $verificationService = null,
    ) {}

    /**
     * Prune expired backups according to retention policy while guaranteeing
     * that at least $minKeep verified valid backups remain.
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
        $this->validateDiskSafety($disk);
        $this->validateDirectorySafety($directory);

        $storage = Storage::disk($disk);
        $retentionDays = max(1, $retentionDays ?? (int) config('operations.backup.retention_days', 14));
        $minKeep = max(1, $minKeep ?? (int) config('operations.backup.min_keep', 1));
        $cutoffTimestamp = now('UTC')->subDays($retentionDays)->getTimestamp();

        $allFiles = $storage->files($directory);

        $candidates = [];
        foreach ($allFiles as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, self::BACKUP_EXTENSIONS, true)) {
                $manifest = $this->loadManifestIfPresent($storage, $file);
                $isValid = $this->checkBackupValidity($disk, $file, $manifest);

                $timestamp = null;
                if ($manifest && $manifest->createdAt !== '') {
                    try {
                        $timestamp = Carbon::parse($manifest->createdAt, 'UTC')->getTimestamp();
                    } catch (Throwable) {
                        $timestamp = null;
                    }
                }

                if ($timestamp === null) {
                    $timestamp = (int) $storage->lastModified($file);
                }

                $candidates[] = [
                    'path' => $file,
                    'timestamp' => $timestamp,
                    'is_valid' => $isValid,
                    'manifest' => $manifest,
                ];
            }
        }

        // Sort candidates newest first
        usort($candidates, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        // Separate valid candidates vs invalid/unverified candidates
        $validCandidates = array_values(array_filter($candidates, fn (array $item): bool => $item['is_valid']));

        // Protect at least $minKeep valid backups
        $protectedValidPaths = [];
        foreach (array_slice($validCandidates, 0, $minKeep) as $validItem) {
            $protectedValidPaths[$validItem['path']] = true;
        }

        $retainedFiles = [];
        $filesToPrune = [];
        $manualReviewCandidates = [];

        foreach ($candidates as $item) {
            $path = $item['path'];
            $timestamp = $item['timestamp'];
            $isValid = $item['is_valid'];

            // 1. NEVER auto-delete invalid, corrupt, unverified, or mismatching backups!
            if (! $isValid) {
                $retainedFiles[] = $path;
                $manualReviewCandidates[] = $path;

                continue;
            }

            // 2. Never prune protected valid backups
            if (isset($protectedValidPaths[$path])) {
                $retainedFiles[] = $path;

                continue;
            }

            // 3. If valid and newer than cutoff (not expired), keep
            if ($timestamp >= $cutoffTimestamp) {
                $retainedFiles[] = $path;

                continue;
            }

            // 4. Candidate is VALID, EXPIRED, and NOT protected by min_keep -> eligible for pruning
            $filesToPrune[] = $path;
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
                if ($storage->exists($fileToDelete)) {
                    $storage->delete($fileToDelete);
                    $prunedCount++;
                }
            }
        } else {
            $prunedCount = count($filesToPrune);
        }

        return [
            'pruned_count' => $prunedCount,
            'pruned_files' => array_values(array_unique($filesToPrune)),
            'retained_files' => array_values(array_unique($retainedFiles)),
            'manual_review_candidates' => array_values(array_unique($manualReviewCandidates)),
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Validate that the filesystem disk is safe and private (not publicly accessible).
     */
    public function validateDiskSafety(string $disk): void
    {
        $normalizedDisk = strtolower(trim($disk));

        if ($normalizedDisk === 'public') {
            throw new InvalidArgumentException("Public filesystem disk [{$disk}] cannot be used for database backups.");
        }

        $diskConfig = config("filesystems.disks.{$disk}");
        if (! is_array($diskConfig)) {
            throw new InvalidArgumentException("Filesystem disk [{$disk}] is not configured in filesystems configuration.");
        }

        // 1. Explicit public visibility check
        if (isset($diskConfig['visibility']) && strtolower((string) $diskConfig['visibility']) === 'public') {
            throw new InvalidArgumentException("Filesystem disk [{$disk}] has public visibility and cannot be used for database backups.");
        }

        // 2. Local root location checks
        if (isset($diskConfig['root'])) {
            $rawRoot = (string) $diskConfig['root'];
            $resolvedRoot = realpath($rawRoot) ?: $rawRoot;
            $publicPath = realpath(public_path()) ?: public_path();
            $storagePublicPath = realpath(storage_path('app/public')) ?: storage_path('app/public');

            if (
                str_starts_with($resolvedRoot, $publicPath) ||
                str_starts_with($resolvedRoot, $storagePublicPath) ||
                str_contains($rawRoot, 'storage/app/public') ||
                str_contains($rawRoot, 'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public')
            ) {
                throw new InvalidArgumentException("Filesystem disk [{$disk}] root is located in a public directory and cannot be used for backups.");
            }
        }

        // 3. Publicly served URL check
        if (isset($diskConfig['url']) && is_string($diskConfig['url'])) {
            $url = strtolower($diskConfig['url']);
            if (str_contains($url, '/storage') || str_contains($url, 'storage/')) {
                throw new InvalidArgumentException("Filesystem disk [{$disk}] is configured as publicly served storage.");
            }
        }
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

    private function loadManifestIfPresent(mixed $storage, string $backupPath): ?BackupManifest
    {
        $manifestPath = $backupPath.'.json';
        if (! $storage->exists($manifestPath)) {
            return null;
        }

        try {
            $content = (string) $storage->get($manifestPath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return BackupManifest::fromArray($data);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function checkBackupValidity(string $disk, string $backupPath, ?BackupManifest $manifest): bool
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($backupPath) || (int) $storage->size($backupPath) <= 0) {
            return false;
        }

        if (! $manifest || ! $storage->exists($backupPath.'.sha256')) {
            return false;
        }

        if ($manifest->sha256 === '' || $manifest->verificationStatus !== 'verified') {
            return false;
        }

        $shaContent = trim((string) $storage->get($backupPath.'.sha256'));
        $expectedSha = explode(' ', $shaContent)[0] ?? '';
        if ($expectedSha === '' || ! hash_equals(strtolower($manifest->sha256), strtolower($expectedSha))) {
            return false;
        }

        // Streaming SHA-256 calculation of actual stored backup bytes
        try {
            $actualSha256 = $this->calculateStorageStreamSha256($disk, $backupPath);
            if (! hash_equals(strtolower($manifest->sha256), strtolower($actualSha256))) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Compute SHA-256 hash by streaming directly from a storage disk.
     */
    public function calculateStorageStreamSha256(string $disk, string $path): string
    {
        if ($this->verificationService !== null) {
            return $this->verificationService->calculateStorageStreamSha256($disk, $path);
        }

        $storage = Storage::disk($disk);
        $stream = $storage->readStream($path);

        if ($stream === false) {
            throw new InvalidArgumentException("Unable to open read stream for [{$disk}:{$path}].");
        }

        $ctx = hash_init('sha256');

        try {
            while (! feof($stream)) {
                $buffer = fread($stream, 1048576); // 1MB chunk
                if ($buffer !== false && $buffer !== '') {
                    hash_update($ctx, $buffer);
                }
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return hash_final($ctx);
    }
}
