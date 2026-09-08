<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PosVoidConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    /** @var resource|null */
    private $worker = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    protected function beforeTruncatingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            self::fail('PosVoidConcurrencyTest REQUIRES independent PostgreSQL sessions (DB_CONNECTION=pgsql). Run phpunit.pgsql.xml.');
        }

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('kojaya_test', DB::selectOne('select current_database() as name')->name);
        $this->assertSame('read committed', DB::selectOne('show transaction_isolation')->transaction_isolation);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->worker)) {
            proc_terminate($this->worker);
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($this->worker);
        }
        if ($this->app !== null) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        parent::tearDown();
    }

    /**
     * PG-01: Double approve concurrency test.
     * Two simultaneous approvals against the same pending void request.
     * Exactly one reversal must execute.
     */
    public function test_pg_01_double_approval_executes_exactly_once(): void
    {
        [$org, $cashier, $supervisor, $product] = $this->fixtures();
        $tx = $this->createCompletedTransaction($org, $cashier, $product, 2);
        $this->assertSame(8, (int) $product->fresh()->stock);

        $voidRequest = PosVoidRequest::query()->create([
            'pos_transaction_id' => $tx->id,
            'requested_by' => $cashier->id,
            'reason' => 'Customer requested cancellation',
            'status' => PosVoidRequest::STATUS_PENDING,
        ]);
        $tx->update(['status' => 'VOID_PENDING']);

        DB::beginTransaction();
        // Hold row lock on the void request
        PosVoidRequest::query()->lockForUpdate()->findOrFail($voidRequest->id);

        $pid = $this->startWorker('approve', $supervisor, ['void_request_id' => $voidRequest->id]);
        $this->assertWaitingOnParent($pid, 'pos_void_requests');

        // Parent performs the approval
        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('validation_error', $result['outcome']);
        $this->assertArrayHasKey('request', $result['errors']);
        $this->assertStringContainsString('sudah diproses', $result['errors']['request'][0]);

        // Assert exact single reversal
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame('VOIDED', $tx->fresh()->status);
        $this->assertSame(PosVoidRequest::STATUS_APPROVED, $voidRequest->fresh()->status);
        $this->assertSame(1, PosVoidRequest::query()->where('status', PosVoidRequest::STATUS_APPROVED)->count());
        $this->assertSame(2, PosStockMovement::query()->where('pos_product_id', $product->id)->count());
    }

    /**
     * PG-02: Approve vs Reject concurrency race.
     * Competing terminal decisions must yield a consistent terminal state with zero data corruption.
     */
    public function test_pg_02_approve_vs_reject_concurrent_decisions_yield_consistent_terminal_state(): void
    {
        [$org, $cashier, $supervisor, $product] = $this->fixtures();
        $tx = $this->createCompletedTransaction($org, $cashier, $product, 2);
        $this->assertSame(8, (int) $product->fresh()->stock);

        $voidRequest = PosVoidRequest::query()->create([
            'pos_transaction_id' => $tx->id,
            'requested_by' => $cashier->id,
            'reason' => 'Testing approve vs reject race',
            'status' => PosVoidRequest::STATUS_PENDING,
        ]);
        $tx->update(['status' => 'VOID_PENDING']);

        DB::beginTransaction();
        // Hold row lock on the void request
        PosVoidRequest::query()->lockForUpdate()->findOrFail($voidRequest->id);

        $pid = $this->startWorker('reject', $supervisor, ['void_request_id' => $voidRequest->id]);
        $this->assertWaitingOnParent($pid, 'pos_void_requests');

        // Parent performs approve
        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('validation_error', $result['outcome']);
        $this->assertArrayHasKey('request', $result['errors']);
        $this->assertStringContainsString('sudah diproses', $result['errors']['request'][0]);

        // State remains consistently approved
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame('VOIDED', $tx->fresh()->status);
        $this->assertSame(PosVoidRequest::STATUS_APPROVED, $voidRequest->fresh()->status);
    }

    /**
     * PG-03: Concurrent duplicate void requests.
     * Two simultaneous requests against one completed transaction must yield at most one pending void request.
     */
    public function test_pg_03_concurrent_void_requests_produce_at_most_one_pending_request(): void
    {
        [$org, $cashier, $supervisor, $product] = $this->fixtures();
        $tx = $this->createCompletedTransaction($org, $cashier, $product, 1);

        DB::beginTransaction();
        // Hold row lock on the transaction
        PosTransaction::query()->lockForUpdate()->findOrFail($tx->id);

        $pid = $this->startWorker('request_void', $cashier, ['transaction_id' => $tx->id]);
        $this->assertWaitingOnParent($pid, 'pos_transactions');

        // Parent submits void request
        app(PosTransactionService::class)->requestVoid($tx, $cashier, 'Parent void request');
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('validation_error', $result['outcome']);
        $this->assertArrayHasKey('transaction', $result['errors']);
        $this->assertStringContainsString('menunggu persetujuan', $result['errors']['transaction'][0]);

        // Assert at most one pending void request exists
        $this->assertSame(1, PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->count());
        $this->assertSame(1, PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->where('status', PosVoidRequest::STATUS_PENDING)->count());
        $this->assertSame('VOID_PENDING', $tx->fresh()->status);
    }

    /**
     * @return array{Organization, User, User, PosProduct}
     */
    private function fixtures(): array
    {
        Permission::findOrCreate('access_cooperative_pos', 'web');
        Permission::findOrCreate('approve_pos_void', 'web');

        $org = Organization::factory()->create(['name' => 'Koperasi Concurrency Org']);
        $cashier = User::factory()->create(['organization_id' => $org->id, 'name' => 'Concurrency Cashier']);
        $cashier->givePermissionTo('access_cooperative_pos');

        $supervisor = User::factory()->create(['organization_id' => $org->id, 'name' => 'Concurrency Supervisor']);
        $supervisor->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        $category = PosCategory::factory()->create(['organization_id' => $org->id]);
        $product = PosProduct::factory()->create([
            'organization_id' => $org->id,
            'pos_category_id' => $category->id,
            'sale_price' => 10000,
            'cost_price' => 5000,
            'stock' => 10,
        ]);

        app(PosInventoryService::class)->syncDefaultLocationStocks();

        return [$org, $cashier, $supervisor, $product];
    }

    private function createCompletedTransaction(Organization $org, User $cashier, PosProduct $product, int $quantity): PosTransaction
    {
        return app(PosTransactionService::class)->create([
            'client_reference' => 'TX-CONCURRENCY-'.uniqid(),
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => $quantity],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000 * $quantity, 'cash_received' => 10000 * $quantity],
            ],
        ], $cashier);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function startWorker(string $action, User $actor, array $params = []): int
    {
        $this->worker = proc_open([PHP_BINARY, base_path('tests/Support/pos-void-worker.php')], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $this->pipes, base_path());

        $this->assertIsResource($this->worker);

        fwrite($this->pipes[0], json_encode([
            'connection' => DB::connection()->getConfig(),
            'action' => $action,
            'actor_id' => $actor->id,
            ...$params,
        ], JSON_THROW_ON_ERROR)."\n");

        $ready = $this->readWorkerLine();
        $this->assertSame('ready', $ready['outcome']);
        $this->assertNotSame((int) DB::selectOne('select pg_backend_pid() as pid')->pid, $ready['pid']);
        fwrite($this->pipes[0], "GO\n");

        return $ready['pid'];
    }

    private function assertWaitingOnParent(int $pid, string $targetTable): void
    {
        $parentPid = (int) DB::selectOne('select pg_backend_pid() as pid')->pid;
        $deadline = microtime(true) + 10;
        do {
            DB::select('select pg_stat_clear_snapshot()');
            $wait = DB::selectOne(<<<'SQL'
                select wait_event_type, wait_event, query from pg_stat_activity
                where pid = ? and ? = any(pg_blocking_pids(pid)) and wait_event_type = 'Lock'
                SQL, [$pid, $parentPid]);
            if ($wait !== null) {
                $this->assertStringContainsString($targetTable, $wait->query);
                $read = [$this->pipes[1]];
                $write = $except = null;
                $this->assertSame(0, stream_select($read, $write, $except, 0), 'Worker must not finish while parent holds the lock.');

                return;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);

        $this->fail("No PostgreSQL lock wait on table {$targetTable} observed before deadline.");
    }

    /**
     * @return array<string, mixed>
     */
    private function readWorkerLine(): array
    {
        $read = [$this->pipes[1]];
        $write = $except = null;
        $this->assertSame(1, stream_select($read, $write, $except, 15), 'Worker pipe timed out.');
        $line = fgets($this->pipes[1]);
        $this->assertNotFalse($line, 'Worker exited without a protocol result.');

        return json_decode($line, true, flags: JSON_THROW_ON_ERROR);
    }
}
