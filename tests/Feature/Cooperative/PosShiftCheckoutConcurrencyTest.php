<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\Organization;
use App\Models\PosCashierShift;
use App\Models\PosCategory;
use App\Models\PosInventoryStock;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Services\Cooperative\PosCashierShiftService;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PosShiftCheckoutConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    /** @var resource|null */
    private $worker = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    protected function beforeTruncatingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            self::fail('PosShiftCheckoutConcurrencyTest REQUIRES independent PostgreSQL sessions (DB_CONNECTION=pgsql). Run phpunit.pgsql.xml.');
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

    public function test_checkout_lock_first_is_included_in_shift_close_summary(): void
    {
        [$organization, $cashier, $product, $shift] = $this->fixtures();

        DB::beginTransaction();
        $sale = app(PosTransactionService::class)->create($this->saleData($product, $shift), $cashier);
        $this->assertSame(1, DB::transactionLevel());

        $pid = $this->startWorker('close', $cashier, $shift, $product);
        $this->assertWaitingOnParent($pid, 'pos_cashier_shifts');
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('closed', $result['outcome']);
        $this->assertDatabaseHas('pos_transactions', [
            'id' => $sale->id,
            'organization_id' => $organization->id,
            'status' => 'COMPLETED',
        ]);

        $closed = $shift->fresh();
        $this->assertSame(PosCashierShift::STATUS_CLOSED, $closed->status);
        $this->assertSame(1, (int) $closed->transaction_count);
        $this->assertSame(5000.0, (float) $closed->total_sales);
        $this->assertSame(5000.0, (float) $closed->total_cash_sales);
        $this->assertSame(105000.0, (float) $closed->expected_cash);
        $this->assertSame(100000.0, (float) $closed->closing_cash);
        $this->assertSame(-5000.0, (float) $closed->cash_difference);
        $this->assertSame(1, PosTransaction::query()->where('pos_cashier_shift_id', $shift->id)->count());
    }

    public function test_shift_close_lock_first_rejects_waiting_checkout_without_mutation(): void
    {
        [$organization, $cashier, $product, $shift] = $this->fixtures();

        DB::beginTransaction();
        PosCashierShift::query()->lockForUpdate()->findOrFail($shift->id);

        $pid = $this->startWorker('sale', $cashier, $shift, $product);
        $this->assertWaitingOnParent($pid, 'pos_cashier_shifts');
        $closed = app(PosCashierShiftService::class)->closeShift($shift, 100000);
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('validation_error', $result['outcome']);
        $this->assertArrayHasKey('pos_cashier_shift_id', $result['errors']);
        $this->assertSame(PosCashierShift::STATUS_CLOSED, $closed->status);
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $organization->id)->count());
        $this->assertSame(0, PosTransactionItem::query()->count());
        $this->assertSame(0, PosPayment::query()->count());
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('organization_id', $organization->id)->count());
    }

    public function test_two_postgres_checkouts_competing_for_one_unit_create_one_sale_only(): void
    {
        [$organization, $cashier, $product, $shift] = $this->fixtures();
        $product->forceFill(['stock' => 1])->save();
        PosInventoryStock::query()->where('pos_product_id', $product->id)->update([
            'quantity' => 1,
            'reserved' => 0,
        ]);

        $workers = [
            $this->launchConcurrentSaleWorker($cashier, $shift, $product),
            $this->launchConcurrentSaleWorker($cashier, $shift, $product),
        ];

        foreach ($workers as $worker) {
            fwrite($worker['pipes'][0], "GO\n");
        }

        $results = [];
        foreach ($workers as $worker) {
            $results[] = $this->readConcurrentWorkerLine($worker['pipes'][1]);
            fclose($worker['pipes'][0]);
            fclose($worker['pipes'][1]);
            fclose($worker['pipes'][2]);
            proc_close($worker['process']);
        }

        $this->assertSame(1, count(array_filter($results, fn (array $result): bool => $result['outcome'] === 'created')));
        $this->assertSame(1, count(array_filter($results, fn (array $result): bool => $result['outcome'] === 'validation_error')));
        $this->assertSame(0, count(array_filter($results, fn (array $result): bool => $result['outcome'] === 'error')));

        $this->assertSame(0, (int) $product->fresh()->stock);
        $transaction = PosTransaction::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'COMPLETED')
            ->sole();

        $this->assertSame(1, PosTransaction::query()->where('organization_id', $organization->id)->where('status', 'COMPLETED')->count());
        $this->assertSame(1, PosTransactionItem::query()->count());
        $this->assertSame(1, PosPayment::query()->count());
        $this->assertSame(1, \App\Models\PosStockMovement::query()->where('pos_product_id', $product->id)->where('movement_type', 'SALE')->count());
        $ledgerEntries = CooperativeLedgerEntry::query()
            ->where('organization_id', $organization->id)
            ->get();

        $this->assertCount(2, $ledgerEntries);
        $this->assertEqualsCanonicalizing(
            ['POS_SALE', 'POS_COGS'],
            $ledgerEntries->pluck('entry_type')->all(),
        );
        $this->assertSame([$transaction->id], $ledgerEntries->pluck('source_id')->unique()->values()->all());
    }

    /** @return array{Organization, User, PosProduct, PosCashierShift} */
    private function fixtures(): array
    {
        Permission::findOrCreate('access_cooperative_pos', 'web');
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->givePermissionTo('access_cooperative_pos');
        $category = PosCategory::factory()->create(['organization_id' => $organization->id]);
        $product = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'pos_category_id' => $category->id,
            'sale_price' => 5000,
            'cost_price' => 2500,
            'stock' => 10,
        ]);
        app(PosInventoryService::class)->syncDefaultLocationStocks();
        $shift = app(PosCashierShiftService::class)->openShift($cashier, 100000);

        return [$organization, $cashier, $product, $shift];
    }

    private function saleData(PosProduct $product, PosCashierShift $shift): array
    {
        return [
            'client_reference' => 'POS008-CONCURRENCY-'.uniqid(),
            'pos_cashier_shift_id' => $shift->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
            'cash_received' => 5000,
        ];
    }

    private function startWorker(string $action, User $cashier, PosCashierShift $shift, PosProduct $product): int
    {
        $this->worker = proc_open([PHP_BINARY, base_path('tests/Support/pos-shift-worker.php')], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $this->pipes, base_path());
        $this->assertIsResource($this->worker);

        fwrite($this->pipes[0], json_encode([
            'connection' => DB::connection()->getConfig(),
            'action' => $action,
            'actor_id' => $cashier->id,
            'shift_id' => $shift->id,
            'closing_cash' => 100000,
            'sale' => $this->saleData($product, $shift),
        ], JSON_THROW_ON_ERROR)."\n");

        $ready = $this->readWorkerLine();
        $this->assertSame('ready', $ready['outcome']);
        $this->assertNotSame((int) DB::selectOne('select pg_backend_pid() as pid')->pid, $ready['pid']);
        fwrite($this->pipes[0], "GO\n");

        return $ready['pid'];
    }

    /** @return array{process: resource, pipes: array<int, resource>} */
    private function launchConcurrentSaleWorker(User $cashier, PosCashierShift $shift, PosProduct $product): array
    {
        $pipes = [];
        $process = proc_open([PHP_BINARY, base_path('tests/Support/pos-shift-worker.php')], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, base_path());
        $this->assertIsResource($process);

        fwrite($pipes[0], json_encode([
            'connection' => DB::connection()->getConfig(),
            'action' => 'sale',
            'actor_id' => $cashier->id,
            'shift_id' => $shift->id,
            'closing_cash' => 100000,
            'sale' => $this->saleData($product, $shift),
        ], JSON_THROW_ON_ERROR)."\n");

        $ready = $this->readConcurrentWorkerLine($pipes[1]);
        $this->assertSame('ready', $ready['outcome']);
        $this->assertNotSame((int) DB::selectOne('select pg_backend_pid() as pid')->pid, $ready['pid']);

        return ['process' => $process, 'pipes' => $pipes];
    }

    /** @return array<string, mixed> */
    private function readConcurrentWorkerLine($pipe): array
    {
        $read = [$pipe];
        $write = $except = null;
        $this->assertSame(1, stream_select($read, $write, $except, 20), 'Concurrent POS worker pipe timed out.');
        $line = fgets($pipe);
        $this->assertNotFalse($line, 'Concurrent POS worker exited without a protocol result.');

        return json_decode($line, true, flags: JSON_THROW_ON_ERROR);
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

    /** @return array<string, mixed> */
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
