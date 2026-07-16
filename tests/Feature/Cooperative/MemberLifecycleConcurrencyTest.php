<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * True concurrency test: two separate PHP processes race approveFinal vs reject
 * against the same member using PostgreSQL row-level locking.
 *
 * Requires PostgreSQL and runs only when DB_CONNECTION=pgsql is set in the
 * environment (e.g. the postgres-concurrency CI job).
 * In the default SQLite test environment this test is skipped.
 */
class MemberLifecycleConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    /** @var array<string, string> */
    private array $dbConfig = [];

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        // Capture the original DB_CONNECTION BEFORE parent::setUp() forces SQLite.
        $originalConnection = getenv('DB_CONNECTION') ?: 'sqlite';

        if ($originalConnection !== 'pgsql') {
            parent::setUp();
            $this->markTestSkipped('MemberLifecycleConcurrencyTest requires PostgreSQL (DB_CONNECTION=pgsql).');

            return;
        }

        // Capture full pgsql config from environment before parent overrides.
        $this->dbConfig = [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_DATABASE') ?: 'kojaya_test',
            'username' => getenv('DB_USERNAME') ?: 'kojaya',
            'password' => getenv('DB_PASSWORD') ?: 'kojaya',
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
        ];

        parent::setUp();

        // Override the database config to use PostgreSQL after parent forced SQLite.
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', $this->dbConfig);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-lifecycle-concurrency-'.bin2hex(random_bytes(8));
        mkdir($this->workingDirectory, 0777, true);

        Artisan::call('migrate:fresh', [
            '--database' => 'pgsql',
            '--force' => true,
        ]);

        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        if ($this->workingDirectory !== '') {
            foreach (glob($this->workingDirectory.'/*') ?: [] as $file) {
                @unlink($file);
            }

            $resultDir = $this->workingDirectory.'/results';
            if (is_dir($resultDir)) {
                @rmdir($resultDir);
            }

            @rmdir($this->workingDirectory);
        }

        parent::tearDown();
    }

    public function test_concurrent_approve_final_vs_reject_exactly_one_wins(): void
    {
        $org = Organization::factory()->create();

        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $workerFile = $this->workingDirectory.'/worker.php';
        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        file_put_contents($workerFile, $this->workerScript());

        $processes = [
            $this->startWorker($workerFile, $startFile, $resultDir, 'approve', $pengurus->id, $member->id),
            $this->startWorker($workerFile, $startFile, $resultDir, 'reject', $admin->id, $member->id),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'approve'),
            $this->finishWorker($processes[1], $resultDir, 'reject'),
        ];

        $successes = array_filter($results, fn (array $r): bool => $r['ok']);
        $failures = array_filter($results, fn (array $r): bool => ! $r['ok']);

        if (count($successes) !== 1) {
            $messages = array_map(fn (array $r): string => sprintf(
                '%s: ok=%s class=%s message=%s',
                $r['action'] ?? 'unknown',
                json_encode($r['ok'] ?? null),
                $r['class'] ?? 'none',
                $r['message'] ?? 'none',
            ), $results);

            $this->fail(
                'Expected exactly one successful transition, got '.count($successes)
                .'. Worker results: '.implode(' | ', $messages)
            );
        }

        $this->assertCount(1, $failures, 'Exactly one transition should fail due to row lock.');

        $member->refresh();
        $winner = reset($successes);

        if ($winner['action'] === 'approve') {
            $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->validation_status);
            $this->assertSame('ACTIVE', $member->status);
        } else {
            $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->validation_status);
            $this->assertSame('INACTIVE', $member->status);
        }

        $transitionAudits = AuditLog::query()
            ->where('action', 'member.status.transitioned')
            ->where('subject_id', $member->id)
            ->count();

        $this->assertSame(1, $transitionAudits, 'Exactly one terminal audit should exist.');
    }

    private function workerScript(): string
    {
        // Embed the PostgreSQL connection parameters directly into the worker
        // script so it can connect independently of the test process.
        $dbHost = $this->dbConfig['host'];
        $dbPort = $this->dbConfig['port'];
        $dbDatabase = $this->dbConfig['database'];
        $dbUsername = $this->dbConfig['username'];
        $dbPassword = $this->dbConfig['password'];

        return <<<PHP
<?php

declare(strict_types=1);

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Cooperative\MemberStatusTransitionService;
use Illuminate\Contracts\Console\Kernel;

[\$script, \$repoPath, \$startFile, \$resultDir, \$action, \$actorId, \$memberId] = \$argv;

while (! file_exists(\$startFile)) {
    usleep(10000);
}

putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('DB_CONNECTION=pgsql');
putenv("DB_HOST={$dbHost}");
putenv("DB_PORT={$dbPort}");
putenv("DB_DATABASE={$dbDatabase}");
putenv("DB_USERNAME={$dbUsername}");
putenv("DB_PASSWORD={$dbPassword}");

\$_ENV['APP_ENV'] = 'testing';
\$_ENV['CACHE_STORE'] = 'array';
\$_ENV['SESSION_DRIVER'] = 'array';
\$_ENV['QUEUE_CONNECTION'] = 'sync';
\$_ENV['DB_CONNECTION'] = 'pgsql';

require \$repoPath.'/vendor/autoload.php';

\$app = require \$repoPath.'/bootstrap/app.php';
\$app->make(Kernel::class)->bootstrap();

config()->set('database.default', 'pgsql');
config()->set('database.connections.pgsql', [
    'driver' => 'pgsql',
    'host' => '{$dbHost}',
    'port' => '{$dbPort}',
    'database' => '{$dbDatabase}',
    'username' => '{$dbUsername}',
    'password' => '{$dbPassword}',
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
]);

\DB::purge('pgsql');
\DB::reconnect('pgsql');

\$resultFile = \$resultDir.'/'.\$action.'.json';

try {
    \$actor = User::query()->findOrFail((int) \$actorId);
    \$member = CooperativeMember::query()->findOrFail((int) \$memberId);
    \$service = app(MemberStatusTransitionService::class);

    if (\$action === 'approve') {
        \$service->approveFinal(\$member, \$actor, 'concurrent approve');
    } else {
        \$service->reject(\$member, \$actor, 'concurrent reject');
    }

    file_put_contents(\$resultFile, json_encode([
        'ok' => true,
        'action' => \$action,
        'validation_status' => \$member->refresh()->validation_status,
        'status' => \$member->status,
    ], JSON_THROW_ON_ERROR));

    exit(0);
} catch (Throwable \$throwable) {
    file_put_contents(\$resultFile, json_encode([
        'ok' => false,
        'action' => \$action,
        'class' => \$throwable::class,
        'message' => \$throwable->getMessage(),
    ], JSON_THROW_ON_ERROR));

    exit(1);
}
PHP;
    }

    /**
     * @return array{process: mixed, pipes: array<int, resource>}
     */
    private function startWorker(string $workerFile, string $startFile, string $resultDir, string $action, int $actorId, string $memberId): array
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $startFile,
                $resultDir,
                $action,
                (string) $actorId,
                $memberId,
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path(),
        );

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    /**
     * @param  array{process: mixed, pipes: array<int, resource>}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker, string $resultDir, string $action): array
    {
        $stderr = '';
        if (is_resource($worker['pipes'][2] ?? null)) {
            $stderr = (string) stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1] ?? null)) {
            fclose($worker['pipes'][1]);
        }

        $exitCode = 0;
        if (is_resource($worker['process'] ?? null)) {
            $exitCode = proc_close($worker['process']);
        }

        $resultFile = $resultDir.'/'.$action.'.json';
        $contents = file_exists($resultFile) ? file_get_contents($resultFile) : '';

        if ($contents === false || $contents === '') {
            return [
                'ok' => false,
                'action' => $action,
                'class' => 'WorkerCrashed',
                'message' => "Worker [{$action}] did not write a result file. Exit code: {$exitCode}. Stderr: ".trim($stderr),
            ];
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! array_key_exists('ok', $decoded)) {
            return [
                'ok' => false,
                'action' => $action,
                'class' => 'MalformedResult',
                'message' => "Worker [{$action}] wrote a malformed result. Contents: {$contents}",
            ];
        }

        return $decoded;
    }
}
