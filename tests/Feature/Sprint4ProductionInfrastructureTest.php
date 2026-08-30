<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint4ProductionInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_retention_prunes_old_log_files_and_audit_rows(): void
    {
        $oldLog = storage_path('logs/laravel-2000-01-01.log');
        File::ensureDirectoryExists(dirname($oldLog));
        File::put($oldLog, 'old log');
        touch($oldLog, now()->subDays(40)->getTimestamp());

        $oldAudit = AuditLog::query()->forceCreate([
            'action' => 'old-action',
            'module' => 'test',
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);
        $freshAudit = AuditLog::query()->forceCreate([
            'action' => 'fresh-action',
            'module' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('operations:prune-retention', [
            '--log-days' => 30,
            '--audit-days' => 365,
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($oldLog);
        $this->assertDatabaseMissing('audit_logs', ['id' => $oldAudit->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $freshAudit->id]);
    }

    public function test_sqlite_database_backup_writes_to_configured_disk(): void
    {
        Storage::fake('local');
        $databasePath = storage_path('framework/testing-sprint4.sqlite');
        File::ensureDirectoryExists(dirname($databasePath));
        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }

        $sqlite = new \SQLite3($databasePath);
        $sqlite->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, val TEXT)');
        $sqlite->exec("INSERT INTO test_table (val) VALUES ('test')");
        $sqlite->close();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
            'operations.backup.disk' => 'local',
            'operations.backup.directory' => 'test-backups',
        ]);

        $this->artisan('backup:database')->assertSuccessful();

        $files = Storage::disk('local')->files('test-backups');

        $this->assertCount(3, $files);
        $sqliteFiles = array_values(array_filter($files, fn (string $f): bool => str_ends_with($f, '.sqlite')));
        $this->assertCount(1, $sqliteFiles);
        $this->assertTrue(Storage::disk('local')->exists($sqliteFiles[0].'.json'));
        $this->assertTrue(Storage::disk('local')->exists($sqliteFiles[0].'.sha256'));

        File::delete($databasePath);
    }

    public function test_sqlite_database_backup_verify_restores_to_temporary_database(): void
    {
        Storage::fake('local');
        $databasePath = storage_path('framework/testing-sprint4-verify.sqlite');
        File::ensureDirectoryExists(dirname($databasePath));
        File::delete($databasePath);

        $sqlite = new \SQLite3($databasePath);
        $sqlite->exec('CREATE TABLE backup_verify (id integer primary key, name text not null)');
        $sqlite->exec("INSERT INTO backup_verify (name) VALUES ('ok')");
        $sqlite->close();

        Storage::disk('local')->put('test-backups/valid.sqlite', fopen($databasePath, 'rb'));

        $this->artisan('backup:verify', [
            'path' => 'test-backups/valid.sqlite',
            '--disk' => 'local',
        ])
            ->expectsOutput('Backup verified successfully: local:test-backups/valid.sqlite')
            ->assertSuccessful();

        File::delete($databasePath);
    }

    public function test_api_errors_include_request_id_from_correlation_header(): void
    {
        $this->getJson('/api/user', ['X-Correlation-ID' => 'req-sprint4'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('request_id', 'req-sprint4')
            ->assertHeader('X-Correlation-ID', 'req-sprint4');
    }

    public function test_scheduler_and_deploy_pipeline_are_configured(): void
    {
        $schedule = file_get_contents(base_path('routes/console.php'));
        $this->assertStringContainsString("Schedule::command('operations:prune-retention')->dailyAt('01:30')", $schedule);
        $this->assertStringContainsString("Schedule::command('backup:database --purpose=scheduled --prune')->dailyAt('02:30')->withoutOverlapping()", $schedule);
        $this->assertStringContainsString('backup:verify', file_get_contents(base_path('app/Console/Commands/VerifyDatabaseBackupCommand.php')));

        $this->assertFileExists(base_path('bin/deploy.sh'));
        $deployScript = file_get_contents(base_path('bin/deploy.sh'));
        $this->assertStringContainsString('php artisan migrate --force', $deployScript);
        $this->assertStringContainsString('php artisan queue:restart', $deployScript);

        $this->assertFileExists(base_path('.github/workflows/deploy.yml'));
        $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));
        $this->assertStringContainsString('workflow_dispatch', $workflow);
        $this->assertStringContainsString('DEPLOY_HOST', $workflow);
        $this->assertStringContainsString('bash bin/deploy.sh', $workflow);
    }
}
