<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SQLite3;
use Symfony\Component\Process\Process;
use Throwable;

class BackupVerificationService
{
    public function __construct(
        private readonly ?BackupRetentionService $retentionService = null,
    ) {}

    /**
     * Verify a local dump file for archive integrity.
     */
    public function verifyLocalArchive(string $filePath, string $engine): void
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("Backup file does not exist at [{$filePath}].");
        }

        $size = (int) File::size($filePath);
        if ($size <= 0) {
            throw new RuntimeException("Backup file [{$filePath}] is empty (0 bytes).");
        }

        match ($engine) {
            'pgsql' => $this->verifyPostgresArchive($filePath),
            'sqlite' => $this->verifySqliteArchive($filePath),
            'mysql', 'mariadb' => $this->verifySqlArchive($filePath),
            default => throw new RuntimeException("Unsupported database engine for verification [{$engine}]."),
        };
    }

    /**
     * Verify that a file's SHA-256 hash matches the expected hash.
     */
    public function verifyChecksum(string $filePath, string $expectedSha256): void
    {
        $actualSha256 = hash_file('sha256', $filePath);

        if (! is_string($actualSha256) || ! hash_equals(strtolower($expectedSha256), strtolower($actualSha256))) {
            throw new RuntimeException("Checksum mismatch: expected [{$expectedSha256}], calculated [{$actualSha256}].");
        }
    }

    /**
     * Compute SHA-256 hash by streaming directly from a storage disk.
     */
    public function calculateStorageStreamSha256(string $disk, string $path): string
    {
        $storage = Storage::disk($disk);
        $stream = $storage->readStream($path);

        if ($stream === false) {
            throw new RuntimeException("Unable to open read stream for [{$disk}:{$path}].");
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

    /**
     * Verify a backup stored in a Laravel filesystem disk.
     */
    public function verifyStorageBackup(string $disk, string $path, bool $requireProvenance = false): BackupManifest
    {
        if ($this->retentionService) {
            $this->retentionService->validateDiskSafety($disk);
        } else {
            (new BackupRetentionService)->validateDiskSafety($disk);
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new RuntimeException("Backup file does not exist on disk [{$disk}:{$path}].");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $engine = match ($extension) {
            'dump' => 'pgsql',
            'sqlite', 'sqlite3', 'db' => 'sqlite',
            'sql' => 'mysql',
            default => throw new RuntimeException("Unknown backup extension [{$extension}]."),
        };

        $manifestPath = $path.'.json';
        $shaPath = $path.'.sha256';
        $manifest = null;

        if ($storage->exists($manifestPath)) {
            $manifestContent = (string) $storage->get($manifestPath);
            $manifestData = json_decode($manifestContent, true);

            if (is_array($manifestData)) {
                $manifest = BackupManifest::fromArray($manifestData);
            }
        }

        if ($requireProvenance && ($manifest === null || ! $storage->exists($shaPath))) {
            throw new RuntimeException("Managed backup [{$disk}:{$path}] is missing required cryptographic provenance (.json manifest or .sha256 checksum).");
        }

        // 1. Verify streaming SHA-256
        $streamSha256 = $this->calculateStorageStreamSha256($disk, $path);

        if ($manifest && $manifest->sha256 !== '') {
            if (! hash_equals(strtolower($manifest->sha256), strtolower($streamSha256))) {
                throw new RuntimeException("Storage SHA-256 mismatch against manifest: expected [{$manifest->sha256}], calculated [{$streamSha256}].");
            }
        }

        if ($storage->exists($shaPath)) {
            $shaContent = trim((string) $storage->get($shaPath));
            $expectedSha = explode(' ', $shaContent)[0] ?? '';
            if ($expectedSha !== '' && ! hash_equals(strtolower($expectedSha), strtolower($streamSha256))) {
                throw new RuntimeException("Storage SHA-256 mismatch against .sha256 file: expected [{$expectedSha}], calculated [{$streamSha256}].");
            }
        }

        // 2. Download to isolated temporary file to perform archive structure verification
        $tmpDirectory = storage_path('app/private/backups/verify');
        File::ensureDirectoryExists($tmpDirectory);
        $tmpFile = $tmpDirectory.'/'.uniqid('verify-', true).'.'.$extension;

        try {
            $stream = $storage->readStream($path);
            if ($stream === false) {
                throw new RuntimeException("Unable to read backup stream from [{$disk}:{$path}].");
            }

            $targetStream = fopen($tmpFile, 'wb');
            if ($targetStream === false) {
                throw new RuntimeException("Unable to open temporary verification file [{$tmpFile}].");
            }

            stream_copy_to_stream($stream, $targetStream);
            fclose($targetStream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Verify archive integrity
            $this->verifyLocalArchive($tmpFile, $engine);

            if ($manifest === null) {
                $size = (int) File::size($tmpFile);
                $manifest = new BackupManifest(
                    backupId: pathinfo($path, PATHINFO_FILENAME),
                    createdAt: now('UTC')->toIso8601String(),
                    applicationEnvironment: app()->environment(),
                    applicationGitSha: 'unknown',
                    databaseEngine: $engine,
                    databaseName: pathinfo($path, PATHINFO_FILENAME),
                    databaseHost: null,
                    databasePort: null,
                    databaseServerVersion: null,
                    backupFilename: basename($path),
                    backupFormat: $extension === 'dump' ? 'custom' : $extension,
                    backupSizeBytes: $size,
                    sha256: $streamSha256,
                    purpose: 'manual',
                    verificationStatus: 'verified',
                    verifiedAt: now('UTC')->toIso8601String(),
                );
            }

            return $manifest;
        } finally {
            if (File::exists($tmpFile)) {
                File::delete($tmpFile);
            }
        }
    }

    private function verifyPostgresArchive(string $filePath): void
    {
        $process = new Process(['pg_restore', '--list', $filePath], base_path());
        $process->setTimeout((int) config('operations.backup.timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException("PostgreSQL archive verification (pg_restore --list) failed: {$errorOutput}");
        }
    }

    private function verifySqliteArchive(string $filePath): void
    {
        try {
            $db = new SQLite3($filePath, SQLITE3_OPEN_READONLY);
            $db->enableExceptions(true);

            $result = $db->querySingle('PRAGMA integrity_check;');
            $db->close();

            if ($result !== 'ok') {
                throw new RuntimeException("SQLite integrity check failed: returned [{$result}].");
            }
        } catch (Throwable $e) {
            throw new RuntimeException("SQLite backup verification failed: {$e->getMessage()}", 0, $e);
        }
    }

    private function verifySqlArchive(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot read SQL archive file [{$filePath}].");
        }

        $header = fread($handle, 4096);
        fclose($handle);

        if ($header === false || strlen($header) < 10) {
            throw new RuntimeException("SQL archive [{$filePath}] is too short or unreadable.");
        }
    }
}
