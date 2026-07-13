<?php

namespace Tests\Feature;

use App\Contracts\Integrations\PaymentGatewayProvider;
use App\Enums\ProviderChargeOutcome;
use App\Exceptions\ProviderChargeException;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentChargeAttempt;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Integrations\PaymentIntentChargeService;
use App\Services\Integrations\WebhookEvent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentChargeRecoveryTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.midtrans.server_key' => '']);
    }

    // ── P0-1: Provider Failure Classification ────────────────────────────

    public function test_provider_connection_timeout_marks_attempt_unknown_and_blocks(): void
    {
        $intent = $this->createChargableIntent();

        $this->bindFailingProvider(
            ProviderChargeException::unknown('Simulated connection timeout')
        );

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
            $this->fail('Expected ProviderChargeException was not thrown.');
        } catch (ProviderChargeException $e) {
            $this->assertSame(ProviderChargeOutcome::Unknown, $e->outcome);
        }

        $intent->refresh();

        $this->assertSame('CHARGE_CREATING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertNotNull($attempt);
        $this->assertSame(MemberPaymentChargeAttempt::STATE_UNKNOWN, $attempt->state);

        $this->assertSame(1, MemberPaymentChargeAttempt::query()->where('member_payment_intent_id', $intent->id)->count());
    }

    public function test_provider_http_4xx_rejection_marks_attempt_failed(): void
    {
        $intent = $this->createChargableIntent();

        $this->bindFailingProvider(
            ProviderChargeException::rejected('Channel not activated', 400)
        );

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
            $this->fail('Expected ProviderChargeException was not thrown.');
        } catch (ProviderChargeException $e) {
            $this->assertSame(ProviderChargeOutcome::DefinitiveRejected, $e->outcome);
        }

        $intent->refresh();

        $this->assertSame('PENDING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_FAILED, $attempt->state);
    }

    public function test_provider_http_5xx_marks_attempt_unknown(): void
    {
        $intent = $this->createChargableIntent();

        $this->bindFailingProvider(
            ProviderChargeException::unknown('Provider server error', 500)
        );

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
        } catch (ProviderChargeException $e) {
            $this->assertSame(ProviderChargeOutcome::Unknown, $e->outcome);
        }

        $intent->refresh();

        $this->assertSame('CHARGE_CREATING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_UNKNOWN, $attempt->state);
    }

    public function test_unknown_outcome_does_not_open_new_attempt(): void
    {
        $intent = $this->createChargableIntent();

        $this->bindFailingProvider(
            ProviderChargeException::unknown('Read timeout')
        );

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
        } catch (ProviderChargeException) {
            // expected
        }

        $response = $service->ensureCharge($intent->refresh());
        $this->assertSame('RECONCILIATION_REQUIRED', $response['status']);

        $this->assertSame(1, MemberPaymentChargeAttempt::query()->where('member_payment_intent_id', $intent->id)->count());
    }

    // ── P0-2: Same-Attempt Retry on Recovery Not-Found ───────────────────

    public function test_recovery_not_found_retries_same_attempt(): void
    {
        $intent = $this->createStaleChargeCreatingIntent();

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        $this->assertSame('PENDING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->where('attempt', 1)
            ->first();
        $this->assertNotNull($attempt);
        $this->assertSame(MemberPaymentChargeAttempt::STATE_PREPARING, $attempt->state);

        $this->assertSame(1, MemberPaymentChargeAttempt::query()->where('member_payment_intent_id', $intent->id)->count());

        // Next ensureCharge reuses attempt 1 (same idempotency key, same provider order ID)
        $service = app(PaymentIntentChargeService::class);
        $service->ensureCharge($intent->refresh());

        $intent->refresh();
        $this->assertSame(1, (int) $intent->charge_attempt);

        $attempt->refresh();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_CONFIRMED, $attempt->state);

        $this->assertSame(1, MemberPaymentChargeAttempt::query()->where('member_payment_intent_id', $intent->id)->count());
    }

    // ── P0-3: Recovery HTTP Outside DB Transaction ───────────────────────

    public function test_recovery_provider_call_outside_db_transaction(): void
    {
        $intent = $this->createStaleChargeCreatingIntent();

        $transactionLevelDuringCall = 99;

        $this->app->bind(PaymentGatewayProvider::class, function () use (&$transactionLevelDuringCall) {
            return new class($transactionLevelDuringCall) implements PaymentGatewayProvider
            {
                public function __construct(private int &$level) {}

                public function isConfigured(): bool
                {
                    return true;
                }

                public function createIntentCharge(\App\Models\MemberPaymentIntent $intent): array
                {
                    return [];
                }

                public function reconcileIntentCharge(string $providerOrderId): ?array
                {
                    $this->level = DB::transactionLevel();

                    return null;
                }

                public function createCharge(\App\Models\CooperativePayment $payment, string $channel): array
                {
                    return [];
                }

                public function verifyWebhook(array $payload, array $headers): bool
                {
                    return false;
                }

                public function parseWebhook(array $payload): WebhookEvent
                {
                    throw new \RuntimeException('Not implemented');
                }

                public function acknowledgeResponse(): mixed
                {
                    return null;
                }
            };
        });

        config(['services.midtrans.server_key' => 'test-configured']);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        // The provider call must happen with zero open DB transactions
        $this->assertSame(0, $transactionLevelDuringCall, 'Recovery provider call must be outside DB transaction');
    }

    // ── P0-4: Per-Status Reconciliation ──────────────────────────────────

    public function test_recovery_reconciliation_pending_confirms_charge(): void
    {
        $intent = $this->createStaleChargeCreatingIntent();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'PENDING',
                'channel' => 'QRIS',
                'amount' => 10000.0,
                'checkout_url' => null,
                'qr_string' => 'fake-qr',
            ],
        ]);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();
        $this->assertSame('PENDING', $intent->gateway_status);
        $this->assertSame($providerOrderId, $intent->gateway_reference);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_CONFIRMED, $attempt->state);
    }

    public function test_recovery_reconciliation_unknown_status_creates_incident(): void
    {
        $intent = $this->createStaleChargeCreatingIntent();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'UNKNOWN',
                'channel' => 'QRIS',
                'amount' => 10000.0,
                'checkout_url' => null,
                'qr_string' => null,
            ],
        ]);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        $this->assertSame('CHARGE_CREATING', $intent->gateway_status);

        $this->assertDatabaseHas('payment_reconciliation_incidents', [
            'member_payment_intent_id' => $intent->id,
            'incident_type' => 'reconciliation_unknown_status',
        ]);
    }

    public function test_recovery_reconciliation_expired_releases_reservation(): void
    {
        $intent = $this->createStaleChargeCreatingIntent();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'EXPIRED',
                'channel' => 'QRIS',
                'amount' => 10000.0,
                'checkout_url' => null,
                'qr_string' => null,
            ],
        ]);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        $this->assertSame('EXPIRED', $intent->gateway_status);
        $this->assertContains($intent->reservation_status, ['RELEASED', 'EXPIRED']);
    }

    // ── Task 2: Reconciliation PAID Settlement ───────────────────────────

    public function test_recovery_reconciliation_paid_settles_and_consumes(): void
    {
        $intent = $this->createStaleChargeCreatingIntentWithInventory();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'PAID',
                'channel' => 'QRIS',
                'amount' => 20000.0,
                'checkout_url' => null,
                'qr_string' => null,
            ],
        ]);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        // intent PAID
        $this->assertSame('PAID', $intent->gateway_status);

        // reservation CONSUMED
        $this->assertSame(MemberPaymentIntent::RESERVATION_CONSUMED, $intent->reservation_status);

        // settlement SETTLED
        $this->assertSame('SETTLED', $intent->settlement_status);

        // settled_at not null
        $this->assertNotNull($intent->settled_at);

        // exactly one POS transaction
        $this->assertSame(1, PosTransaction::count(), 'Task2: exactly one POS transaction');

        // exactly one consume audit
        $consumeAudits = AuditLog::query()
            ->where('action', 'reservation.consumed')
            ->where('subject_type', MemberPaymentIntent::class)
            ->where('subject_id', $intent->id)
            ->count();
        $this->assertSame(1, $consumeAudits, 'Task2: exactly one consume audit');
    }

    public function test_recovery_reconciliation_paid_idempotent_on_double_run(): void
    {
        $intent = $this->createStaleChargeCreatingIntentWithInventory();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'PAID',
                'channel' => 'QRIS',
                'amount' => 20000.0,
                'checkout_url' => null,
                'qr_string' => null,
            ],
        ]);

        // First recovery run
        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        // Second recovery run — should be a no-op since intent is no longer CHARGE_CREATING
        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        $this->assertSame('PAID', $intent->gateway_status);
        $this->assertSame('SETTLED', $intent->settlement_status);

        // Still exactly one POS transaction (no duplicate)
        $this->assertSame(1, PosTransaction::count(), 'Task2: no duplicate POS transaction after double recovery');

        // Still exactly one consume audit
        $consumeAudits = AuditLog::query()
            ->where('action', 'reservation.consumed')
            ->where('subject_type', MemberPaymentIntent::class)
            ->where('subject_id', $intent->id)
            ->count();
        $this->assertSame(1, $consumeAudits, 'Task2: no duplicate consume audit after double recovery');
    }

    public function test_recovery_reconciliation_paid_amount_mismatch_no_settle(): void
    {
        $intent = $this->createStaleChargeCreatingIntentWithInventory();

        $providerOrderId = "KOJ-MPI-{$intent->id}-1";

        // Provider reports PAID but with wrong amount (50000 vs expected 10000)
        $this->bindReconcilingProvider([
            $providerOrderId => [
                'provider' => 'fake',
                'reference' => $providerOrderId,
                'status' => 'PAID',
                'channel' => 'QRIS',
                'amount' => 50000.0,
                'checkout_url' => null,
                'qr_string' => null,
            ],
        ]);

        Artisan::call('orders:recover-stale-charges', ['--minutes' => 5]);

        $intent->refresh();

        // Must NOT be PAID (amount mismatch prevents settlement)
        $this->assertNotSame('PAID', $intent->gateway_status);

        // Must NOT be settled
        $this->assertNotSame('SETTLED', $intent->settlement_status);
        $this->assertNull($intent->settled_at);

        // Reservation must NOT be consumed
        $this->assertNotSame(MemberPaymentIntent::RESERVATION_CONSUMED, $intent->reservation_status);

        // No POS transaction created
        $this->assertSame(0, PosTransaction::count(), 'Task2: no POS transaction on amount mismatch');

        // Reconciliation incident must be recorded
        $this->assertDatabaseHas('payment_reconciliation_incidents', [
            'member_payment_intent_id' => $intent->id,
        ]);
    }

    // ── Task 3: Reject Empty/Malformed 2xx Provider Response ─────────────

    public function test_charge_200_empty_body_throws_unknown(): void
    {
        $intent = $this->createChargableIntent();

        // Mock HTTP: 200 OK but empty body
        \Illuminate\Support\Facades\Http::fake([
            '*/v2/charge' => \Illuminate\Support\Facades\Http::response([], 200),
        ]);

        $this->configureMidtransProvider();

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
            $this->fail('Expected ProviderChargeException for empty 2xx body.');
        } catch (ProviderChargeException $e) {
            $this->assertSame(ProviderChargeOutcome::Unknown, $e->outcome);
        }

        $intent->refresh();
        $this->assertSame('CHARGE_CREATING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_UNKNOWN, $attempt->state);
    }

    public function test_charge_200_malformed_body_throws_unknown(): void
    {
        $intent = $this->createChargableIntent();

        // Mock HTTP: 200 OK with body but no payment presentation (no qr_string, no actions, no redirect_url)
        \Illuminate\Support\Facades\Http::fake([
            '*/v2/charge' => \Illuminate\Support\Facades\Http::response([
                'status_code' => '201',
                'status_message' => 'OK',
                'transaction_id' => 'test-txn-123',
                'some_irrelevant_field' => 'value',
            ], 200),
        ]);

        $this->configureMidtransProvider();

        $service = app(PaymentIntentChargeService::class);

        try {
            $service->ensureCharge($intent->refresh());
            $this->fail('Expected ProviderChargeException for malformed 2xx body.');
        } catch (ProviderChargeException $e) {
            $this->assertSame(ProviderChargeOutcome::Unknown, $e->outcome);
        }

        $intent->refresh();
        $this->assertSame('CHARGE_CREATING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_UNKNOWN, $attempt->state);
    }

    public function test_charge_200_valid_qris_response_succeeds(): void
    {
        $intent = $this->createChargableIntent();

        // Mock HTTP: 201 Created with valid QRIS response
        \Illuminate\Support\Facades\Http::fake([
            '*/v2/charge' => \Illuminate\Support\Facades\Http::response([
                'status_code' => '201',
                'status_message' => 'Success, transaction is successful',
                'transaction_id' => 'test-txn-'.uniqid(),
                'order_id' => sprintf('KOJ-MPI-%d-1', $intent->id),
                'gross_amount' => '10000.00',
                'payment_type' => 'qris',
                'transaction_status' => 'pending',
                'fraud_status' => 'accept',
                'qr_string' => '00020101021126570011...',
            ], 201),
        ]);

        $this->configureMidtransProvider();

        $service = app(PaymentIntentChargeService::class);

        $charge = $service->ensureCharge($intent->refresh());

        $this->assertSame('PENDING', $charge['status']);
        $this->assertNotEmpty($charge['reference']);

        $intent->refresh();
        $this->assertSame('PENDING', $intent->gateway_status);

        $attempt = MemberPaymentChargeAttempt::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();
        $this->assertSame(MemberPaymentChargeAttempt::STATE_CONFIRMED, $attempt->state);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function createChargableIntent(): MemberPaymentIntent
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

        return MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'TEST-CHARGE-'.uniqid(),
            'request_fingerprint' => hash('sha256', uniqid()),
            'amount' => 20000,
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
    }

    private function createStaleChargeCreatingIntent(): MemberPaymentIntent
    {
        $intent = $this->createChargableIntent();

        $intent->forceFill([
            'gateway_status' => 'CHARGE_CREATING',
            'charge_attempt' => 1,
            'updated_at' => now()->subMinutes(10),
        ])->save();

        MemberPaymentChargeAttempt::query()->create([
            'member_payment_intent_id' => $intent->id,
            'attempt' => 1,
            'idempotency_key' => "member-intent:{$intent->id}:1",
            'provider_order_id' => "KOJ-MPI-{$intent->id}-1",
            'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
            'started_at' => now()->subMinutes(10),
        ]);

        return $intent->refresh();
    }

    /**
     * Create a stale CHARGE_CREATING intent with inventory location and stock
     * set up so that settlement can create a POS transaction.
     */
    private function createStaleChargeCreatingIntentWithInventory(): MemberPaymentIntent
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

        // Ensure inventory location and stock exist
        $inventory = app(PosInventoryService::class);
        $location = $inventory->ensureDefaultLocation();
        $inventory->syncDefaultLocationStocks($location->id);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $user->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'client_reference' => 'TEST-CHARGE-'.uniqid(),
            'request_fingerprint' => hash('sha256', uniqid()),
            'amount' => 20000,
            'channel' => 'QRIS',
            'gateway_status' => 'CHARGE_CREATING',
            'charge_attempt' => 1,
            'reservation_status' => 'RESERVED',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->addHour(),
            'updated_at' => now()->subMinutes(10),
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

        MemberPaymentChargeAttempt::query()->create([
            'member_payment_intent_id' => $intent->id,
            'attempt' => 1,
            'idempotency_key' => "member-intent:{$intent->id}:1",
            'provider_order_id' => "KOJ-MPI-{$intent->id}-1",
            'state' => MemberPaymentChargeAttempt::STATE_UNKNOWN,
            'started_at' => now()->subMinutes(10),
        ]);

        return $intent->refresh();
    }

    private function bindFailingProvider(ProviderChargeException $exception): void
    {
        $this->app->bind(PaymentGatewayProvider::class, function () use ($exception) {
            return new class($exception) implements PaymentGatewayProvider
            {
                public function __construct(private ProviderChargeException $exception) {}

                public function isConfigured(): bool
                {
                    return true;
                }

                public function createIntentCharge(\App\Models\MemberPaymentIntent $intent): array
                {
                    throw $this->exception;
                }

                public function reconcileIntentCharge(string $providerOrderId): ?array
                {
                    return null;
                }

                public function createCharge(\App\Models\CooperativePayment $payment, string $channel): array
                {
                    return [];
                }

                public function verifyWebhook(array $payload, array $headers): bool
                {
                    return false;
                }

                public function parseWebhook(array $payload): WebhookEvent
                {
                    throw new \RuntimeException('Not implemented');
                }

                public function acknowledgeResponse(): mixed
                {
                    return null;
                }
            };
        });

        config(['services.midtrans.server_key' => 'test-configured']);
    }

    /**
     * @param  array<string, array<string, mixed>>  $charges
     */
    private function bindReconcilingProvider(array $charges): void
    {
        $this->app->bind(PaymentGatewayProvider::class, function () use ($charges) {
            return new class($charges) implements PaymentGatewayProvider
            {
                /** @param  array<string, array<string, mixed>>  $charges */
                public function __construct(private array $charges) {}

                public function isConfigured(): bool
                {
                    return true;
                }

                public function createIntentCharge(\App\Models\MemberPaymentIntent $intent): array
                {
                    $orderId = sprintf('KOJ-MPI-%d-%d', $intent->id, $intent->charge_attempt ?: 1);

                    return $this->charges[$orderId] ?? [
                        'provider' => 'fake',
                        'reference' => $orderId,
                        'status' => 'PENDING',
                        'channel' => 'QRIS',
                        'amount' => (float) $intent->amount,
                        'checkout_url' => null,
                        'qr_string' => null,
                    ];
                }

                public function reconcileIntentCharge(string $providerOrderId): ?array
                {
                    return $this->charges[$providerOrderId] ?? null;
                }

                public function createCharge(\App\Models\CooperativePayment $payment, string $channel): array
                {
                    return [];
                }

                public function verifyWebhook(array $payload, array $headers): bool
                {
                    return false;
                }

                public function parseWebhook(array $payload): WebhookEvent
                {
                    throw new \RuntimeException('Not implemented');
                }

                public function acknowledgeResponse(): mixed
                {
                    return null;
                }
            };
        });

        config(['services.midtrans.server_key' => 'test-configured']);
    }

    /**
     * Bind the real MidtransPaymentProvider with a configured server key
     * so that the provider path is used (not the internal fallback).
     */
    private function configureMidtransProvider(): void
    {
        $this->app->bind(PaymentGatewayProvider::class, function () {
            return new \App\Services\Integrations\MidtransPaymentProvider;
        });

        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.is_production' => false,
        ]);
    }
}
