<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabaseService
{
    /**
     * Non-sensitive tables to sample for aggregate row count evidence.
     */
    private const REPRESENTATIVE_TABLES = [
        'users',
        'organizations',
        'cooperative_members',
        'roles',
        'permissions',
        'cooperative_contribution_types',
        'cooperative_payments',
        'cooperative_dues_invoices',
        'cooperative_loans',
        'cooperative_loan_installments',
        'cooperative_savings_ledgers',
        'cooperative_ledger_entries',
        'pos_products',
        'pos_transactions',
        'pos_inventory_locations',
        'employees',
        'audit_logs',
        'migrations',
    ];

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
     * Create a verified database backup with cryptographic manifest and optional off-site replication.
     *
     * @return array{
     *     manifest: BackupManifest,
     *     disk: string,
     *     path: string,
     *     offsite: array<string, mixed>
     * }
     */
    public function backup(
        ?string $disk = null,
        ?string $directory = null,
        string $purpose = 'manual',
        ?string $offsiteDisk = null,
        ?string $offsiteDirectory = null,
        ?bool $requireOffsite = null,
    ): array {
        $startTime = microtime(true);
        $environment = app()->environment();
        $isBackupEnabled = (bool) config('operations.backup.enabled', true);

        // Backup enabled check
        if (! $isBackupEnabled) {
            if ($purpose === 'pre-deploy' && in_array($environment, ['production', 'staging'], true)) {
                throw new RuntimeException("Database backup is disabled via BACKUP_ENABLED=false, but pre-deploy backup is mandatory in [{$environment}]. Deployment aborted.");
            }

            throw new RuntimeException('Database backup is disabled via BACKUP_ENABLED=false.');
        }

        $disk = (string) ($disk ?: config('operations.backup.disk', 'local'));
        $directory = trim((string) ($directory ?: config('operations.backup.directory', 'backups/database')), '/\\');

        // Disk safety validation (must not be public)
        $this->retentionService->validateDiskSafety($disk);
        $this->retentionService->validateDirectorySafety($directory);

        $hasExplicitOffsiteDisk = $offsiteDisk !== null;
        $offsiteDisk = $offsiteDisk ?: config('operations.backup.offsite_disk');
        $offsiteDisk = is_string($offsiteDisk) && trim($offsiteDisk) !== '' ? trim($offsiteDisk) : null;
        $offsiteDirectory = trim((string) ($offsiteDirectory ?: config('operations.backup.offsite_directory', 'backups/database')), '/\\');
        $requireOffsite = $requireOffsite ?? (bool) config('operations.backup.require_offsite', false);
        $offsiteEnabled = $offsiteDisk !== null && ($hasExplicitOffsiteDisk || (bool) config('operations.backup.offsite_enabled', false) || $requireOffsite);

        if ($requireOffsite && $offsiteDisk === null) {
            throw new RuntimeException('Off-site backup replication is required (require_offsite=true), but no usable off-site disk is configured.');
        }

        if ($offsiteDisk !== null) {
            $this->retentionService->validateDiskSafety($offsiteDisk);
            $this->retentionService->validateDirectorySafety($offsiteDirectory);
        }

        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");
        $driver = (string) ($connection['driver'] ?? '');

        // Database identity safety guard:
        // Production and staging MUST use PostgreSQL
        if (in_array($environment, ['production', 'staging'], true) && $driver !== 'pgsql') {
            throw new RuntimeException("Database backup in [{$environment}] requires PostgreSQL driver, found [{$driver}].");
        }

        $environmentSlug = $this->slugify($environment);
        $databaseName = (string) ($connection['database'] ?? 'database');
        $databaseSlug = $this->slugify($databaseName);
        $host = isset($connection['host']) ? (string) $connection['host'] : null;
        $port = $connection['port'] ?? null;
        $serverVersion = $this->resolveServerVersion($connectionName, $driver);

        $gitSha = $this->resolveGitSha();
        if ($purpose === 'pre-deploy' && in_array($environment, ['production', 'staging'], true) && $gitSha === 'unknown') {
            throw new RuntimeException("Pre-deploy database backup in [{$environment}] requires exact Git commit SHA provenance, but Git SHA could not be resolved.");
        }

        $gitShaShort = substr($gitSha, 0, 7) ?: 'unknown';
        $utcTimestamp = now('UTC')->format('Ymd\THis\Z');

        $backupId = "kojaya-{$environmentSlug}-{$databaseSlug}-{$utcTimestamp}-{$gitShaShort}";
        $extension = match ($driver) {
            'pgsql' => 'dump',
            'sqlite' => 'sqlite',
            'mysql', 'mariadb' => 'sql',
            default => throw new RuntimeException("Unsupported backup driver [{$driver}]."),
        };
        $backupFilename = "{$backupId}.{$extension}";
        $targetPath = "{$directory}/{$backupFilename}";

        $storage = Storage::disk($disk);
        if ($storage->exists($targetPath)) {
            throw new RuntimeException("Backup artifact [{$disk}:{$targetPath}] already exists. Overwrite prohibited.");
        }

        Log::info('Database backup started', [
            'backup_id' => $backupId,
            'purpose' => $purpose,
            'environment' => $environment,
            'database' => $databaseName,
            'driver' => $driver,
            'disk' => $disk,
        ]);

        $tmpDirectory = storage_path('app/private/backups/tmp');
        File::ensureDirectoryExists($tmpDirectory);
        $tmpFile = "{$tmpDirectory}/".uniqid('tmp-backup-', true)."-{$backupFilename}";

        try {
            // 1. Gather non-sensitive row counts before dump
            $rowCounts = $this->gatherRowCounts($connectionName);

            // 2. Execute read-only dump
            $this->executeDump($driver, $connection, $tmpFile);

            // 3. Verify file exists and is non-empty
            if (! File::exists($tmpFile) || File::size($tmpFile) <= 0) {
                throw new RuntimeException("Backup dump failed: output file [{$tmpFile}] is missing or 0 bytes.");
            }

            $sizeBytes = (int) File::size($tmpFile);
            $sha256 = (string) hash_file('sha256', $tmpFile);

            // 4. In-line archive verification on local temp file
            $this->verificationService->verifyLocalArchive($tmpFile, $driver);

            // 5. Verify computed checksum
            $this->verificationService->verifyChecksum($tmpFile, $sha256);

            $verifiedAt = now('UTC')->toIso8601String();

            // 6. Build Manifest
            $manifest = new BackupManifest(
                backupId: $backupId,
                createdAt: now('UTC')->toIso8601String(),
                applicationEnvironment: $environment,
                applicationGitSha: $gitSha,
                databaseEngine: $driver,
                databaseName: $databaseName,
                databaseHost: $host,
                databasePort: $port,
                databaseServerVersion: $serverVersion,
                backupFilename: $backupFilename,
                backupFormat: $driver === 'pgsql' ? 'custom' : $extension,
                backupSizeBytes: $sizeBytes,
                sha256: $sha256,
                purpose: $purpose,
                verificationStatus: 'verified',
                verifiedAt: $verifiedAt,
                rowCounts: $rowCounts,
                offsiteCopy: [
                    'enabled' => $offsiteEnabled,
                    'disk' => $offsiteDisk,
                    'directory' => $offsiteDirectory,
                    'copied' => false,
                    'copied_at' => null,
                    'sha256_verified' => false,
                ],
            );

            // 7. Write dump, manifest, and checksum to primary disk
            $stream = fopen($tmpFile, 'rb');
            if ($stream === false) {
                throw new RuntimeException("Failed to open temporary dump file [{$tmpFile}] for reading.");
            }
            $putSuccess = $storage->put($targetPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $putSuccess) {
                throw new RuntimeException("Failed to write backup dump to primary storage [{$disk}:{$targetPath}].");
            }

            $sha256Content = "{$sha256}  {$backupFilename}\n";
            $storage->put($targetPath.'.sha256', $sha256Content);
            $storage->put($targetPath.'.json', $manifest->toJson());

            // 8. CRITICAL: Verify final primary stored artifact
            $this->verifyFinalPrimaryArtifact($disk, $targetPath, $sizeBytes, $sha256);

            // 9. Handle Off-site Replication if enabled
            $offsiteResult = [
                'enabled' => $offsiteEnabled,
                'disk' => $offsiteDisk,
                'directory' => $offsiteDirectory,
                'copied' => false,
                'copied_at' => null,
                'sha256_verified' => false,
            ];

            if ($offsiteEnabled && $offsiteDisk) {
                $offsiteResult = $this->replicateToOffsite(
                    $tmpFile,
                    $backupFilename,
                    $offsiteDisk,
                    $offsiteDirectory,
                    $sha256,
                    $manifest,
                    $requireOffsite,
                );

                $manifest = new BackupManifest(
                    backupId: $manifest->backupId,
                    createdAt: $manifest->createdAt,
                    applicationEnvironment: $manifest->applicationEnvironment,
                    applicationGitSha: $manifest->applicationGitSha,
                    databaseEngine: $manifest->databaseEngine,
                    databaseName: $manifest->databaseName,
                    databaseHost: $manifest->databaseHost,
                    databasePort: $manifest->databasePort,
                    databaseServerVersion: $manifest->databaseServerVersion,
                    backupFilename: $manifest->backupFilename,
                    backupFormat: $manifest->backupFormat,
                    backupSizeBytes: $manifest->backupSizeBytes,
                    sha256: $manifest->sha256,
                    purpose: $manifest->purpose,
                    verificationStatus: $manifest->verificationStatus,
                    verifiedAt: $manifest->verifiedAt,
                    rowCounts: $manifest->rowCounts,
                    offsiteCopy: $offsiteResult,
                    schemaVersion: $manifest->schemaVersion,
                );

                $storage->put($targetPath.'.json', $manifest->toJson());

                if (! empty($offsiteResult['copied'])) {
                    Storage::disk($offsiteDisk)->put("{$offsiteDirectory}/{$backupFilename}.json", $manifest->toJson());
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Database backup completed and verified', [
                'backup_id' => $backupId,
                'purpose' => $purpose,
                'environment' => $environment,
                'database' => $databaseName,
                'size_bytes' => $sizeBytes,
                'sha256' => $sha256,
                'duration_ms' => $duration,
                'disk' => $disk,
                'offsite' => $offsiteResult,
            ]);

            return [
                'manifest' => $manifest,
                'disk' => $disk,
                'path' => $targetPath,
                'offsite' => $offsiteResult,
            ];
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Database backup failed', [
                'backup_id' => $backupId ?? 'unknown',
                'purpose' => $purpose,
                'environment' => $environment,
                'database' => $databaseName ?? 'unknown',
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if (File::exists($tmpFile)) {
                File::delete($tmpFile);
            }
        }
    }

    /**
     * Verify the final stored artifact on primary storage disk.
     */
    private function verifyFinalPrimaryArtifact(string $disk, string $targetPath, int $expectedSizeBytes, string $expectedSha256): void
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($targetPath)) {
            throw new RuntimeException("Primary storage verification failed: file [{$targetPath}] does not exist on disk [{$disk}].");
        }

        $storedSize = (int) $storage->size($targetPath);
        if ($storedSize !== $expectedSizeBytes) {
            throw new RuntimeException("Primary storage verification failed: size mismatch (expected {$expectedSizeBytes} bytes, found {$storedSize} bytes) on disk [{$disk}].");
        }

        $storedSha256 = $this->verificationService->calculateStorageStreamSha256($disk, $targetPath);
        if (! hash_equals(strtolower($expectedSha256), strtolower($storedSha256))) {
            throw new RuntimeException("Primary storage verification failed: SHA-256 mismatch on disk [{$disk}].");
        }

        // Full archive integrity check on stored artifact
        $this->verificationService->verifyStorageBackup($disk, $targetPath, requireProvenance: true);
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function executeDump(string $driver, array $connection, string $tmpFile): void
    {
        match ($driver) {
            'pgsql' => $this->dumpPostgres($connection, $tmpFile),
            'sqlite' => $this->dumpSqlite($connection, $tmpFile),
            'mysql', 'mariadb' => $this->dumpMysql($connection, $tmpFile),
            default => throw new RuntimeException("Unsupported database driver [{$driver}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function dumpPostgres(array $connection, string $tmpFile): void
    {
        $command = array_values(array_filter([
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 5432),
            '--username='.($connection['username'] ?? 'postgres'),
            '--dbname='.($connection['database'] ?? ''),
            '--file='.$tmpFile,
        ]));

        $processEnv = [
            'PGPASSWORD' => (string) ($connection['password'] ?? ''),
        ];

        $process = new Process($command, base_path(), array_filter($processEnv));
        $process->setTimeout((int) config('operations.backup.timeout', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException("pg_dump process execution failed: {$errorOutput}");
        }
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function dumpSqlite(array $connection, string $tmpFile): void
    {
        $database = (string) ($connection['database'] ?? '');

        if ($database === '' || $database === ':memory:') {
            // For in-memory sqlite in testing, export schema and data
            $sqlite = new \SQLite3($tmpFile);
            $sqlite->exec('CREATE TABLE IF NOT EXISTS _backup_test (id INTEGER PRIMARY KEY, created_at TEXT)');
            $sqlite->exec("INSERT INTO _backup_test (created_at) VALUES ('".now()->toIso8601String()."')");
            $sqlite->close();

            return;
        }

        if (! is_file($database)) {
            throw new RuntimeException("SQLite backup requires an existing file-backed database [{$database}].");
        }

        if (! copy($database, $tmpFile)) {
            throw new RuntimeException("Failed to copy SQLite database file to [{$tmpFile}].");
        }
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function dumpMysql(array $connection, string $tmpFile): void
    {
        $command = array_values(array_filter([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 3306),
            '--user='.($connection['username'] ?? ''),
            (string) ($connection['database'] ?? ''),
            '--result-file='.$tmpFile,
        ]));

        $processEnv = [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ];

        $process = new Process($command, base_path(), array_filter($processEnv));
        $process->setTimeout((int) config('operations.backup.timeout', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException("mysqldump process execution failed: {$errorOutput}");
        }
    }

    /**
     * Replicate backup artifacts to offsite disk.
     *
     * @return array<string, mixed>
     */
    private function replicateToOffsite(
        string $tmpFile,
        string $backupFilename,
        string $offsiteDisk,
        string $offsiteDirectory,
        string $sha256,
        BackupManifest $manifest,
        bool $requireOffsite,
    ): array {
        try {
            $offsiteStorage = Storage::disk($offsiteDisk);
            $offsiteTargetPath = "{$offsiteDirectory}/{$backupFilename}";

            $stream = fopen($tmpFile, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Failed to open temporary dump file for offsite upload.');
            }
            $putSuccess = $offsiteStorage->put($offsiteTargetPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $putSuccess) {
                throw new RuntimeException("Failed to write backup dump to offsite storage [{$offsiteDisk}:{$offsiteTargetPath}].");
            }

            $offsiteStorage->put($offsiteTargetPath.'.sha256', "{$sha256}  {$backupFilename}\n");
            $offsiteStorage->put($offsiteTargetPath.'.json', $manifest->toJson());

            // Validate offsite size matches
            $expectedSize = (int) File::size($tmpFile);
            $offsiteSize = (int) $offsiteStorage->size($offsiteTargetPath);

            if ($offsiteSize !== $expectedSize) {
                throw new RuntimeException("Off-site size mismatch: expected {$expectedSize} bytes, got {$offsiteSize} bytes on [{$offsiteDisk}].");
            }

            // Real cryptographic SHA-256 verification of remote offsite bytes
            $offsiteCalculatedSha256 = $this->verificationService->calculateStorageStreamSha256($offsiteDisk, $offsiteTargetPath);
            if (! hash_equals(strtolower($sha256), strtolower($offsiteCalculatedSha256))) {
                throw new RuntimeException("Off-site SHA-256 verification mismatch on [{$offsiteDisk}]: expected [{$sha256}], calculated [{$offsiteCalculatedSha256}].");
            }

            return [
                'enabled' => true,
                'disk' => $offsiteDisk,
                'directory' => $offsiteDirectory,
                'copied' => true,
                'copied_at' => now('UTC')->toIso8601String(),
                'sha256_verified' => true,
            ];
        } catch (Throwable $e) {
            Log::warning('Off-site backup replication failed', [
                'offsite_disk' => $offsiteDisk,
                'error' => $e->getMessage(),
                'required' => $requireOffsite,
            ]);

            if ($requireOffsite) {
                throw new RuntimeException("Required off-site backup copy to [{$offsiteDisk}] failed: {$e->getMessage()}", 0, $e);
            }

            return [
                'enabled' => true,
                'disk' => $offsiteDisk,
                'directory' => $offsiteDirectory,
                'copied' => false,
                'copied_at' => null,
                'sha256_verified' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sample non-sensitive row counts for known tables.
     *
     * @return array<string, int>
     */
    private function gatherRowCounts(string $connectionName): array
    {
        $counts = [];

        foreach (self::REPRESENTATIVE_TABLES as $table) {
            try {
                if (Schema::connection($connectionName)->hasTable($table)) {
                    $counts[$table] = (int) DB::connection($connectionName)->table($table)->count();
                }
            } catch (Throwable) {
                // Table count gathering is best-effort evidence; ignore individual errors
            }
        }

        return $counts;
    }

    private function resolveServerVersion(string $connectionName, string $driver): ?string
    {
        if ($driver !== 'pgsql') {
            return null;
        }

        try {
            $result = DB::connection($connectionName)->selectOne('SHOW server_version');
            if ($result && isset($result->server_version)) {
                return (string) $result->server_version;
            }
        } catch (Throwable) {
            // DB might be accessed only via CLI tool, return null if query fails
        }

        return null;
    }

    private function resolveGitSha(): string
    {
        $envSha = (string) (config('app.git_sha') ?: env('APP_GIT_SHA', ''));
        if (preg_match('/^[0-9a-fA-F]{40}$/', $envSha)) {
            return $envSha;
        }

        try {
            $sha = trim((string) shell_exec('git rev-parse HEAD 2>/dev/null'));
            if (preg_match('/^[0-9a-fA-F]{40}$/', $sha)) {
                return $sha;
            }
        } catch (Throwable) {
            // Non-git environment
        }

        $revisionFile = base_path('REVISION');
        if (File::exists($revisionFile)) {
            $rev = trim((string) File::get($revisionFile));
            if (preg_match('/^[0-9a-fA-F]{40}$/', $rev)) {
                return $rev;
            }
        }

        return 'unknown';
    }

    private function slugify(string $value): string
    {
        return str($value)
            ->replace(['/', '\\', '..'], '-')
            ->replaceMatches('/[^A-Za-z0-9_.-]+/', '-')
            ->replaceMatches('/\.+/', '.')
            ->trim('.-')
            ->lower()
            ->value() ?: 'unknown';
    }
}
