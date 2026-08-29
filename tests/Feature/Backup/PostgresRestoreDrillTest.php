<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupDatabaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Throwable;

class PostgresRestoreDrillTest extends TestCase
{
    private ?string $disposableSourceDb = null;

    private ?string $disposableTargetDb = null;

    private ?string $dumpFilePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')) {
            throw new RuntimeException('CRITICAL: PostgreSQL restore drill is strictly prohibited outside APP_ENV=testing.');
        }

        if (Config::get('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL restore drill requires pgsql database connection.');
        }

        // Verify pg_dump and pg_restore binaries are available
        $pgDumpCheck = new Process(['which', 'pg_dump']);
        $pgDumpCheck->run();
        $pgRestoreCheck = new Process(['which', 'pg_restore']);
        $pgRestoreCheck->run();

        if (! $pgDumpCheck->isSuccessful() || ! $pgRestoreCheck->isSuccessful()) {
            $this->markTestSkipped('pg_dump and pg_restore binaries are required for PostgreSQL restore drill.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->disposableSourceDb !== null) {
            $this->safelyDropDisposableDatabase($this->disposableSourceDb);
        }

        if ($this->disposableTargetDb !== null) {
            $this->safelyDropDisposableDatabase($this->disposableTargetDb);
        }

        if ($this->dumpFilePath !== null && File::exists($this->dumpFilePath)) {
            File::delete($this->dumpFilePath);
        }

        parent::tearDown();
    }

    public function test_postgresql_backup_and_real_restore_drill_with_disposable_source_and_target(): void
    {
        $connection = Config::get('database.connections.pgsql');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '5432');
        $username = (string) ($connection['username'] ?? 'postgres');
        $password = (string) ($connection['password'] ?? '');

        // 1. Generate strictly namespaced disposable source and target database names
        $uniqueId = uniqid().'_'.time();
        $this->disposableSourceDb = 'kojaya_restore_source_'.$uniqueId;
        $this->disposableTargetDb = 'kojaya_restore_target_'.$uniqueId;

        $this->assertMatchesRegularExpression('/^kojaya_restore_source_[a-zA-Z0-9_]+$/', $this->disposableSourceDb);
        $this->assertMatchesRegularExpression('/^kojaya_restore_target_[a-zA-Z0-9_]+$/', $this->disposableTargetDb);

        // 2. Create disposable source database
        $this->createDisposableDatabase($this->disposableSourceDb, $host, $port, $username, $password);

        // 3. Configure temporary database connection for source
        Config::set('database.connections.pgsql.database', $this->disposableSourceDb);
        Config::set('database.default', 'pgsql');
        DB::purge('pgsql');

        // 4. Migrate disposable source database
        $this->artisan('migrate', [
            '--database' => 'pgsql',
            '--force' => true,
        ])->assertSuccessful();

        // 5. Seed deterministic test fixtures into disposable source database
        $pdoSource = new PDO(
            "pgsql:host={$host};port={$port};dbname={$this->disposableSourceDb}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdoSource->exec("
            INSERT INTO organizations (name, slug, is_active, created_at, updated_at)
            VALUES ('Drill Source Org', 'drill-source-org', true, NOW(), NOW());
        ");

        $pdoSource->exec("
            INSERT INTO users (name, email, password, created_at, updated_at)
            VALUES ('Drill Source User', 'drill.user@kojaya.local', 'fake-hashed-password', NOW(), NOW());
        ");

        $sourceUserCount = (int) $pdoSource->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $sourceOrgCount = (int) $pdoSource->query('SELECT COUNT(*) FROM organizations')->fetchColumn();
        unset($pdoSource);

        $this->assertGreaterThan(0, $sourceUserCount);
        $this->assertGreaterThan(0, $sourceOrgCount);

        // 6. Execute actual Kojaya BackupDatabaseService code path
        $drillDirectory = 'backups/database/drill_'.uniqid();
        $backupService = app(\App\Services\Backup\BackupDatabaseService::class);
        $result = $backupService->backup(
            disk: 'local',
            directory: $drillDirectory,
            purpose: 'restore-drill'
        );

        $this->assertSame('success', $result['status']);
        $producedRelativePath = $result['primary_copy']['path'];
        $this->dumpFilePath = Storage::disk('local')->path($producedRelativePath);

        // 7. Assert produced .dump, .json manifest, and .sha256 checksum
        $this->assertFileExists($this->dumpFilePath);
        $this->assertGreaterThan(0, File::size($this->dumpFilePath));
        $this->assertFileExists($this->dumpFilePath.'.json');
        $this->assertFileExists($this->dumpFilePath.'.sha256');

        // 8. Cryptographically verify the produced backup artifact
        $verificationService = app(\App\Services\Backup\BackupVerificationService::class);
        $manifest = $verificationService->verifyStorageBackup('local', $producedRelativePath, requireProvenance: true);
        $this->assertSame('verified', $manifest->verificationStatus);
        $this->assertSame('pgsql', $manifest->databaseEngine);

        // 9. Verify dump archive structure using pg_restore --list
        $listProcess = new Process([
            'pg_restore',
            '--list',
            $this->dumpFilePath,
        ], base_path());
        $listProcess->run();
        $this->assertTrue($listProcess->isSuccessful(), 'pg_restore --list must succeed on custom dump');

        // 10. Create disposable target database
        $this->createDisposableDatabase($this->disposableTargetDb, $host, $port, $username, $password);

        // 11. Execute pg_restore into disposable target database
        $restoreProcess = new Process([
            'pg_restore',
            '--no-owner',
            '--no-acl',
            '--exit-on-error',
            '--host='.$host,
            '--port='.$port,
            '--username='.$username,
            '--dbname='.$this->disposableTargetDb,
            $this->dumpFilePath,
        ], base_path(), ['PGPASSWORD' => $password]);
        $restoreProcess->setTimeout(120);
        $restoreProcess->run();

        if (! $restoreProcess->isSuccessful()) {
            $error = trim($restoreProcess->getErrorOutput() ?: $restoreProcess->getOutput());
            $this->fail("pg_restore into disposable target database failed: {$error}");
        }

        // 12. Verify restored data in target database
        $pdoTarget = new PDO(
            "pgsql:host={$host};port={$port};dbname={$this->disposableTargetDb}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $restoredUserCount = (int) $pdoTarget->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $restoredOrgCount = (int) $pdoTarget->query('SELECT COUNT(*) FROM organizations')->fetchColumn();

        $this->assertSame($sourceUserCount, $restoredUserCount);
        $this->assertSame($sourceOrgCount, $restoredOrgCount);

        $stmt = $pdoTarget->prepare('SELECT name FROM organizations WHERE slug = :slug');
        $stmt->execute(['slug' => 'drill-source-org']);
        $restoredOrgName = $stmt->fetchColumn();
        $this->assertSame('Drill Source Org', $restoredOrgName);

        unset($pdoTarget);

        // Cleanup drill directory in storage
        Storage::disk('local')->deleteDirectory($drillDirectory);
    }

    private function createDisposableDatabase(string $dbName, string $host, string $port, string $user, string $pass): void
    {
        if (! preg_match('/^kojaya_restore_(source|target)_[a-zA-Z0-9_]+$/', $dbName)) {
            throw new RuntimeException("Invalid disposable database name [{$dbName}].");
        }

        $process = new Process([
            'psql',
            '--host='.$host,
            '--port='.$port,
            '--username='.$user,
            '--dbname=postgres',
            '--command=CREATE DATABASE '.$dbName.';',
        ], base_path(), ['PGPASSWORD' => $pass]);
        $process->run();

        if (! $process->isSuccessful()) {
            $process = new Process([
                'psql',
                '--host='.$host,
                '--port='.$port,
                '--username='.$user,
                '--dbname=kojaya_test',
                '--command=CREATE DATABASE '.$dbName.';',
            ], base_path(), ['PGPASSWORD' => $pass]);
            $process->run();
        }

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException("Failed to create disposable database [{$dbName}]: {$error}");
        }
    }

    private function safelyDropDisposableDatabase(string $dbName): void
    {
        if (! preg_match('/^kojaya_restore_(source|target)_[a-zA-Z0-9_]+$/', $dbName)) {
            return;
        }

        $connection = Config::get('database.connections.pgsql');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '5432');
        $username = (string) ($connection['username'] ?? 'postgres');
        $password = (string) ($connection['password'] ?? '');

        try {
            $process = new Process([
                'psql',
                '--host='.$host,
                '--port='.$port,
                '--username='.$username,
                '--dbname=postgres',
                '--command=DROP DATABASE IF EXISTS '.$dbName.' WITH (FORCE);',
            ], base_path(), ['PGPASSWORD' => $password]);
            $process->run();

            if (! $process->isSuccessful()) {
                $process = new Process([
                    'psql',
                    '--host='.$host,
                    '--port='.$port,
                    '--username='.$username,
                    '--dbname=kojaya_test',
                    '--command=DROP DATABASE IF EXISTS '.$dbName.' WITH (FORCE);',
                ], base_path(), ['PGPASSWORD' => $password]);
                $process->run();
            }
        } catch (Throwable) {
            // Best effort cleanup
        }
    }
}
