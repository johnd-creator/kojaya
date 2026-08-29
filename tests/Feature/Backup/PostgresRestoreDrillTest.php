<?php

namespace Tests\Feature\Backup;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    private ?string $disposableDbName = null;

    private ?string $dumpFilePath = null;

    protected function setUp(): void
    {
        parent::setUp();

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
        if ($this->disposableDbName !== null) {
            $this->safelyDropDisposableDatabase($this->disposableDbName);
        }

        if ($this->dumpFilePath !== null && File::exists($this->dumpFilePath)) {
            File::delete($this->dumpFilePath);
        }

        parent::tearDown();
    }

    public function test_postgresql_backup_and_real_restore_drill_in_isolated_environment(): void
    {
        $connection = Config::get('database.connections.pgsql');
        $sourceDb = (string) ($connection['database'] ?? '');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '5432');
        $username = (string) ($connection['username'] ?? 'postgres');
        $password = (string) ($connection['password'] ?? '');

        // Safety Guard 1: Must never run against production DB
        if (in_array(strtolower($sourceDb), ['kojaya_erp', 'kojaya_prod', 'kojaya_production', 'production'], true)) {
            throw new RuntimeException("CRITICAL SAFETY ERROR: Restore drill attempted against protected database [{$sourceDb}]. Aborting.");
        }

        // 1. Seed deterministic test fixtures in source DB
        $testOrg = Organization::query()->firstOrCreate(
            ['slug' => 'restore-drill-org'],
            [
                'name' => 'Restore Drill Test Organization',
                'is_active' => true,
            ]
        );

        $testUser = User::query()->firstOrCreate(
            ['email' => 'restore.drill.'.uniqid().'@kojaya.local'],
            [
                'name' => 'Restore Drill User',
                'password' => bcrypt('test-password-only'),
            ]
        );

        $sourceUserCount = (int) DB::connection('pgsql')->table('users')->count();
        $sourceOrgCount = (int) DB::connection('pgsql')->table('organizations')->count();

        // 2. Perform Backup via BackupDatabaseCommand
        Storage::fake('local');
        $backupDir = 'backups/restore-drill-test';

        $this->artisan('backup:database', [
            '--purpose' => 'restore-drill',
            '--directory' => $backupDir,
        ])->assertSuccessful();

        $files = Storage::disk('local')->files($backupDir);
        $dumpFiles = array_values(array_filter($files, fn (string $f): bool => str_ends_with($f, '.dump')));
        $this->assertCount(1, $dumpFiles, 'Expected exactly one .dump file');

        $dumpRelativePath = $dumpFiles[0];
        $this->dumpFilePath = storage_path('app/private/backups/tmp/drill_restore_'.uniqid().'.dump');
        File::ensureDirectoryExists(dirname($this->dumpFilePath));
        File::put($this->dumpFilePath, Storage::disk('local')->get($dumpRelativePath));

        // 3. Verify Manifest and Checksum
        $manifestJson = Storage::disk('local')->get($dumpRelativePath.'.json');
        $manifest = json_decode($manifestJson, true);
        $this->assertSame('verified', $manifest['verification_status']);
        $this->assertSame('restore-drill', $manifest['purpose']);
        $this->assertSame('pgsql', $manifest['database_engine']);

        // 4. Generate Disposable Restore Database Name with strict naming pattern
        $this->disposableDbName = 'kojaya_restore_test_'.uniqid().'_'.time();
        $this->assertMatchesRegularExpression('/^kojaya_restore_test_[a-zA-Z0-9_]+$/', $this->disposableDbName);

        // Safety Guard 2: Target cannot equal source database
        $this->assertNotSame($sourceDb, $this->disposableDbName);

        // 5. Create disposable database
        $this->createDisposableDatabase($this->disposableDbName, $host, $port, $username, $password);

        // 6. Execute pg_restore into disposable database
        $restoreProcess = new Process([
            'pg_restore',
            '--no-owner',
            '--no-acl',
            '--exit-on-error',
            '--host='.$host,
            '--port='.$port,
            '--username='.$username,
            '--dbname='.$this->disposableDbName,
            $this->dumpFilePath,
        ], base_path(), ['PGPASSWORD' => $password]);
        $restoreProcess->setTimeout(120);
        $restoreProcess->run();

        if (! $restoreProcess->isSuccessful()) {
            $error = trim($restoreProcess->getErrorOutput() ?: $restoreProcess->getOutput());
            $this->fail("pg_restore into disposable database failed: {$error}");
        }

        // 7. Verify restored dataset inside the disposable database
        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$this->disposableDbName}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $restoredUserCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $restoredOrgCount = (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn();

        $this->assertSame($sourceUserCount, $restoredUserCount);
        $this->assertSame($sourceOrgCount, $restoredOrgCount);

        // Verify specific fixture record persisted intact
        $stmt = $pdo->prepare('SELECT name FROM organizations WHERE slug = :slug');
        $stmt->execute(['slug' => 'restore-drill-org']);
        $restoredOrgName = $stmt->fetchColumn();
        $this->assertSame('Restore Drill Test Organization', $restoredOrgName);

        // Explicitly close connection before tearDown drop
        unset($pdo);
    }

    private function createDisposableDatabase(string $dbName, string $host, string $port, string $user, string $pass): void
    {
        $this->assertMatchesRegularExpression('/^kojaya_restore_test_[a-zA-Z0-9_]+$/', $dbName);

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
            // Fallback to template1 or existing connection db
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
        // Enforce disposable DB naming guard
        if (! preg_match('/^kojaya_restore_test_[a-zA-Z0-9_]+$/', $dbName)) {
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
