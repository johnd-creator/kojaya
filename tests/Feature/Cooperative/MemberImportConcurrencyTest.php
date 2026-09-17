<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * True PostgreSQL concurrency test: Two separate PHP processes concurrently execute
 * canonical member imports containing blank member_numbers against the same global
 * KOP-### sequence. PostgreSQL transaction-level advisory lock (pg_advisory_xact_lock)
 * ensures serialized number allocation, yielding distinct numbers and zero duplicate collisions.
 *
 * Requires PostgreSQL (DB_CONNECTION=pgsql); skipped under SQLite.
 */
class MemberImportConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    /** @var array<string, string> */
    private array $dbConfig = [];

    private static string $requiredConnection = '';

    public static function setUpBeforeClass(): void
    {
        self::$requiredConnection = getenv('DB_CONNECTION') ?: 'sqlite';
    }

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        if (self::$requiredConnection !== 'pgsql') {
            self::fail(
                'MemberImportConcurrencyTest REQUIRES PostgreSQL. Got DB_CONNECTION='.self::$requiredConnection
                .'. Use: vendor/bin/phpunit --configuration phpunit.pgsql.xml tests/Feature/Cooperative/MemberImportConcurrencyTest.php'
            );
        }

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

        $this->refreshApplication();

        putenv('DB_CONNECTION=pgsql');
        putenv('DB_DATABASE='.$this->dbConfig['database']);
        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_DATABASE'] = $this->dbConfig['database'];
        $_SERVER['DB_CONNECTION'] = 'pgsql';
        $_SERVER['DB_DATABASE'] = $this->dbConfig['database'];

        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', $this->dbConfig);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-import-concurrency-'.bin2hex(random_bytes(8));
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
                foreach (glob($resultDir.'/*') ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($resultDir);
            }

            @rmdir($this->workingDirectory);
        }

        parent::tearDown();
    }

    public function test_concurrent_imports_with_blank_member_numbers_allocate_distinct_numbers(): void
    {
        $org = Organization::factory()->create();

        // Prepare CSV A with 2 members needing auto-generated numbers
        $csvContentA = implode("\n", [
            'member_number,full_name,email,phone_number,identity_number,gender,company_code,employee_number,address,membership_type,join_date,notes',
            ',Budi Santoso,budi.concurrency@example.com,081234560001,3171012301900001,L,IP,,Jl. Sudirman No. 1,AB,2026-06-01,Concurrent A1',
            ',Siti Aminah,siti.concurrency@example.com,081234560002,3171012301900002,P,IP,,Jl. Sudirman No. 2,AB,2026-06-01,Concurrent A2',
        ]);
        $csvPathA = $this->workingDirectory.'/import_a.csv';
        file_put_contents($csvPathA, $csvContentA);
        $hashA = hash_file('sha256', $csvPathA);

        // Prepare CSV B with 2 members needing auto-generated numbers in same global numbering domain
        $csvContentB = implode("\n", [
            'member_number,full_name,email,phone_number,identity_number,gender,company_code,employee_number,address,membership_type,join_date,notes',
            ',Ahmad Dahlan,ahmad.concurrency@example.com,081234560003,3171012301900003,L,IP,,Jl. Sudirman No. 3,AB,2026-06-01,Concurrent B1',
            ',Dewi Sartika,dewi.concurrency@example.com,081234560004,3171012301900004,P,IP,,Jl. Sudirman No. 4,AB,2026-06-01,Concurrent B2',
        ]);
        $csvPathB = $this->workingDirectory.'/import_b.csv';
        file_put_contents($csvPathB, $csvContentB);
        $hashB = hash_file('sha256', $csvPathB);

        $workerFile = $this->workingDirectory.'/worker.php';
        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        file_put_contents($workerFile, $this->workerScript());

        // Spawn Worker A and Worker B concurrently
        $processes = [
            $this->startWorker($workerFile, $startFile, $resultDir, 'importer_a', $csvPathA, (string) $org->id, '2026-06-01', $hashA),
            $this->startWorker($workerFile, $startFile, $resultDir, 'importer_b', $csvPathB, (string) $org->id, '2026-06-01', $hashB),
        ];

        // Give workers time to launch and await start signal
        usleep(300000);
        touch($startFile);

        $resultA = $this->finishWorker($processes[0], $resultDir, 'importer_a');
        $resultB = $this->finishWorker($processes[1], $resultDir, 'importer_b');

        // Assert both workers completed successfully
        $this->assertTrue($resultA['ok'], 'Worker A failed: '.($resultA['message'] ?? ''));
        $this->assertTrue($resultB['ok'], 'Worker B failed: '.($resultB['message'] ?? ''));

        $this->assertSame(2, $resultA['imported_count']);
        $this->assertSame(2, $resultB['imported_count']);

        // Assert 4 members in database total
        $this->assertSame(4, CooperativeMember::query()->count());

        // Collect all generated numbers
        $numbersA = $resultA['generated_member_numbers'];
        $numbersB = $resultB['generated_member_numbers'];

        $this->assertCount(2, $numbersA);
        $this->assertCount(2, $numbersB);

        // Verify zero collisions between workers
        $overlap = array_intersect($numbersA, $numbersB);
        $this->assertEmpty($overlap, 'Concurrent imports collided on member numbers: '.json_encode($overlap));

        // Verify all 4 numbers in database are unique and distinct
        $dbNoAnggota = CooperativeMember::query()->pluck('no_anggota')->all();
        $this->assertCount(4, array_unique($dbNoAnggota));

        $dbMemberNo = CooperativeMember::query()->pluck('member_no')->all();
        $this->assertCount(4, array_unique($dbMemberNo));

        // Verify all generated numbers follow KOP-### sequence
        foreach ($dbNoAnggota as $number) {
            $this->assertMatchesRegularExpression('/^KOP-\d{3,}$/', $number);
        }

        // Verify both transactions created mandatory audit logs
        $auditLogs = AuditLog::query()
            ->where('action', 'member.import.completed')
            ->where('module', 'cooperative')
            ->count();
        $this->assertSame(2, $auditLogs);

        // Verify all members start in PENDING/PENDING state with null user_id
        $pendingCount = CooperativeMember::query()
            ->where('status', 'PENDING')
            ->where('validation_status', CooperativeMember::VALIDATION_PENDING)
            ->whereNull('user_id')
            ->count();
        $this->assertSame(4, $pendingCount);
    }

    private function workerScript(): string
    {
        $dbHost = $this->dbConfig['host'];
        $dbPort = $this->dbConfig['port'];
        $dbDatabase = $this->dbConfig['database'];
        $dbUsername = $this->dbConfig['username'];
        $dbPassword = $this->dbConfig['password'];

        return <<<PHP
<?php

declare(strict_types=1);

use App\Services\Cooperative\MemberImportExecutionService;
use Illuminate\Contracts\Console\Kernel;

[\$script, \$repoPath, \$startFile, \$resultDir, \$workerName, \$filePath, \$organizationId, \$importDate, \$fileSha256] = \$argv;

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
config()->set('cooperative.member_import_execution_enabled', true);

\DB::purge('pgsql');
\DB::reconnect('pgsql');

\$resultFile = \$resultDir.'/'.\$workerName.'.json';

try {
    \$service = app(MemberImportExecutionService::class);

    \$result = \$service->execute(
        filePath: \$filePath,
        organizationId: \$organizationId,
        importDate: \$importDate,
        fileSha256: \$fileSha256,
    );

    file_put_contents(\$resultFile, json_encode([
        'ok' => true,
        'worker' => \$workerName,
        'imported_count' => \$result->importedCount,
        'generated_member_numbers' => \$result->generatedMemberNumbers,
        'supplied_member_numbers' => \$result->suppliedMemberNumbers,
    ], JSON_THROW_ON_ERROR));

    exit(0);
} catch (Throwable \$throwable) {
    file_put_contents(\$resultFile, json_encode([
        'ok' => false,
        'worker' => \$workerName,
        'class' => \$throwable::class,
        'message' => \$throwable->getMessage().(\$throwable->getPrevious() ? ' | Prev: '.\$throwable->getPrevious()->getMessage() : ''),
    ], JSON_THROW_ON_ERROR));

    exit(1);
}
PHP;
    }

    /**
     * @return array{process: mixed, pipes: array<int, resource>}
     */
    private function startWorker(
        string $workerFile,
        string $startFile,
        string $resultDir,
        string $workerName,
        string $filePath,
        string $organizationId,
        string $importDate,
        string $fileSha256,
    ): array {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $startFile,
                $resultDir,
                $workerName,
                $filePath,
                $organizationId,
                $importDate,
                $fileSha256,
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
    private function finishWorker(array $worker, string $resultDir, string $workerName): array
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

        $resultFile = $resultDir.'/'.$workerName.'.json';
        $contents = file_exists($resultFile) ? file_get_contents($resultFile) : '';

        if ($contents === false || $contents === '') {
            return [
                'ok' => false,
                'worker' => $workerName,
                'class' => 'WorkerCrashed',
                'message' => "Worker [{$workerName}] did not write a result file. Exit code: {$exitCode}. Stderr: ".trim($stderr),
            ];
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! array_key_exists('ok', $decoded)) {
            return [
                'ok' => false,
                'worker' => $workerName,
                'class' => 'MalformedResult',
                'message' => "Worker [{$workerName}] wrote a malformed result. Contents: {$contents}",
            ];
        }

        return $decoded;
    }
}
