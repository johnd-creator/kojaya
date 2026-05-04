<?php

namespace Tests\Feature\Cooperative;

use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosTransactionConcurrencyTest extends TestCase
{
    private string $workingDirectory;

    private string $databasePath;

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-pos-concurrency-'.bin2hex(random_bytes(8));
        mkdir($this->workingDirectory, 0777, true);

        $this->databasePath = $this->workingDirectory.'/testing.sqlite';
        touch($this->databasePath);

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $this->databasePath);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA busy_timeout = 5000');

        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        foreach (glob($this->workingDirectory.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->workingDirectory);

        parent::tearDown();
    }

    public function test_parallel_pos_requests_with_same_client_reference_resolve_to_single_transaction(): void
    {
        $cashier = User::factory()->create();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 6000,
            'sale_price' => 10000,
            'stock' => 5,
            'minimum_stock' => 1,
        ]);

        $payloadFile = $this->workingDirectory.'/payload.json';
        $startFile = $this->workingDirectory.'/start.signal';
        $workerFile = $this->workingDirectory.'/worker.php';

        file_put_contents($payloadFile, json_encode([
            'client_reference' => 'POS-PARALLEL-001',
            'payment_method' => 'CASH',
            'items' => [
                [
                    'pos_product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        file_put_contents($workerFile, <<<'PHP'
<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

[$script, $repoPath, $databasePath, $payloadPath, $startFile, $cashierId] = $argv;

while (! file_exists($startFile)) {
    usleep(10000);
}

putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('DB_CONNECTION=sqlite');
putenv("DB_DATABASE={$databasePath}");

$_ENV['APP_ENV'] = 'testing';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $databasePath;
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $databasePath;

require $repoPath.'/vendor/autoload.php';

$app = require $repoPath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config()->set('database.default', 'sqlite');
config()->set('database.connections.sqlite.database', $databasePath);

DB::purge('sqlite');
DB::reconnect('sqlite');
DB::statement('PRAGMA busy_timeout = 5000');

$payload = json_decode(file_get_contents($payloadPath), true, 512, JSON_THROW_ON_ERROR);
$cashier = User::query()->findOrFail((int) $cashierId);
$service = app(PosTransactionService::class);

try {
    $transaction = $service->create($payload, $cashier);

    fwrite(STDOUT, json_encode([
        'ok' => true,
        'transaction_id' => $transaction->id,
    ], JSON_THROW_ON_ERROR));

    exit(0);
} catch (Throwable $throwable) {
    fwrite(STDOUT, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ], JSON_THROW_ON_ERROR));

    exit(1);
}
PHP);

        $processes = [
            $this->startWorker($workerFile, $payloadFile, $startFile, $cashier->id),
            $this->startWorker($workerFile, $payloadFile, $startFile, $cashier->id),
        ];

        usleep(200000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0]),
            $this->finishWorker($processes[1]),
        ];

        $this->assertCount(2, $results);
        $this->assertSame([0, 0], array_column($results, 'exit_code'));
        $this->assertTrue($results[0]['payload']['ok']);
        $this->assertTrue($results[1]['payload']['ok']);
        $this->assertCount(1, array_unique(array_column(array_column($results, 'payload'), 'transaction_id')));

        $product->refresh();

        $this->assertSame(4, $product->stock);
        $this->assertSame(1, PosTransaction::query()->where('client_reference', 'POS-PARALLEL-001')->count());
        $this->assertSame(1, PosStockMovement::query()->where('pos_product_id', $product->id)->count());
        $this->assertSame(1, PosTransaction::query()->firstOrFail()->payments()->count());
        $this->assertSame(1, PosTransaction::query()->firstOrFail()->items()->count());
    }

    /**
     * @return array{process: resource, pipes: array<int, resource>}
     */
    private function startWorker(string $workerFile, string $payloadFile, string $startFile, int $cashierId): array
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $this->databasePath,
                $payloadFile,
                $startFile,
                (string) $cashierId,
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path(),
        );

        $this->assertIsResource($process);

        return [
            'process' => $process,
            'pipes' => $pipes,
        ];
    }

    /**
     * @param  array{process: resource, pipes: array<int, resource>}  $worker
     * @return array{exit_code: int, payload: array<string, mixed>, stderr: string}
     */
    private function finishWorker(array $worker): array
    {
        $stdout = stream_get_contents($worker['pipes'][1]);
        $stderr = stream_get_contents($worker['pipes'][2]);

        fclose($worker['pipes'][1]);
        fclose($worker['pipes'][2]);

        $exitCode = proc_close($worker['process']);

        return [
            'exit_code' => $exitCode,
            'payload' => json_decode($stdout ?: '{}', true, 512, JSON_THROW_ON_ERROR),
            'stderr' => $stderr ?: '',
        ];
    }
}
