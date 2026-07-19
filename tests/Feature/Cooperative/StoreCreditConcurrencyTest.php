<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * True concurrency test: multiple PHP processes race store-credit purchases
 * against a single account with a finite credit limit using PostgreSQL
 * row-level locking. The signed balance must never fall below -credit_limit,
 * proving overspend is impossible under real concurrency.
 *
 * Requires PostgreSQL (DB_CONNECTION=pgsql); skipped under SQLite.
 */
class StoreCreditConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    /** @var array<string, string> */
    private array $dbConfig = [];

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        $originalConnection = getenv('DB_CONNECTION') ?: 'sqlite';

        if ($originalConnection !== 'pgsql') {
            parent::setUp();
            $this->markTestSkipped('StoreCreditConcurrencyTest requires PostgreSQL (DB_CONNECTION=pgsql).');

            return;
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

        parent::setUp();

        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', $this->dbConfig);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-store-credit-concurrency-'.bin2hex(random_bytes(8));
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

    public function test_concurrent_purchases_never_exceed_credit_limit(): void
    {
        $org = Organization::factory()->create();
        $opener = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $account = $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: $org->id,
            cooperativeMemberId: $member->id,
            creditLimit: 100000,
            openingBalance: 0,
            openedBy: $opener,
        ));

        $workerFile = $this->workingDirectory.'/worker.php';
        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        file_put_contents($workerFile, $this->workerScript());

        $workerCount = 6;
        $purchaseAmount = 25000; // limit 100000 => at most 4 succeed
        $processes = [];

        for ($i = 0; $i < $workerCount; $i++) {
            $processes[] = $this->startWorker($workerFile, $startFile, $resultDir, $i, $account->id, $purchaseAmount);
        }

        usleep(300000);
        touch($startFile);

        $results = [];
        foreach ($processes as $i => $worker) {
            $results[] = $this->finishWorker($worker, $resultDir, $i);
        }

        $successes = array_filter($results, fn (array $r): bool => $r['ok']);
        $failures = array_filter($results, fn (array $r): bool => ! $r['ok']);

        $finalBalance = (int) DB::connection('pgsql')->table('member_store_accounts')->where('id', $account->id)->value('balance');
        $postedEntries = (int) DB::connection('pgsql')->table('member_store_ledger_entries')
            ->where('account_id', $account->id)
            ->where('entry_type', 'pos_purchase')
            ->count();

        // Balance must never breach the credit limit floor.
        $this->assertGreaterThanOrEqual(-100000, $finalBalance, "Balance [{$finalBalance}] breached the -100000 credit floor.");
        // Total successful debits must equal the number of posted purchase entries × amount.
        $this->assertSame(count($successes), $postedEntries, 'Each successful worker must post exactly one ledger entry.');
        // Expected outcome: exactly 4 succeed (4 × 25000 = 100000), 2 rejected over-limit.
        $this->assertSame(4, count($successes), 'Exactly 4 concurrent purchases should fit within the 100000 credit limit.');
        $this->assertCount(2, $failures, 'Exactly 2 purchases should be rejected over-limit by row locking.');
        $this->assertSame(-100000, $finalBalance);
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

use App\Models\MemberStoreAccount;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use Illuminate\Contracts\Console\Kernel;

[\$script, \$repoPath, \$startFile, \$resultDir, \$index, \$accountId, \$amount] = \$argv;

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

\$resultFile = \$resultDir.'/'.\$index.'.json';

try {
    \$account = MemberStoreAccount::query()->findOrFail((int) \$accountId);
    \$actor = User::query()->first() ?? User::factory()->create();
    \$transaction = PosTransaction::query()->create([
        'transaction_no' => 'CONC-'.\$index.'-'.uniqid(),
        'subtotal' => \$amount,
        'discount_amount' => 0,
        'total_amount' => \$amount,
        'status' => 'COMPLETED',
        'sold_at' => date('Y-m-d'),
    ]);

    app(StoreCreditLedgerService::class)->postPurchase(\$account, \$transaction, (int) \$amount, \$actor, null);

    file_put_contents(\$resultFile, json_encode([
        'ok' => true,
        'index' => \$index,
    ], JSON_THROW_ON_ERROR));

    exit(0);
} catch (Throwable \$throwable) {
    file_put_contents(\$resultFile, json_encode([
        'ok' => false,
        'index' => \$index,
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
    private function startWorker(string $workerFile, string $startFile, string $resultDir, int $index, int $accountId, int $amount): array
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $startFile,
                $resultDir,
                (string) $index,
                (string) $accountId,
                (string) $amount,
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
    private function finishWorker(array $worker, string $resultDir, int $index): array
    {
        $stderr = '';
        if (is_resource($worker['pipes'][2] ?? null)) {
            $stderr = (string) stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1] ?? null)) {
            fclose($worker['pipes'][1]);
        }

        $exitCode = is_resource($worker['process'] ?? null) ? proc_close($worker['process']) : 0;

        $resultFile = $resultDir.'/'.$index.'.json';
        $contents = file_exists($resultFile) ? file_get_contents($resultFile) : '';

        if ($contents === false || $contents === '') {
            return [
                'ok' => false,
                'index' => $index,
                'class' => 'WorkerCrashed',
                'message' => "Worker [{$index}] exit {$exitCode}. Stderr: ".trim($stderr),
            ];
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}
