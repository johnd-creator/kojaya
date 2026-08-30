<?php

namespace Tests\Feature\Security;

use App\Enums\PaymentGatewayStatus;
use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PaymentReconciliationIncident;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentWebhookFailClosedTest extends TestCase
{
    private const SERVER_KEY = 'test-midtrans-secret-key-12345';

    private Organization $organization;

    private User $memberUser;

    private CooperativeMember $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        config(['services.payment_gateway.allow_simulation' => false]);

        $this->organization = Organization::factory()->create();
        $this->memberUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUser->id,
        ]);
    }

    private function createPayment(float $amount = 100000.0, string $reference = 'KOJ-PAY-TEST-001'): CooperativePayment
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-TEST',
            'name' => 'Simpanan Wajib Test',
            'category' => 'WAJIB',
            'default_amount' => $amount,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => $amount,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        return CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => $reference,
            'gateway_status' => 'PENDING',
        ]);
    }

    private function createProduct(string $name = 'Test Product', float $price = 10000.0, int $stock = 20): PosProduct
    {
        $category = PosCategory::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-cat-'.uniqid(),
        ]);

        return PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'pos_category_id' => $category->id,
            'name' => $name.' '.uniqid(),
            'cost_price' => $price * 0.5,
            'sale_price' => $price,
            'stock' => $stock,
        ]);
    }

    private function createStoreOrderIntent(float $price = 10000.0, int $quantity = 2): MemberPaymentIntent
    {
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => true,
        ]);

        Sanctum::actingAs($this->memberUser, ['member:write']);
        $product = $this->createProduct(price: $price, stock: 20);

        $response = $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => $quantity]],
            'client_reference' => 'STORE-ORDER-'.uniqid(),
        ])->assertCreated();

        $intentId = $response->json('data.payment_intent_id');

        return MemberPaymentIntent::query()->findOrFail($intentId);
    }

    // ── FINDING 1 & ADDITIONAL: Gateway Charges Fail Closed When No Provider ──

    public function test_charge_fails_closed_when_provider_not_configured_for_cooperative_payment(): void
    {
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => false,
        ]);

        $payment = $this->createPayment(100000.0, '');
        $payment->forceFill(['gateway_reference' => null, 'gateway_provider' => null])->save();

        Sanctum::actingAs($this->memberUser, ['member:write']);

        $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'QRIS',
        ])->assertStatus(503)
            ->assertJsonPath('error_code', 'PAYMENT_GATEWAY_UNAVAILABLE')
            ->assertJsonPath('message', 'Payment gateway provider is not configured.');

        $payment->refresh();
        $this->assertNull($payment->gateway_reference);
        $this->assertNull($payment->gateway_provider);
        $this->assertSame('PENDING', $payment->status);
    }

    public function test_charge_fails_closed_when_provider_not_configured_for_member_payment_intent(): void
    {
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => false,
        ]);

        $loanType = \App\Models\LoanType::factory()->create();

        $loan = Loan::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'cooperative_member_id' => $this->member->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 5000000,
        ]);

        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_no' => 1,
            'due_date' => now()->addDay()->toDateString(),
            'principal_amount' => 400000,
            'interest_amount' => 60000,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 460000,
            'amount_paid' => 0,
            'paid_at' => null,
            'status' => \App\Enums\InstallmentStatus::Pending->value,
        ]);

        Sanctum::actingAs($this->memberUser, ['member:write']);

        $this->postJson('/api/v1/member/bills/loan:'.$installment->id.'/payment-intent', [
            'channel' => 'QRIS',
        ])->assertStatus(503)
            ->assertJsonPath('error_code', 'PAYMENT_GATEWAY_UNAVAILABLE');

        $intent = MemberPaymentIntent::query()->where('cooperative_member_id', $this->member->id)->first();
        $this->assertNotNull($intent);
        $this->assertNull($intent->gateway_reference);
        $this->assertNull($intent->gateway_provider);
        $this->assertNull($intent->settled_at);
    }

    public function test_store_order_fails_closed_when_provider_not_configured(): void
    {
        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => false,
        ]);

        Sanctum::actingAs($this->memberUser, ['member:write']);
        $product = $this->createProduct(price: 10000.0, stock: 20);

        $this->postJson('/api/v1/member/store/orders', [
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'client_reference' => 'STORE-FAIL-CLOSED-001',
        ])->assertStatus(503)
            ->assertJsonPath('error_code', 'PAYMENT_GATEWAY_UNAVAILABLE');
    }

    // ── FINDING 2: Semantic Verification Rules (Status Code & Fraud Status) ──

    public function test_valid_signature_with_status_code_201_pending_does_not_mark_paid_or_reconcile(): void
    {
        // Case A: Valid signature, status_code = 201, transaction_status = settlement
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment(100000.0);

        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            statusCode: '201',
            serverKey: self::SERVER_KEY,
        )->assertOk();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_valid_signature_with_capture_and_fraud_deny_does_not_mark_paid(): void
    {
        // Case B: Valid signature, status_code = 200, transaction_status = capture, fraud_status = deny
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment(100000.0);

        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'capture',
            grossAmount: 100000.0,
            statusCode: '200',
            fraudStatus: 'deny',
            serverKey: self::SERVER_KEY,
        )->assertOk();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('FAILED', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_valid_signature_with_capture_and_fraud_challenge_does_not_mark_paid(): void
    {
        // Case C: Valid signature, status_code = 200, transaction_status = capture, fraud_status = challenge
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment(100000.0);

        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'capture',
            grossAmount: 100000.0,
            statusCode: '200',
            fraudStatus: 'challenge',
            serverKey: self::SERVER_KEY,
        )->assertOk();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_valid_signature_with_settlement_and_fraud_accept_marks_paid_and_reconciles_once(): void
    {
        // Case D: Valid signature, status_code = 200, transaction_status = settlement, fraud_status = accept, correct amount
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment(100000.0);

        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            statusCode: '200',
            fraudStatus: 'accept',
            serverKey: self::SERVER_KEY,
            extra: ['reconciliation_reference' => 'RECON-CASE-D'],
        )->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID')
            ->assertJsonPath('data.status', 'APPROVED');

        $payment->refresh();
        $this->assertSame('PAID', $payment->gateway_status);
        $this->assertSame('APPROVED', $payment->status);
        $this->assertNotNull($payment->reconciled_at);
        $this->assertSame('RECON-CASE-D', $payment->reconciliation_reference);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    // ── Webhook Fail-Closed Invariants: Unsigned / Unconfigured ──────────

    public function test_unsigned_webhook_rejected_when_provider_not_configured(): void
    {
        config(['services.midtrans.server_key' => '']);

        $payment = $this->createPayment();

        $this->postJson('/api/payments/webhook', [
            'reference' => $payment->gateway_reference,
            'status' => 'PAID',
        ])->assertUnprocessable();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_webhook_with_payload_rejected_when_provider_not_configured(): void
    {
        config(['services.midtrans.server_key' => '']);

        $payment = $this->createPayment();

        $this->postJson('/api/payments/webhook', [
            'order_id' => $payment->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => 'some-signature',
        ])->assertBadRequest()
            ->assertJsonPath('message', 'Payment gateway provider is not configured.');

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
    }

    // ── Invalid / Missing Signature ──────────────────────────────────────

    public function test_invalid_signature_rejected_and_does_not_mutate_cooperative_payment(): void
    {
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment();

        $this->postJson('/api/payments/webhook', [
            'order_id' => $payment->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => 'invalid-forged-signature-value',
        ])->assertBadRequest()
            ->assertJsonPath('message', 'Invalid payment gateway webhook signature.');

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_invalid_signature_rejected_and_does_not_settle_member_payment_intent(): void
    {
        $intent = $this->createStoreOrderIntent(10000.0, 2);
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $this->postJson('/api/payments/webhook', [
            'order_id' => $intent->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '20000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => 'invalid-forged-signature-value',
        ])->assertBadRequest()
            ->assertJsonPath('message', 'Invalid payment gateway webhook signature.');

        $intent->refresh();
        $this->assertSame(PaymentGatewayStatus::Pending->value, $intent->gateway_status);
        $this->assertSame(PaymentSettlementStatus::NotSettled->value, $intent->settlement_status);
        $this->assertSame(PaymentReservationStatus::Reserved->value, $intent->reservation_status);
        $this->assertNull($intent->settled_at);
    }

    public function test_missing_signature_rejected(): void
    {
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment();

        $this->postJson('/api/payments/webhook', [
            'order_id' => $payment->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ])->assertUnprocessable();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->gateway_status);
    }

    // ── Forged Payload Protection ────────────────────────────────────────

    public function test_forged_paid_status_cannot_reconcile_payment(): void
    {
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        $payment = $this->createPayment();

        // Valid signature computed for 'pending' (201), but payload body tampered to 'settlement' (200)
        $pendingSignature = hash('sha512', $payment->gateway_reference.'201'.'100000.00'.self::SERVER_KEY);

        $this->postJson('/api/payments/webhook', [
            'order_id' => $payment->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => $pendingSignature,
        ])->assertBadRequest()
            ->assertJsonPath('message', 'Invalid payment gateway webhook signature.');

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
    }

    public function test_forged_member_payment_intent_paid_event_cannot_settle_intent(): void
    {
        $intent = $this->createStoreOrderIntent(10000.0, 2);
        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        // Attacker attempts to forge with wrong secret key hash
        $forgedSignature = hash('sha512', $intent->gateway_reference.'200'.'20000.00'.'attacker-key');

        $this->postJson('/api/payments/webhook', [
            'order_id' => $intent->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '20000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => $forgedSignature,
        ])->assertBadRequest();

        $intent->refresh();
        $this->assertSame(PaymentGatewayStatus::Pending->value, $intent->gateway_status);
        $this->assertSame(PaymentSettlementStatus::NotSettled->value, $intent->settlement_status);
        $this->assertNull($intent->settled_at);
    }

    // ── Valid Signed Intent Transition & Replay ──────────────────────────

    public function test_valid_signed_webhook_transitions_member_payment_intent_and_settles(): void
    {
        $intent = $this->createStoreOrderIntent(10000.0, 2);

        $this->postSignedMidtransWebhook(
            orderId: $intent->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 20000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID');

        $intent->refresh();
        $this->assertSame(PaymentGatewayStatus::Paid->value, $intent->gateway_status);
        $this->assertSame(PaymentSettlementStatus::Settled->value, $intent->settlement_status);
        $this->assertSame(PaymentReservationStatus::Consumed->value, $intent->reservation_status);
        $this->assertNotNull($intent->settled_at);
    }

    public function test_webhook_replay_is_idempotent_and_does_not_duplicate_side_effects(): void
    {
        $payment = $this->createPayment(100000.0);

        // First delivery
        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            serverKey: self::SERVER_KEY,
            extra: ['reconciliation_reference' => 'RECON-REPLAY-001'],
        )->assertOk();

        $payment->refresh();
        $reconciledAtFirst = $payment->reconciled_at?->toIso8601String();
        $ledgerCountFirst = CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count();

        $this->assertSame(1, $ledgerCountFirst);

        // Second delivery (replay)
        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            serverKey: self::SERVER_KEY,
            extra: ['reconciliation_reference' => 'RECON-REPLAY-001'],
        )->assertOk();

        $payment->refresh();
        $this->assertSame($reconciledAtFirst, $payment->reconciled_at?->toIso8601String());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_unknown_payment_reference_fails_safely_without_affecting_other_payments(): void
    {
        $payment = $this->createPayment(100000.0, 'KOJ-KNOWN-REF');

        $this->postSignedMidtransWebhook(
            orderId: 'KOJ-UNKNOWN-REF-999',
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            serverKey: self::SERVER_KEY,
        )->assertStatus(202)
            ->assertJsonPath('message', 'Webhook ignored.');

        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);
    }

    // ── Amount Mismatch Protection ────────────────────────────────────────

    public function test_amount_mismatch_creates_incident_and_does_not_mark_intent_paid(): void
    {
        $intent = $this->createStoreOrderIntent(10000.0, 2);

        // Webhook signed for 10,000 instead of 20,000
        $this->postSignedMidtransWebhook(
            orderId: $intent->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 10000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        $intent->refresh();
        $this->assertSame(PaymentGatewayStatus::Pending->value, $intent->gateway_status);
        $this->assertSame(PaymentSettlementStatus::NotSettled->value, $intent->settlement_status);
        $this->assertNull($intent->settled_at);

        $incident = PaymentReconciliationIncident::query()
            ->where('member_payment_intent_id', $intent->id)
            ->first();

        $this->assertNotNull($incident);
        $this->assertSame(PaymentReconciliationIncident::TYPE_AMOUNT_MISMATCH, $incident->incident_type);
    }

    public function test_amount_mismatch_does_not_mark_cooperative_payment_paid(): void
    {
        $payment = $this->createPayment(100000.0);

        // Webhook signed for 50,000 instead of 100,000
        $this->postSignedMidtransWebhook(
            orderId: $payment->gateway_reference,
            transactionStatus: 'settlement',
            grossAmount: 50000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        $payment->refresh();
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertSame('PENDING', $payment->status);
        $this->assertNull($payment->reconciled_at);
    }
}
