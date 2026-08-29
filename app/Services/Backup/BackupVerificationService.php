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
     * Verify a backup stored in a Laravel filesystem disk.
     */
    public function verifyStorageBackup(string $disk, string $path): BackupManifest
    {
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
        $manifest = null;

        if ($storage->exists($manifestPath)) {
            $manifestContent = (string) $storage->get($manifestPath);
            $manifestData = json_decode($manifestContent, true);

            if (is_array($manifestData)) {
                $manifest = BackupManifest::fromArray($manifestData);
            }
        }

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

            // Verify checksum if manifest or .sha256 file exists
            if ($manifest && $manifest->sha256 !== '') {
                $this->verifyChecksum($tmpFile, $manifest->sha256);
            } elseif ($storage->exists($path.'.sha256')) {
                $shaContent = trim((string) $storage->get($path.'.sha256'));
                $expectedSha = explode(' ', $shaContent)[0] ?? '';
                if ($expectedSha !== '') {
                    $this->verifyChecksum($tmpFile, $expectedSha);
                }
            }

            // Verify archive integrity
            $this->verifyLocalArchive($tmpFile, $engine);

            if ($manifest === null) {
                $size = (int) File::size($tmpFile);
                $sha256 = (string) hash_file('sha256', $tmpFile);
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
                    sha256: $sha256,
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
            throw new RuntimeException("PostgreSQL archive verification failed (pg_restore --list): {$errorOutput}");
        }
    }

    private function verifySqliteArchive(string $filePath): void
    {
        try {
            $database = new SQLite3($filePath, SQLITE3_OPEN_READONLY);
            $database->enableExceptions(true);
            $result = @$database->querySingle('PRAGMA integrity_check');
            @$database->close();

            if ($result !== 'ok') {
                $msg = is_string($result) && $result !== '' ? $result : 'invalid archive';
                throw new RuntimeException("PRAGMA integrity_check returned [{$msg}].");
            }
        } catch (Throwable $e) {
            throw new RuntimeException("SQLite integrity check failed: {$e->getMessage()}", 0, $e);
        }
    }

    private function verifySqlArchive(string $filePath): void
    {
        $contents = File::get($filePath);

        if (! str_contains($contents, 'CREATE') && ! str_contains($contents, 'INSERT')) {
            throw new RuntimeException('SQL dump does not contain CREATE or INSERT statements.');
        }
    }
}
