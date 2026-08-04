<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\CooperativeNotificationOutbox;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PaymentReconciliationIncident;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * True PostgreSQL concurrency tests C1-C8 from the Payment and Reservation
 * State Machine specification (Document 02).
 *
 * Each test spawns separate PHP processes against the same PostgreSQL
 * database with barrier synchronization to exercise real race conditions.
 *
 * Requires DB_CONNECTION=pgsql; skipped otherwise.
 */
class PaymentConcurrencyTest extends TestCase
{
    private string $workingDirectory = '';

    /** @var array<string, string> */
    private array $dbConfig = [];

    /**
     * Capture the DB connection BEFORE any test's setUp() runs.
     * Tests\TestCase::setUp() forces putenv('DB_CONNECTION=sqlite') which
     * would clobber the pgsql value for subsequent tests.
     */
    private static string $requiredConnection = '';

    /**
     * @return list<string>
     */
    public static function setUpBeforeClass(): void
    {
        self::$requiredConnection = getenv('DB_CONNECTION') ?: 'sqlite';
    }

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        if (self::$requiredConnection !== 'pgsql') {
            self::fail(
                'PaymentConcurrencyTest REQUIRES PostgreSQL. Got DB_CONNECTION='.self::$requiredConnection
                .'. Use: vendor/bin/phpunit --configuration phpunit.pgsql.xml tests/Feature/PaymentConcurrencyTest.php'
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

        /*
         * Do NOT call Tests\TestCase::setUp() — it forces SQLite via
         * putenv('DB_CONNECTION=sqlite') which clobbers our pgsql env
         * for both this process and child worker processes.
         *
         * Instead, bootstrap the Laravel app directly.
         */
        $this->refreshApplication();

        // Re-assert pgsql env for this process and any child workers
        putenv('DB_CONNECTION=pgsql');
        putenv('DB_DATABASE='.$this->dbConfig['database']);
        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_DATABASE'] = $this->dbConfig['database'];
        $_SERVER['DB_CONNECTION'] = 'pgsql';
        $_SERVER['DB_DATABASE'] = $this->dbConfig['database'];

        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', $this->dbConfig);
        config(['services.midtrans.server_key' => '']);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $this->workingDirectory = sys_get_temp_dir().'/kojaya-payment-concurrency-'.bin2hex(random_bytes(8));
        mkdir($this->workingDirectory, 0777, true);

        Artisan::call('migrate:fresh', [
            '--database' => 'pgsql',
            '--force' => true,
        ]);

        $this->seed(RolePermissionSeeder::class);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        if ($this->workingDirectory !== '') {
            $resultDir = $this->workingDirectory.'/results';
            $this->cleanDir($resultDir);

            foreach (glob($this->workingDirectory.'/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($this->workingDirectory);
        }

        parent::tearDown();
    }

    // ── C1: 8 parallel same-key same-payload → one intent ─────────────

    public function test_c1_parallel_same_key_same_payload_one_intent(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $workerCount = 32;
        $processes = [];

        for ($i = 0; $i < $workerCount; $i++) {
            $processes[] = $this->startWorker(
                $this->writeWorkerScript('c1', [
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                ]),
                $startFile,
                $resultDir,
                "c1-{$i}",
                [
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                ],
            );
        }

        usleep(300000);
        touch($startFile);

        $results = [];
        foreach ($processes as $i => $proc) {
            $results[] = $this->finishWorker($proc, $resultDir, "c1-{$i}");
        }

        // All 32 workers must produce a result
        $this->assertCount($workerCount, $results, 'C1: all 32 workers produced a result');

        $successes = array_filter($results, fn (array $r): bool => $r['ok'] ?? false);

        // All successful responses must point to the same intent
        $intentIds = array_column($successes, 'intent_id');
        $this->assertCount(1, array_unique($intentIds), 'C1: exactly one unique intent ID across all workers');

        // Exactly one intent must exist
        $this->assertSame(1, MemberPaymentIntent::count(), 'C1: exactly one intent');

        // Exactly one reservation for the product
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 2,
        ], 'pgsql');

        // Exactly one reservation.created audit (not <= 1, must be === 1)
        $createdAudits = AuditLog::query()
            ->where('action', 'reservation.created')
            ->where('subject_type', MemberPaymentIntent::class)
            ->count();
        $this->assertSame(1, $createdAudits, 'C1: exactly one reservation.created audit');
    }

    // ── C2: Parallel same-key different-payload → winner + 409 ────────

    public function test_c2_parallel_same_key_different_payload_winner_and_conflict(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        // Worker A: qty=2 (20000)
        file_put_contents($this->workingDirectory.'/payload-a.json', json_encode([
            'member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000]],
        ]));

        // Worker B: qty=5 (50000) — different payload
        file_put_contents($this->workingDirectory.'/payload-b.json', json_encode([
            'member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000]],
        ]));

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c2', ['payload_file' => $this->workingDirectory.'/payload-a.json']),
                $startFile, $resultDir, 'c2-a',
                ['payload_file' => $this->workingDirectory.'/payload-a.json'],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c2', ['payload_file' => $this->workingDirectory.'/payload-b.json']),
                $startFile, $resultDir, 'c2-b',
                ['payload_file' => $this->workingDirectory.'/payload-b.json'],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c2-a'),
            $this->finishWorker($processes[1], $resultDir, 'c2-b'),
        ];

        $successes = array_filter($results, fn (array $r): bool => $r['ok']);
        $failures = array_filter($results, fn (array $r): bool => ! $r['ok']);

        $this->assertCount(1, $successes, 'C2: exactly one winner');
        $this->assertCount(1, $failures, 'C2: exactly one conflict');
        $this->assertSame(1, MemberPaymentIntent::count(), 'C2: exactly one intent');

        $intent = MemberPaymentIntent::first();
        $metadataItems = $intent->metadata['items'] ?? [];
        $this->assertCount(1, $metadataItems);

        // Winner's reservation must match metadata
        $reservedQty = array_sum(array_column($metadataItems, 'quantity'));
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => $reservedQty,
        ], 'pgsql');
    }

    // ── C3: Reuse settled key in parallel → all conflict ──────────────

    public function test_c3_parallel_settled_key_conflicts(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        // Create settled intent
        MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C3-SETTLED',
            'request_fingerprint' => hash('sha256', 'settled-fp'),
            'amount' => 10000,
            'channel' => 'QRIS',
            'gateway_status' => 'PAID',
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'SETTLED',
            'settled_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c3-resolve', [
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                ]),
                $startFile, $resultDir, 'c3-a',
                ['member_id' => $member->id, 'product_id' => $product->id],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c3-resolve', [
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                ]),
                $startFile, $resultDir, 'c3-b',
                ['member_id' => $member->id, 'product_id' => $product->id],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c3-a'),
            $this->finishWorker($processes[1], $resultDir, 'c3-b'),
        ];

        // All must fail with conflict
        foreach ($results as $r) {
            $this->assertFalse($r['ok'], 'C3: settled key must conflict');
        }

        // Only the pre-existing settled intent
        $this->assertSame(1, MemberPaymentIntent::count(), 'C3: no new intent');
    }

    // ── C4: Barrier-synchronized PAID vs expiry → one valid path ──────

    public function test_c4_barrier_paid_vs_expiry_one_valid_path(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        // Create intent with a charge (gateway_reference set)
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C4-BARRIER',
            'request_fingerprint' => hash('sha256', 'c4-fp'),
            'amount' => 10000,
            'channel' => 'QRIS',
            'gateway_reference' => 'MPI-C4-'.bin2hex(random_bytes(6)),
            'gateway_status' => 'PENDING',
            'gateway_payload' => ['reference' => 'MPI-C4-'.bin2hex(random_bytes(6))],
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->subMinute(), // already expired for expiry worker
            'metadata' => [
                'items' => [[
                    'pos_product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => '10000.00',
                    'line_total' => '20000.00',
                    'reservation_location_id' => '1',
                ]],
            ],
        ]);

        $gatewayRef = $intent->gateway_reference;

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c4-paid', ['gateway_reference' => $gatewayRef]),
                $startFile, $resultDir, 'c4-paid',
                ['gateway_reference' => $gatewayRef],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c4-expiry', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c4-expiry',
                ['intent_id' => $intent->id],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c4-paid'),
            $this->finishWorker($processes[1], $resultDir, 'c4-expiry'),
        ];

        // Both workers must have executed
        $this->assertCount(2, $results, 'C4: both workers produced a result');

        $intent->refresh();

        // Cannot have PAID + EXPIRED simultaneously
        $this->assertFalse(
            $intent->gatewayStatus()->value === 'PAID'
            && $intent->reservationStatus()->value === 'EXPIRED',
            'C4: no illegal PAID+EXPIRED combination'
        );
        $this->assertFalse(
            $intent->gatewayStatus()->value === 'PAID'
            && $intent->reservationStatus()->value === 'RELEASED',
            'C4: no illegal PAID+RELEASED combination'
        );

        // If expiry won and PAID came after, a reconciliation incident must exist
        if ($intent->gatewayStatus()->value === 'EXPIRED') {
            $this->assertDatabaseHas('payment_reconciliation_incidents', [
                'member_payment_intent_id' => $intent->id,
            ], 'pgsql');
        }

        // State combination must be valid
        $this->assertTrue($intent->isStateCombinationValid(), 'C4: state combination valid');
    }

    // ── C5: Parallel duplicate PAID webhooks → one transaction ────────

    public function test_c5_parallel_duplicate_paid_webhooks_one_transaction(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        // Set up inventory location + stock so settlement can create POS transaction
        $inventory = app(\App\Services\Cooperative\PosInventoryService::class);
        $location = $inventory->ensureDefaultLocation();
        $inventory->syncDefaultLocationStocks($location->id);

        $gatewayRef = 'MPI-C5-'.bin2hex(random_bytes(6));

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C5-DUP-PAID',
            'request_fingerprint' => hash('sha256', 'c5-fp'),
            'amount' => 20000,
            'channel' => 'QRIS',
            'gateway_reference' => $gatewayRef,
            'gateway_status' => 'PENDING',
            'gateway_payload' => ['reference' => $gatewayRef, 'status' => 'PENDING'],
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'items' => [[
                    'pos_product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => '10000.00',
                    'line_total' => '20000.00',
                    'reservation_location_id' => (string) $location->id,
                ]],
            ],
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c5-paid', ['gateway_reference' => $gatewayRef]),
                $startFile, $resultDir, 'c5-a',
                ['gateway_reference' => $gatewayRef],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c5-paid', ['gateway_reference' => $gatewayRef]),
                $startFile, $resultDir, 'c5-b',
                ['gateway_reference' => $gatewayRef],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c5-a'),
            $this->finishWorker($processes[1], $resultDir, 'c5-b'),
        ];

        // Both workers must have executed and returned a result
        $this->assertCount(2, $results, 'C5: both workers produced a result');

        $intent->refresh();

        // Must have exactly one POS transaction (settlement processed once)
        $this->assertSame(1, PosTransaction::count(), 'C5: exactly one transaction');
        $this->assertSame('PAID', $intent->gatewayStatus()->value, 'C5: intent is PAID');
        $this->assertSame(
            MemberPaymentIntent::RESERVATION_CONSUMED,
            $intent->reservationStatus()->value,
            'C5: reservation consumed'
        );

        // Exactly one consume audit (not multiple from duplicate webhooks)
        $consumeAudits = AuditLog::query()
            ->where('action', 'reservation.consumed')
            ->where('subject_type', MemberPaymentIntent::class)
            ->count();
        $this->assertSame(1, $consumeAudits, 'C5: exactly one reservation consumed');

        $transaction = PosTransaction::query()->firstOrFail();
        $settlementOutbox = CooperativeNotificationOutbox::query()
            ->where('deduplication_key', "member.pos.sale_completed:{$transaction->id}")
            ->firstOrFail();
        $this->assertSame(
            1,
            CooperativeNotificationOutbox::query()
                ->where('deduplication_key', "member.pos.sale_completed:{$transaction->id}")
                ->count(),
            'C5: exactly one settlement outbox row'
        );

        $notificationCount = $user->notifications()
            ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
            ->where('id', $settlementOutbox->id)
            ->count();
        $this->assertSame(1, $notificationCount, 'C5: exactly one delivered member notification');
    }

    // ── C6: Parallel charge calls → one provider reference ────────────

    public function test_c6_parallel_charge_calls_one_provider_reference(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        // Create an intent with reservation but NO charge yet
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C6-CHARGE-RACE',
            'request_fingerprint' => hash('sha256', 'c6-fp'),
            'amount' => 10000,
            'channel' => 'QRIS',
            'gateway_reference' => null,
            'gateway_status' => 'PENDING',
            'gateway_payload' => null,
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'items' => [[
                    'pos_product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => '10000.00',
                    'line_total' => '20000.00',
                    'reservation_location_id' => '1',
                ]],
            ],
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        // Shared counter file to track provider create-call count
        $counterFile = $this->workingDirectory.'/c6-create-counter.txt';
        file_put_contents($counterFile, '0');

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c6-charge', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c6-a',
                ['intent_id' => $intent->id, 'counter_file' => $counterFile],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c6-charge', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c6-b',
                ['intent_id' => $intent->id, 'counter_file' => $counterFile],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c6-a'),
            $this->finishWorker($processes[1], $resultDir, 'c6-b'),
        ];

        $intent->refresh();

        // Assert exact provider create-call count === 1
        $createCallCount = (int) trim(file_get_contents($counterFile) ?: '0');
        $this->assertSame(1, $createCallCount, 'C6: provider create-call count must be exactly 1');

        // Assert exact attempt count === 1
        $attemptCount = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->count();
        $this->assertSame(1, $attemptCount, 'C6: exactly one charge attempt');

        // Assert exact confirmed attempt count === 1
        $confirmed = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('state', MemberPaymentChargeAttempt::STATE_CONFIRMED)
            ->count();
        $this->assertSame(1, $confirmed, 'C6: exactly one confirmed charge attempt');

        // All non-empty references must be identical
        $refs = array_filter(array_column($results, 'reference'));
        $this->assertGreaterThanOrEqual(1, count($refs), 'C6: at least one worker returned a reference');
        $this->assertCount(1, array_unique($refs), 'C6: exactly one unique reference');
    }

    // ── C7: Two concurrent orders with opposite item ordering ─────────

    public function test_c7_opposite_item_ordering_no_deadlock(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $productA = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 20,
        ]);

        $category2 = PosCategory::factory()->create();
        $productB = PosProduct::factory()->for($category2, 'category')->create([
            'cost_price' => 7500,
            'sale_price' => 15000,
            'stock' => 20,
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        // Order 1: [A:2, B:3]
        file_put_contents($this->workingDirectory.'/order1.json', json_encode([
            'member_id' => $member->id,
            'client_ref' => 'C7-ORDER-1',
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 2],
                ['pos_product_id' => $productB->id, 'quantity' => 3],
            ],
        ]));

        // Order 2: [B:1, A:4] — opposite ordering
        file_put_contents($this->workingDirectory.'/order2.json', json_encode([
            'member_id' => $member->id,
            'client_ref' => 'C7-ORDER-2',
            'items' => [
                ['pos_product_id' => $productB->id, 'quantity' => 1],
                ['pos_product_id' => $productA->id, 'quantity' => 4],
            ],
        ]));

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c7-order', ['payload_file' => $this->workingDirectory.'/order1.json']),
                $startFile, $resultDir, 'c7-a',
                ['payload_file' => $this->workingDirectory.'/order1.json'],
            ),
            $this->startWorker(
                $this->writeWorkerScript('c7-order', ['payload_file' => $this->workingDirectory.'/order2.json']),
                $startFile, $resultDir, 'c7-b',
                ['payload_file' => $this->workingDirectory.'/order2.json'],
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c7-a'),
            $this->finishWorker($processes[1], $resultDir, 'c7-b'),
        ];

        // Both must succeed — no deadlock
        foreach ($results as $i => $r) {
            $this->assertTrue(
                $r['ok'],
                "C7: worker {$i} must succeed (no deadlock). Error: ".($r['message'] ?? 'none')
            );
        }

        // Reservation totals: A=2+4=6, B=3+1=4
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productA->id,
            'reserved' => 6,
        ], 'pgsql');
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $productB->id,
            'reserved' => 4,
        ], 'pgsql');

        $this->assertSame(2, MemberPaymentIntent::count(), 'C7: exactly two intents');
    }

    // ── C8: Recovery vs ensureCharge race → no orphan charge ──────────

    public function test_c8_recovery_vs_ensure_charge_no_orphan(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->for($category, 'category')->create([
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
        ]);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C8-REAL-TIMEOUT',
            'request_fingerprint' => hash('sha256', 'c8-fp'),
            'amount' => '20000.00',
            'channel' => 'QRIS',
            'gateway_status' => 'PENDING',
            'charge_attempt' => 0,
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'items' => [[
                    'pos_product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => '10000.00',
                    'line_total' => '20000.00',
                    'reservation_location_id' => '1',
                ]],
            ],
        ]);

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $startFileA = $this->workingDirectory.'/start-a.signal';
        $startFileB = $this->workingDirectory.'/start-b.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $storeFile = $this->workingDirectory.'/c8-provider-store.json';
        file_put_contents($storeFile, json_encode([]));
        $counterFile = $this->workingDirectory.'/c8-create-counter.txt';
        $createdSignal = $this->workingDirectory.'/c8-provider-created.signal';
        $releaseSignal = $this->workingDirectory.'/c8-release-response.signal';
        $reconcileSignal = $this->workingDirectory.'/c8-reconcile-called.signal';
        file_put_contents($counterFile, '0');

        $workerA = $this->startWorker(
            $this->writeWorkerScript('c8-charge', ['intent_id' => $intent->id]),
            $startFileA,
            $resultDir,
            'c8-worker-a',
            [
                'intent_id' => $intent->id,
                'store_file' => $storeFile,
                'counter_file' => $counterFile,
                'created_signal' => $createdSignal,
                'release_signal' => $releaseSignal,
                'reconcile_signal' => $reconcileSignal,
            ],
        );

        touch($startFileA);
        $this->waitForFile($createdSignal, 10, 'C8: provider-created signal was not emitted by worker A.');

        MemberPaymentIntent::query()->whereKey($intent->id)->update([
            'updated_at' => now()->subMinutes(10),
        ]);

        $workerB = $this->startWorker(
            $this->writeWorkerScript('c8-charge', ['intent_id' => $intent->id]),
            $startFileB,
            $resultDir,
            'c8-worker-b',
            [
                'intent_id' => $intent->id,
                'store_file' => $storeFile,
                'counter_file' => $counterFile,
                'created_signal' => $createdSignal,
                'release_signal' => $releaseSignal,
                'reconcile_signal' => $reconcileSignal,
            ],
        );
        $recoveryWorker = $this->startWorker(
            $this->writeWorkerScript('c8-recovery', []),
            $startFileB,
            $resultDir,
            'c8-recovery',
            [
                'store_file' => $storeFile,
                'counter_file' => $counterFile,
                'created_signal' => $createdSignal,
                'release_signal' => $releaseSignal,
                'reconcile_signal' => $reconcileSignal,
            ],
        );

        usleep(300000);
        touch($startFileB);

        $workerBResult = $this->finishWorker($workerB, $resultDir, 'c8-worker-b');
        $this->waitForFile($reconcileSignal, 10, 'C8: recovery did not call provider reconciliation.');
        $recoveryResult = $this->finishWorker($recoveryWorker, $resultDir, 'c8-recovery');

        touch($releaseSignal);
        $workerAResult = $this->finishWorker($workerA, $resultDir, 'c8-worker-a');

        $results = [$workerAResult, $workerBResult, $recoveryResult];

        $intent->refresh();

        $createCallCount = (int) trim(file_get_contents($counterFile) ?: '0');
        $this->assertSame(1, $createCallCount, 'C8: provider create-call count must be exactly 1');

        $attemptCount = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->count();
        $this->assertSame(1, $attemptCount, 'C8: exactly one charge attempt');

        $confirmedAttemptCount = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('state', MemberPaymentChargeAttempt::STATE_CONFIRMED)
            ->count();
        $this->assertSame(1, $confirmedAttemptCount, 'C8: exactly one confirmed attempt');

        $providerStore = json_decode((string) file_get_contents($storeFile), true, 512, JSON_THROW_ON_ERROR);
        $providerStoreChargeCount = is_array($providerStore) ? count($providerStore) : 0;
        $this->assertSame(1, $providerStoreChargeCount, 'C8: provider store contains exactly one charge');

        $uniqueProviderReferenceCount = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->whereNotNull('provider_reference')
            ->distinct('provider_reference')
            ->count('provider_reference');
        $this->assertSame(1, $uniqueProviderReferenceCount, 'C8: exactly one unique provider reference');

        $unexpectedOrphanAttemptCount = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('state', MemberPaymentChargeAttempt::STATE_ORPHANED)
            ->count();
        $this->assertSame(0, $unexpectedOrphanAttemptCount, 'C8: no unexpected orphan attempts');

        $duplicateChargeIncidentCount = PaymentReconciliationIncident::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('incident_type', 'orphaned_charge')
            ->count();
        $this->assertSame(0, $duplicateChargeIncidentCount, 'C8: no duplicate-charge orphan incident');

        $this->assertCount(3, $results, 'C8: worker A, worker B, and recovery worker all executed');
        $this->assertTrue($workerAResult['ok'], 'C8: worker A completed successfully');
        $this->assertTrue($workerBResult['ok'], 'C8: worker B completed successfully');
        $this->assertTrue($recoveryResult['ok'], 'C8: recovery worker completed successfully');
        $this->assertSame(0, $workerAResult['exit_code'], 'C8: worker A exit code valid');
        $this->assertSame(0, $workerBResult['exit_code'], 'C8: worker B exit code valid');
        $this->assertSame(0, $recoveryResult['exit_code'], 'C8: recovery worker exit code valid');
        $this->assertSame('RECONCILIATION_REQUIRED', $workerBResult['status'] ?? null, 'C8: worker B blocked and did not create a second charge');
        $this->assertSame($providerOrderId, $workerAResult['reference'] ?? null, 'C8: late response returned the same provider reference');
        $this->assertTrue(file_exists($releaseSignal), 'C8: late response was released only after recovery completed');

        $this->assertTrue($intent->isStateCombinationValid(), 'C8: valid state combination');
    }

    // ── C9: Parallel loan installment requests → one intent and charge ──

    public function test_c9_parallel_loan_installment_requests_create_one_charge(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);
        $loan = Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $org->id,
        ]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
        ]);

        $startFile = $this->workingDirectory.'/c9-start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $processes[] = $this->startWorker(
                $this->writeWorkerScript('c9-loan-charge', [
                    'member_id' => $member->id,
                    'installment_id' => $installment->id,
                ]),
                $startFile,
                $resultDir,
                "c9-worker-{$i}",
                [
                    'member_id' => $member->id,
                    'installment_id' => $installment->id,
                ],
            );
        }

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c9-worker-0'),
            $this->finishWorker($processes[1], $resultDir, 'c9-worker-1'),
        ];

        $this->assertCount(2, $results);
        $this->assertSame(1, MemberPaymentIntent::query()
            ->where('cooperative_member_id', $member->id)
            ->where('payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
            ->where('payable_id', $installment->id)
            ->count());

        $intent = MemberPaymentIntent::query()
            ->where('cooperative_member_id', $member->id)
            ->where('payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
            ->where('payable_id', $installment->id)
            ->firstOrFail();

        $this->assertSame(1, MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->count());
        $this->assertNotNull($intent->refresh()->gateway_reference);
        $this->assertSame('PENDING', $intent->gateway_status);
        $this->assertTrue($intent->isStateCombinationValid());
    }

    // ── Worker helpers ──────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $params
     */
    private function writeWorkerScript(string $action, array $params): string
    {
        $dbHost = $this->dbConfig['host'];
        $dbPort = $this->dbConfig['port'];
        $dbDatabase = $this->dbConfig['database'];
        $dbUsername = $this->dbConfig['username'];
        $dbPassword = $this->dbConfig['password'];
        $appKey = config('app.key') ?: getenv('APP_KEY') ?: '';
        $paramsJson = json_encode($params, JSON_THROW_ON_ERROR);

        $script = match ($action) {
            'c1' => $this->workerTemplateC1(),
            'c2' => $this->workerTemplateC2(),
            'c3-resolve' => $this->workerTemplateC3Resolve(),
            'c4-paid' => $this->workerTemplateC4Paid(),
            'c4-expiry' => $this->workerTemplateC4Expiry(),
            'c5-paid' => $this->workerTemplateC5Paid(),
            'c6-charge' => $this->workerTemplateC6Charge(),
            'c7-order' => $this->workerTemplateC7Order(),
            'c8-recovery' => $this->workerTemplateC8Recovery(),
            'c8-charge' => $this->workerTemplateC8Charge(),
            'c9-loan-charge' => $this->workerTemplateC9LoanCharge(),
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };

        $fullScript = <<<PHP
<?php

declare(strict_types=1);

[\$script, \$repoPath, \$startFile, \$resultFile, \$paramsJson] = \$argv;

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
putenv("APP_KEY={$appKey}");

\$_ENV['APP_ENV'] = 'testing';
\$_ENV['CACHE_STORE'] = 'array';
\$_ENV['SESSION_DRIVER'] = 'array';
\$_ENV['QUEUE_CONNECTION'] = 'sync';
\$_ENV['DB_CONNECTION'] = 'pgsql';
\$_ENV['APP_KEY'] = '{$appKey}';

require \$repoPath.'/vendor/autoload.php';

\$app = require \$repoPath.'/bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config()->set('database.default', 'pgsql');
config()->set('services.midtrans.server_key', '');

\$params = json_decode(\$paramsJson, true, 512, JSON_THROW_ON_ERROR);

{$script}
PHP;

        // Every worker must read an immutable script. Reusing the same path lets
        // concurrent file_put_contents() calls truncate a script while another
        // PHP process is starting, which can leave that worker without a result.
        $scriptPath = $this->workingDirectory.'/'.$action.'-'.bin2hex(random_bytes(8)).'.php';
        file_put_contents($scriptPath, $fullScript);

        return $scriptPath;
    }

    private function workerTemplateC1(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Models\MemberPaymentIntent;

$member = CooperativeMember::query()->findOrFail((int) $params['member_id']);
$service = app(MemberOrderIntentService::class);
$items = [['pos_product_id' => (int) $params['product_id'], 'quantity' => 2, 'unit_price' => '10000.00', 'line_total' => '20000.00']];
$canonical = ['amount' => 20000, 'channel' => 'QRIS', 'items' => $items];

try {
    $resolution = $service->resolveOrCreate(
        member: $member,
        payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
        clientReference: 'C1-CONCURRENT',
        canonicalRequest: $canonical,
        rawItems: $items,
    );

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'intent_id' => $resolution->intent->id,
        'created' => $resolution->wasCreated(),
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC2(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Models\MemberPaymentIntent;

$payload = json_decode(file_get_contents($params['payload_file']), true, 512, JSON_THROW_ON_ERROR);
$member = CooperativeMember::query()->findOrFail((int) $payload['member_id']);
$service = app(MemberOrderIntentService::class);

$rawItems = $payload['items'];
$item = $rawItems[0];
$quantity = (int) $item['quantity'];
$total = (int) $item['unit_price'] * $quantity;
$rawItems[0]['line_total'] = (string) $total;

try {
    $resolution = $service->resolveOrCreate(
        member: $member,
        payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
        clientReference: 'C2-CONCURRENT',
        canonicalRequest: ['amount' => $total, 'channel' => 'QRIS', 'items' => $rawItems],
        rawItems: $rawItems,
    );

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'intent_id' => $resolution->intent->id,
        'created' => $resolution->wasCreated(),
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC3Resolve(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Models\MemberPaymentIntent;

$member = CooperativeMember::query()->findOrFail((int) $params['member_id']);
$service = app(MemberOrderIntentService::class);
$items = [['pos_product_id' => (int) $params['product_id'], 'quantity' => 1, 'unit_price' => '10000.00', 'line_total' => '10000.00']];

try {
    $resolution = $service->resolveOrCreate(
        member: $member,
        payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
        clientReference: 'C3-SETTLED',
        canonicalRequest: ['amount' => 10000, 'channel' => 'QRIS', 'items' => $items],
        rawItems: $items,
    );

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'intent_id' => $resolution->intent->id,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC4Paid(): string
    {
        return <<<'PHP'
use App\Services\Integrations\MemberPaymentIntentStateService;

$stateService = app(MemberPaymentIntentStateService::class);

try {
    $stateService->applyGatewayEvent(
        $params['gateway_reference'],
        'PAID',
        ['status' => 'PAID', 'reference' => $params['gateway_reference']],
        null,
    );

    file_put_contents($resultFile, json_encode(['ok' => true]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC4Expiry(): string
    {
        return <<<'PHP'
use App\Services\Integrations\MemberPaymentIntentStateService;
use App\Models\MemberPaymentIntent;

$stateService = app(MemberPaymentIntentStateService::class);
$intent = MemberPaymentIntent::query()->findOrFail((int) $params['intent_id']);

try {
    $stateService->expireStaleIntent($intent);

    file_put_contents($resultFile, json_encode(['ok' => true]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC5Paid(): string
    {
        return <<<'PHP'
use App\Services\Integrations\MemberPaymentIntentStateService;
use App\Services\Integrations\MemberPaymentSettlementService;
use App\Models\MemberPaymentIntent;

$stateService = app(MemberPaymentIntentStateService::class);

try {
    $intent = $stateService->applyGatewayEvent(
        $params['gateway_reference'],
        'PAID',
        ['status' => 'PAID', 'reference' => $params['gateway_reference']],
        null,
    );

    // Full webhook→settlement flow: if PAID and not yet settled, settle
    if ($intent && $intent->gateway_status === 'PAID' && ! $intent->settled_at) {
        $settlementService = app(MemberPaymentSettlementService::class);
        $intent = $settlementService->settle($intent);
    }

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'settled' => $intent && $intent->settled_at !== null,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC6Charge(): string
    {
        return <<<'PHP'
use App\Models\MemberPaymentIntent;
use App\Services\Integrations\PaymentGatewayService;
use App\Services\Integrations\PaymentIntentChargeService;

// Define a counting gateway that tracks provider calls via a shared file.
if (! class_exists('CountingGateway')) {
    class CountingGateway extends PaymentGatewayService
    {
        public static string $counterFile = '';

        public function buildIntentCharge(MemberPaymentIntent $intent): array
        {
            if (self::$counterFile !== '') {
                $fp = fopen(self::$counterFile, 'c+');
                if ($fp) {
                    flock($fp, LOCK_EX);
                    $count = (int) trim(fread($fp, 32) ?: '0');
                    $count++;
                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, (string) $count);
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            }

            return parent::buildIntentCharge($intent);
        }
    }
}

CountingGateway::$counterFile = $params['counter_file'] ?? '';

app()->bind(PaymentGatewayService::class, function ($app) {
    return $app->make(CountingGateway::class);
});

$intent = MemberPaymentIntent::query()->findOrFail((int) $params['intent_id']);
$chargeService = app(PaymentIntentChargeService::class);

try {
    $charge = $chargeService->ensureCharge($intent);

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'reference' => $charge['reference'] ?? null,
        'status' => $charge['status'] ?? null,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC7Order(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Models\MemberPaymentIntent;
use App\Support\Money\MinorAmount;

$payload = json_decode(file_get_contents($params['payload_file']), true, 512, JSON_THROW_ON_ERROR);
$member = CooperativeMember::query()->findOrFail((int) $payload['member_id']);
$service = app(MemberOrderIntentService::class);

$rawItems = $payload['items'];
foreach ($rawItems as &$it) {
    $it['unit_price'] = (string) ($it['unit_price'] ?? '10000.00');
    $lineMinor = MinorAmount::fromDecimal($it['unit_price']) * (int) $it['quantity'];
    $it['line_total'] = MinorAmount::toDecimalString($lineMinor);
}
unset($it);
$total = array_sum(array_map(
    static fn (array $it): int => MinorAmount::fromDecimal($it['line_total']),
    $rawItems
));

try {
    $resolution = $service->resolveOrCreate(
        member: $member,
        payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
        clientReference: $payload['client_ref'],
        canonicalRequest: ['amount' => $total, 'channel' => 'QRIS', 'items' => $rawItems],
        rawItems: $rawItems,
    );

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'intent_id' => $resolution->intent->id,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC8Recovery(): string
    {
        return $this->fakeRecoveryProviderSetup().<<<'PHP'

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('orders:recover-stale-charges', [
        '--minutes' => 1,
        '--limit' => 50,
    ]);

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'output' => trim(Artisan::output()),
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC8Charge(): string
    {
        return $this->fakeRecoveryProviderSetup().<<<'PHP'

use App\Models\MemberPaymentIntent;
use App\Services\Integrations\PaymentIntentChargeService;

$intent = MemberPaymentIntent::query()->findOrFail((int) $params['intent_id']);
$chargeService = app(PaymentIntentChargeService::class);

try {
    $charge = $chargeService->ensureCharge($intent);

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'reference' => $charge['reference'] ?? null,
        'status' => $charge['status'] ?? null,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    private function workerTemplateC9LoanCharge(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Integrations\LoanPaymentIntentService;
use App\Services\Integrations\PaymentIntentChargeService;

$member = CooperativeMember::query()->findOrFail((int) $params['member_id']);
$loanIntentService = app(LoanPaymentIntentService::class);
$chargeService = app(PaymentIntentChargeService::class);

try {
    $resolution = $loanIntentService->resolveOrCreate(
        member: $member,
        installmentId: (int) $params['installment_id'],
        userId: $member->user_id,
        requestedChannel: 'QRIS',
    );
    $charge = $chargeService->ensureCharge($resolution->intent->refresh());

    file_put_contents($resultFile, json_encode([
        'ok' => true,
        'intent_id' => $resolution->intent->id,
        'reference' => $charge['reference'] ?? null,
    ]));
    exit(0);
} catch (Throwable $throwable) {
    file_put_contents($resultFile, json_encode([
        'ok' => false,
        'class' => $throwable::class,
        'message' => $throwable->getMessage(),
    ]));
    exit(1);
}
PHP;
    }

    /**
     * Returns PHP code defining a fake provider that stores charges in a shared
     * file, counts create calls, and supports reconciliation. Used by C8 workers
     * to simulate a provider that recorded a charge before a response timeout.
     */
    private function fakeRecoveryProviderSetup(): string
    {
        return <<<'PHP'
config()->set('services.midtrans.server_key', 'fake-configured-key');
app()->bind(\App\Contracts\Integrations\PaymentGatewayProvider::class, function () use ($params) {
    $provider = new \Tests\Support\ConcurrencyPaymentGatewayProvider();
    $provider->storeFile = (string) ($params['store_file'] ?? '');
    $provider->counterFile = (string) ($params['counter_file'] ?? '');
    $provider->createdSignal = (string) ($params['created_signal'] ?? '');
    $provider->releaseSignal = (string) ($params['release_signal'] ?? '');
    $provider->reconcileCalledSignal = (string) ($params['reconcile_signal'] ?? '');

    return $provider;
});
PHP;
    }

    // ── Process management ───────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $params
     * @return array{process: mixed, pipes: array<int, resource>}
     */
    private function startWorker(string $workerFile, string $startFile, string $resultDir, string $label, array $params = []): array
    {
        $resultFile = $resultDir.'/'.$label.'.json';
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                $workerFile,
                base_path(),
                $startFile,
                $resultFile,
                json_encode($params, JSON_THROW_ON_ERROR),
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
            'resultFile' => $resultFile,
        ];
    }

    /**
     * @param  array{process: mixed, pipes: array<int, resource>, resultFile: string}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker, string $resultDir, string $label): array
    {
        $deadline = 30;
        $stdout = '';
        $stderr = '';

        // Wait for process to finish with a bounded deadline
        $start = time();
        while (is_resource($worker['process'] ?? null)) {
            $status = proc_get_status($worker['process']);
            if ($status !== false && ! $status['running']) {
                break;
            }

            if (time() - $start > $deadline) {
                // Terminate hung worker
                if (is_resource($worker['process'] ?? null)) {
                    proc_terminate($worker['process'], 15);
                }

                self::fail("Worker {$label} exceeded {$deadline}s deadline.");
            }

            usleep(100000);
        }

        if (is_resource($worker['pipes'][2] ?? null)) {
            $stderr = (string) stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1] ?? null)) {
            $stdout = (string) stream_get_contents($worker['pipes'][1]);
            fclose($worker['pipes'][1]);
        }

        $exitCode = 0;
        if (is_resource($worker['process'] ?? null)) {
            $exitCode = proc_close($worker['process']);
        }

        $contents = file_exists($worker['resultFile'])
            ? file_get_contents($worker['resultFile'])
            : '{}';

        $result = json_decode($contents ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        if ($exitCode !== 0 && ! isset($result['ok'])) {
            $result['ok'] = false;
            $result['message'] = "Worker exited with code {$exitCode}. Stderr: ".substr($stderr, 0, 500);
        }

        if (! file_exists($worker['resultFile'])) {
            self::fail("Worker {$label} did not produce a result file. Stderr: ".substr($stderr, 0, 500));
        }

        $result['exit_code'] = $exitCode;
        $result['stdout'] = $stdout;
        $result['stderr'] = $stderr;

        return $result;
    }

    private function waitForFile(string $file, int $timeoutSeconds, string $failureMessage): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (! file_exists($file)) {
            if (microtime(true) >= $deadline) {
                self::fail($failureMessage);
            }

            usleep(50000);
        }
    }

    private function cleanDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
