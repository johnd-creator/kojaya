<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
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

    /** @var array<string, mixed> */
    private array $sharedState = [];

    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        $originalConnection = getenv('DB_CONNECTION') ?: 'sqlite';

        if ($originalConnection !== 'pgsql') {
            parent::setUp();
            $this->markTestSkipped('PaymentConcurrencyTest requires PostgreSQL (DB_CONNECTION=pgsql).');

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

        $workerCount = 8;
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
            );
        }

        usleep(300000);
        touch($startFile);

        $results = [];
        foreach ($processes as $i => $proc) {
            $results[] = $this->finishWorker($proc, $resultDir, "c1-{$i}");
        }

        $successes = array_filter($results, fn (array $r): bool => $r['ok']);
        $conflicts = array_filter($results, fn (array $r): bool => ! $r['ok']);

        // All workers should return a result (either created or reused)
        // Exactly one intent must exist
        $this->assertSame(1, MemberPaymentIntent::count(), 'C1: exactly one intent');
        $this->assertSame(1, array_unique(array_column($successes, 'intent_id')), 'C1: one unique intent ID');

        // Reserved stock must be exactly 2
        $this->assertDatabaseHas('pos_inventory_stocks', [
            'pos_product_id' => $product->id,
            'reserved' => 2,
        ], 'pgsql');

        // At most one reservation.created audit
        $createdAudits = AuditLog::query()
            ->where('action', 'reservation.created')
            ->where('subject_type', 'member_payment_intent')
            ->count();
        $this->assertLessThanOrEqual(1, $createdAudits, 'C1: at most one reservation.created audit');
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
            ),
            $this->startWorker(
                $this->writeWorkerScript('c2', ['payload_file' => $this->workingDirectory.'/payload-b.json']),
                $startFile, $resultDir, 'c2-b',
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
            ),
            $this->startWorker(
                $this->writeWorkerScript('c3-resolve', [
                    'member_id' => $member->id,
                    'product_id' => $product->id,
                ]),
                $startFile, $resultDir, 'c3-b',
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
            ),
            $this->startWorker(
                $this->writeWorkerScript('c4-expiry', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c4-expiry',
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c4-paid'),
            $this->finishWorker($processes[1], $resultDir, 'c4-expiry'),
        ];

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

        $gatewayRef = 'MPI-C5-'.bin2hex(random_bytes(6));

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C5-DUP-PAID',
            'request_fingerprint' => hash('sha256', 'c5-fp'),
            'amount' => 10000,
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
                    'reservation_location_id' => '1',
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
            ),
            $this->startWorker(
                $this->writeWorkerScript('c5-paid', ['gateway_reference' => $gatewayRef]),
                $startFile, $resultDir, 'c5-b',
            ),
        ];

        usleep(300000);
        touch($startFile);

        $this->finishWorker($processes[0], $resultDir, 'c5-a');
        $this->finishWorker($processes[1], $resultDir, 'c5-b');

        $intent->refresh();

        // Must have exactly one transaction
        $this->assertSame(1, PosTransaction::count(), 'C5: exactly one transaction');
        $this->assertSame('PAID', $intent->gatewayStatus()->value, 'C5: intent is PAID');
        $this->assertSame(
            MemberPaymentIntent::RESERVATION_CONSUMED,
            $intent->reservationStatus()->value,
            'C5: reservation consumed'
        );
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

        $processes = [
            $this->startWorker(
                $this->writeWorkerScript('c6-charge', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c6-a',
            ),
            $this->startWorker(
                $this->writeWorkerScript('c6-charge', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c6-b',
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c6-a'),
            $this->finishWorker($processes[1], $resultDir, 'c6-b'),
        ];

        $intent->refresh();

        // Both workers must have returned a reference
        $refs = array_filter(array_column($results, 'reference'));
        $this->assertCount(2, $refs, 'C6: both workers returned a reference');

        // All returned references must be the same
        $this->assertCount(1, array_unique($refs), 'C6: exactly one unique reference');

        // Only one charge attempt should be CONFIRMED, not two
        $confirmed = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('state', MemberPaymentChargeAttempt::STATE_CONFIRMED)
            ->count();
        $this->assertSame(1, $confirmed, 'C6: exactly one confirmed charge attempt');
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
            ),
            $this->startWorker(
                $this->writeWorkerScript('c7-order', ['payload_file' => $this->workingDirectory.'/order2.json']),
                $startFile, $resultDir, 'c7-b',
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

        // Create intent stuck in stale CHARGE_CREATING
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'C8-STALE',
            'request_fingerprint' => hash('sha256', 'c8-fp'),
            'amount' => 10000,
            'channel' => 'QRIS',
            'gateway_status' => 'CHARGE_CREATING',
            'charge_attempt' => 1,
            'updated_at' => now()->subMinutes(10), // stale
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

        // Create a stale CHARGE_CREATING attempt record
        MemberPaymentChargeAttempt::query()->create([
            'member_payment_intent_id' => $intent->id,
            'attempt' => 1,
            'idempotency_key' => "member-intent:{$intent->id}:1",
            'state' => MemberPaymentChargeAttempt::STATE_PREPARING,
            'started_at' => now()->subMinutes(10),
        ]);

        $startFile = $this->workingDirectory.'/start.signal';
        $resultDir = $this->workingDirectory.'/results';
        mkdir($resultDir);

        $processes = [
            // Process A: run recovery command
            $this->startWorker(
                $this->writeWorkerScript('c8-recovery', []),
                $startFile, $resultDir, 'c8-recovery',
            ),
            // Process B: call ensureCharge
            $this->startWorker(
                $this->writeWorkerScript('c6-charge', ['intent_id' => $intent->id]),
                $startFile, $resultDir, 'c8-charge',
            ),
        ];

        usleep(300000);
        touch($startFile);

        $results = [
            $this->finishWorker($processes[0], $resultDir, 'c8-recovery'),
            $this->finishWorker($processes[1], $resultDir, 'c8-charge'),
        ];

        $intent->refresh();

        // Intent must end up in a valid state with exactly one charge reference
        $gatewayStatus = $intent->gatewayStatus()->value;
        $this->assertContains($gatewayStatus, ['PENDING', 'CHARGE_CREATING'], 'C8: valid gateway state');

        // No confirmed charge attempts with duplicate provider references
        $confirmedAttempts = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('state', MemberPaymentChargeAttempt::STATE_CONFIRMED)
            ->get();

        $providerRefs = $confirmedAttempts->pluck('provider_reference')->filter()->unique();
        $this->assertLessThanOrEqual(1, $providerRefs->count(), 'C8: at most one unique provider reference');

        // Stale attempt 1 must not be CONFIRMED (either UNKNOWN or FAILED)
        $attempt1 = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('attempt', 1)
            ->first();
        if ($attempt1) {
            $this->assertNotSame(
                MemberPaymentChargeAttempt::STATE_CONFIRMED,
                $attempt1->state,
                'C8: stale attempt 1 is not CONFIRMED'
            );
        }

        // State combination must be valid
        $this->assertTrue($intent->isStateCombinationValid(), 'C8: valid state combination');
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

\$_ENV['APP_ENV'] = 'testing';
\$_ENV['CACHE_STORE'] = 'array';
\$_ENV['SESSION_DRIVER'] = 'array';
\$_ENV['QUEUE_CONNECTION'] = 'sync';
\$_ENV['DB_CONNECTION'] = 'pgsql';

require \$repoPath.'/vendor/autoload.php';

\$app = require \$repoPath.'/bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config()->set('database.default', 'pgsql');
config()->set('services.midtrans.server_key', '');

\$params = json_decode(\$paramsJson, true, 512, JSON_THROW_ON_ERROR);

{$script}
PHP;

        $scriptPath = $this->workingDirectory.'/'.$action.'.php';
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

    private function workerTemplateC6Charge(): string
    {
        return <<<'PHP'
use App\Models\MemberPaymentIntent;
use App\Services\Integrations\PaymentIntentChargeService;

$intent = MemberPaymentIntent::query()->findOrFail((int) $params['intent_id']);
$chargeService = app(PaymentIntentChargeService::class);

try {
    $charge = $chargeService->ensureCharge($intent);

    file_put_contents($resultFile, json_encode([
        'ok' => true,
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

    private function workerTemplateC7Order(): string
    {
        return <<<'PHP'
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Models\MemberPaymentIntent;

$payload = json_decode(file_get_contents($params['payload_file']), true, 512, JSON_THROW_ON_ERROR);
$member = CooperativeMember::query()->findOrFail((int) $payload['member_id']);
$service = app(MemberOrderIntentService::class);

$rawItems = $payload['items'];
foreach ($rawItems as &$it) {
    $it['unit_price'] = (string) ($it['unit_price'] ?? '10000.00');
    $it['line_total'] = (string) ((int) $it['quantity'] * (float) $it['unit_price']);
}
unset($it);
$total = array_sum(array_map(fn (array $it): float => (float) $it['line_total'], $rawItems));

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
        return <<<'PHP'
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

    // ── Process management ───────────────────────────────────────────────

    /**
     * @return array{process: mixed, pipes: array<int, resource>}
     */
    private function startWorker(string $workerFile, string $startFile, string $resultDir, string $label): array
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
                json_encode($this->sharedState, JSON_THROW_ON_ERROR),
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
        if (is_resource($worker['pipes'][2] ?? null)) {
            stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);
        }

        if (is_resource($worker['pipes'][1] ?? null)) {
            fclose($worker['pipes'][1]);
        }

        if (is_resource($worker['process'] ?? null)) {
            proc_close($worker['process']);
        }

        $contents = file_exists($worker['resultFile'])
            ? file_get_contents($worker['resultFile'])
            : '{}';

        return json_decode($contents ?: '{}', true, 512, JSON_THROW_ON_ERROR);
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
