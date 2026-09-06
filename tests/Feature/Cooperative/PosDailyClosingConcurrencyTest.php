<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\Organization;
use App\Models\PosDailyClosing;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\PosDailyClosingService;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;

class PosDailyClosingConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    private const DATE = '2026-09-06';

    /** @var resource|null */
    private $worker = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    protected function beforeTruncatingDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires independent PostgreSQL sessions; SQLite cannot prove row-lock serialization. Run phpunit.pgsql.xml.');
        }

        // Check the effective database BEFORE the Laravel fixture trait may migrate/truncate.
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('kojaya_test', DB::selectOne('select current_database() as name')->name);
        $this->assertSame('read committed', DB::selectOne('show transaction_isolation')->transaction_isolation);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->worker)) {
            proc_terminate($this->worker);
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
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

    public static function mutexStates(): array
    {
        return ['existing mutex row' => [true], 'uncommitted new placeholder' => [false]];
    }

    #[DataProvider('mutexStates')]
    public function test_sale_holds_mutex_until_commit_and_waiting_closing_includes_sale(bool $existingMutex): void
    {
        [$org, $actor, $product] = $this->fixtures();
        $this->prepareMutex($org, $existingMutex);
        DB::beginTransaction();
        $sale = app(PosTransactionService::class)->create($this->saleData($product), $actor);
        $this->assertSame(1, DB::transactionLevel());

        $pid = $this->startWorker('close', $org, $actor, $product);
        $this->assertWaitingOnParent($pid, $org, $existingMutex);
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('closed', $result['outcome']);
        $this->assertDatabaseHas('pos_transactions', ['id' => $sale->id, 'status' => 'COMPLETED', 'organization_id' => $org->id]);
        $closing = PosDailyClosing::query()->findOrFail($result['id']);
        $this->assertTrue($closing->is_locked);
        $this->assertSame($org->id, $closing->organization_id);
        $this->assertSame(self::DATE, $closing->closing_date->toDateString());
        $this->assertSame(1, $closing->transaction_count);
        $this->assertSame(10000.0, (float) $closing->gross_sales);
        $this->assertSame(10000.0, (float) $closing->net_sales);
        $this->assertSame(10000.0, (float) $closing->payment_summary[0]['total']);
        $this->assertSame(1, PosDailyClosing::query()->where('organization_id', $org->id)->whereDate('closing_date', self::DATE)->count());
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'source_type' => PosDailyClosing::class, 'source_id' => $closing->id,
            'organization_id' => $org->id, 'entry_type' => 'POS_DAILY_CLOSING', 'credit' => 10000,
        ]);
        $this->evidence(['result' => 'sale committed; closing locked; count=1; gross=net=cash=10000', 'organization_id' => $org->id]);
    }

    #[DataProvider('mutexStates')]
    public function test_closing_holds_mutex_until_commit_and_waiting_sale_is_rejected(bool $existingMutex): void
    {
        [$org, $actor, $product] = $this->fixtures();
        $this->prepareMutex($org, $existingMutex);
        DB::beginTransaction();
        $closing = app(PosDailyClosingService::class)->closeDay(self::DATE, $actor, $org->id);
        $this->assertSame(1, DB::transactionLevel());

        $pid = $this->startWorker('sale', $org, $actor, $product);
        $this->assertWaitingOnParent($pid, $org, $existingMutex);
        DB::commit();

        $result = $this->readWorkerLine();
        $this->assertSame('rejected', $result['outcome']);
        $this->assertArrayHasKey('sold_at', $result['errors']);
        $this->assertStringContainsString('sudah ditutup', $result['errors']['sold_at'][0]);
        $this->assertTrue($closing->refresh()->is_locked);
        $this->assertSame($org->id, $closing->organization_id);
        $this->assertSame(0, $closing->transaction_count);
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $org->id)->count());
        $this->assertSame(100, $product->refresh()->stock);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('organization_id', $org->id)->count());
        $this->evidence(['result' => 'closing committed; competing sale rejected on sold_at; no sale/journal/stock change', 'organization_id' => $org->id]);
    }

    public function test_other_organization_closes_same_date_while_sale_mutex_is_held(): void
    {
        [$orgA, $actorA, $productA] = $this->fixtures();
        [$orgB, $actorB, $productB] = $this->fixtures();
        app(PosTransactionService::class)->create($this->saleData($productB), $actorB);

        DB::beginTransaction();
        app(PosTransactionService::class)->create($this->saleData($productA), $actorA);
        $this->startWorker('close', $orgB, $actorB, $productB);
        // Worker B must finish while A's real sale transaction is still open.
        $result = $this->readWorkerLine();
        $this->assertSame('closed', $result['outcome']);
        $this->assertSame(1, DB::transactionLevel());
        $closingB = PosDailyClosing::query()->findOrFail($result['id']);
        $this->assertSame($orgB->id, $closingB->organization_id);
        $this->assertTrue($closingB->is_locked);
        $this->assertSame(1, $closingB->transaction_count);
        $this->assertSame(10000.0, (float) $closingB->net_sales);
        $this->assertFalse(app(PosDailyClosingService::class)->isLocked(self::DATE, $orgA->id));
        DB::commit();
        $this->evidence(['result' => 'B closed before A released same-date sale mutex', 'organization_a' => $orgA->id, 'organization_b' => $orgB->id]);
    }

    /** @return array{Organization, User, PosProduct} */
    private function fixtures(): array
    {
        Permission::findOrCreate('view_pos_reports', 'web');
        Permission::findOrCreate('access_cooperative_pos', 'web');
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $actor->givePermissionTo(['view_pos_reports', 'access_cooperative_pos']);
        $product = PosProduct::factory()->create([
            'organization_id' => $org->id, 'sale_price' => 5000, 'cost_price' => 1000, 'stock' => 100,
        ]);
        app(PosInventoryService::class)->syncDefaultLocationStocks();

        return [$org, $actor, $product];
    }

    private function saleData(PosProduct $product): array
    {
        return [
            'sold_at' => self::DATE,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ];
    }

    private function prepareMutex(Organization $org, bool $existing): void
    {
        $this->assertFalse(PosDailyClosing::query()->where('organization_id', $org->id)->whereDate('closing_date', self::DATE)->exists());
        if ($existing) {
            DB::transaction(fn () => app(PosDailyClosingService::class)->acquireLockRow($org->id, self::DATE));
        }
    }

    private function startWorker(string $action, Organization $org, User $actor, PosProduct $product): int
    {
        $this->worker = proc_open([PHP_BINARY, base_path('tests/Support/pos-closing-worker.php')], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $this->pipes, base_path());
        $this->assertIsResource($this->worker);
        fwrite($this->pipes[0], json_encode([
            'connection' => DB::connection()->getConfig(), 'action' => $action,
            'organization_id' => $org->id, 'actor_id' => $actor->id,
            'date' => self::DATE, 'sale' => $this->saleData($product),
        ], JSON_THROW_ON_ERROR)."\n");
        $ready = $this->readWorkerLine();
        $this->assertSame('ready', $ready['outcome']);
        $this->assertNotSame((int) DB::selectOne('select pg_backend_pid() as pid')->pid, $ready['pid']);
        fwrite($this->pipes[0], "GO\n");

        return $ready['pid'];
    }

    private function assertWaitingOnParent(int $pid, Organization $org, bool $existing): void
    {
        $parentPid = (int) DB::selectOne('select pg_backend_pid() as pid')->pid;
        $deadline = microtime(true) + 10;
        do {
            // Database-observed wait, not elapsed time, is the synchronization barrier.
            DB::select('select pg_stat_clear_snapshot()');
            $wait = DB::selectOne(<<<'SQL'
                select wait_event_type, wait_event, query from pg_stat_activity
                where pid = ? and ? = any(pg_blocking_pids(pid)) and wait_event_type = 'Lock'
                SQL, [$pid, $parentPid]);
            if ($wait !== null) {
                $this->assertStringContainsString('pos_daily_closings', $wait->query);
                $this->assertStringContainsString($existing ? 'for update' : 'on conflict do nothing', $wait->query);
                $read = [$this->pipes[1]];
                $write = $except = null;
                $this->assertSame(0, stream_select($read, $write, $except, 0), 'Worker must not finish while TX-A owns the mutex.');
                $this->evidence([
                    'organization_id' => $org->id, 'date' => self::DATE, 'holder_pid' => $parentPid, 'waiter_pid' => $pid,
                    'wait_event_type' => $wait->wait_event_type, 'wait_event' => $wait->wait_event,
                    'mutex' => $existing ? 'existing row FOR UPDATE' : 'new placeholder ON CONFLICT DO NOTHING',
                ]);

                return;
            }
            usleep(10000); // Poll pacing only; success requires pg_blocking_pids evidence.
        } while (microtime(true) < $deadline);

        $this->fail('No PostgreSQL closing-mutex wait observed before deadline.');
    }

    private function readWorkerLine(): array
    {
        $read = [$this->pipes[1]];
        $write = $except = null;
        $this->assertSame(1, stream_select($read, $write, $except, 15), 'Worker pipe timed out.');
        $line = fgets($this->pipes[1]);
        $this->assertNotFalse($line, 'Worker exited without a protocol result.');

        return json_decode($line, true, flags: JSON_THROW_ON_ERROR);
    }

    private function evidence(array $details): void
    {
        fwrite(STDERR, "\nSEC-P1-06 ".json_encode($details, JSON_THROW_ON_ERROR)."\n");
    }
}
