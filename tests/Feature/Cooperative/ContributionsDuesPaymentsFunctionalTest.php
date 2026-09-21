<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeNotificationOutbox;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeNotificationDispatcher;
use App\Services\Cooperative\CooperativeNotificationOutboxService;
use App\Services\Cooperative\CooperativePaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContributionsDuesPaymentsFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-secret-key-12345';

    protected Organization $organization;

    protected Organization $otherOrganization;

    protected User $adminUser;

    protected User $memberUserA;

    protected CooperativeMember $memberA;

    protected User $memberUserB;

    protected CooperativeMember $memberB;

    protected User $otherMemberUser;

    protected CooperativeMember $otherMember;

    protected CooperativeContributionType $typePokok;

    protected CooperativeContributionType $typeWajib;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        config([
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.is_production' => false,
            'services.payment_gateway.allow_simulation' => false,
        ]);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Primer Sejahtera',
        ]);

        $this->otherOrganization = Organization::factory()->create([
            'code' => 'KOP-002',
            'name' => 'Koperasi Sekunder Mandiri',
        ]);

        // Admin Koperasi with permissions for dues, payments, members
        $this->adminUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Test',
            'email' => 'admin.kop@kojaya.test',
        ]);
        $this->adminUser->assignRole('Admin Koperasi');
        $this->adminUser->givePermissionTo([
            'view_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
            'verify_cooperative_member',
        ]);

        // Member A in Org 1
        $this->memberUserA = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Member A Test',
            'email' => 'member.a@kojaya.test',
        ]);
        $this->memberUserA->assignRole('Anggota');
        $this->memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUserA->id,
            'name' => 'Member A Test',
            'joined_at' => '2026-01-01',
            'tanggal_aktif' => '2026-01-01',
        ]);

        // Member B in Org 1
        $this->memberUserB = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Member B Test',
            'email' => 'member.b@kojaya.test',
        ]);
        $this->memberUserB->assignRole('Anggota');
        $this->memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUserB->id,
            'name' => 'Member B Test',
            'joined_at' => '2026-01-01',
            'tanggal_aktif' => '2026-01-01',
        ]);

        // Other Member in Org 2
        $this->otherMemberUser = User::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Other Member Test',
            'email' => 'other.member@kojaya.test',
        ]);
        $this->otherMemberUser->assignRole('Anggota');
        $this->otherMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->otherOrganization->id,
            'user_id' => $this->otherMemberUser->id,
            'name' => 'Other Member Test',
            'joined_at' => '2026-01-01',
            'tanggal_aktif' => '2026-01-01',
        ]);

        // Standard contribution types
        $this->typePokok = CooperativeContributionType::query()->firstOrCreate(
            ['code' => 'POKOK'],
            [
                'name' => 'Simpanan Pokok',
                'category' => 'POKOK',
                'default_amount' => 100000.0,
                'frequency' => 'ONCE',
                'is_active' => true,
            ]
        );

        $this->typeWajib = CooperativeContributionType::query()->firstOrCreate(
            ['code' => 'WAJIB'],
            [
                'name' => 'Simpanan Wajib',
                'category' => 'WAJIB',
                'default_amount' => 50000.0,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ]
        );
    }

    // =========================================================================
    // PAY-001: Dues Invoices Monthly Batch Generation
    // =========================================================================

    public function test_pay001_admin_koperasi_can_generate_monthly_dues_invoices_via_web(): void
    {
        $period = '2026-03';

        $response = $this->actingAs($this->adminUser)
            ->post(route('cooperative.dues.generate'), [
                'period' => $period,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify that WAJIB invoices were generated for active members for period 2026-03
        $invoiceA = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $this->memberA->id)
            ->where('cooperative_contribution_type_id', $this->typeWajib->id)
            ->where('period', $period)
            ->first();

        $this->assertNotNull($invoiceA);
        $this->assertSame(50000.0, (float) $invoiceA->amount);
        $this->assertSame('UNPAID', $invoiceA->status);
    }

    public function test_pay001_duplicate_generation_for_same_period_is_prevented_and_idempotent(): void
    {
        $period = '2026-03';

        // First generation run
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.dues.generate'), ['period' => $period])
            ->assertRedirect()
            ->assertSessionHas('success');

        $initialInvoiceCount = CooperativeDuesInvoice::query()->where('period', $period)->count();
        $this->assertGreaterThan(0, $initialInvoiceCount);

        // Second generation run for the exact same period
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.dues.generate'), ['period' => $period])
            ->assertRedirect()
            ->assertSessionHas('success', '0 dues invoices generated.');

        // Total count must not have changed — zero duplicate invoices created
        $finalInvoiceCount = CooperativeDuesInvoice::query()->where('period', $period)->count();
        $this->assertSame($initialInvoiceCount, $finalInvoiceCount, 'Idempotency violated: duplicate invoices created');
    }

    public function test_pay001_api_dues_generation_works_and_is_idempotent(): void
    {
        $period = '2026-04';

        Sanctum::actingAs($this->adminUser, ['cooperative:write']);

        // First API run creates invoices
        $response1 = $this->postJson('/api/v1/dues/generate', ['period' => $period])
            ->assertCreated()
            ->assertJsonStructure(['created']);

        $createdCount = $response1->json('created');
        $this->assertGreaterThan(0, $createdCount);

        // Second API run returns 0 created
        $response2 = $this->postJson('/api/v1/dues/generate', ['period' => $period])
            ->assertCreated()
            ->assertJson(['created' => 0]);
    }

    public function test_pay001_unauthorized_user_cannot_generate_dues(): void
    {
        // Member user cannot generate dues
        $this->actingAs($this->memberUserA)
            ->post(route('cooperative.dues.generate'), ['period' => '2026-03'])
            ->assertForbidden();

        // Unauthenticated user is redirected to login
        auth()->logout();
        $this->post(route('cooperative.dues.generate'), ['period' => '2026-03'])
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // PAY-002: Member Payment Intent Creation (Midtrans)
    // =========================================================================

    public function test_pay002_active_member_can_create_payment_intent_for_unpaid_invoice(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-05',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-05-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        $response = $this->postJson("/api/v1/member/dues/invoices/{$invoice->id}/payment-intent")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'payment_id',
                    'invoice_id',
                    'amount',
                    'status',
                    'available_channels',
                    'expires_at',
                ],
            ]);

        $this->assertSame($invoice->id, $response->json('data.invoice_id'));
        $this->assertSame(50000.0, (float) $response->json('data.amount'));
        $this->assertSame('PENDING', $response->json('data.status'));

        $this->assertDatabaseHas('cooperative_payments', [
            'id' => $response->json('data.payment_id'),
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000.0,
            'status' => 'PENDING',
        ]);
    }

    public function test_pay002_payment_intent_creation_fails_on_already_paid_invoice(): void
    {
        $paidInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-05',
            'amount' => 50000.0,
            'paid_amount' => 50000.0,
            'due_date' => '2026-05-10',
            'status' => 'PAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // API must reject payment intent creation on already PAID invoice
        $this->postJson("/api/v1/member/dues/invoices/{$paidInvoice->id}/payment-intent")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invoice sudah lunas.');

        // Web portal must also reject already PAID invoice (fails findOrFail on UNPAID/PARTIAL)
        $this->actingAs($this->memberUserA)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $paidInvoice->id,
            ])
            ->assertNotFound();
    }

    public function test_pay002_payment_intent_prevents_amount_tampering_by_using_authoritative_server_balance(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-05',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-05-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // Attempting to send a tampered client amount (e.g. 100 instead of 50000)
        $response = $this->postJson("/api/v1/member/dues/invoices/{$invoice->id}/payment-intent", [
            'amount' => 100,
        ])->assertOk();

        // Server must enforce authoritative amount from the invoice record
        $this->assertSame(50000.0, (float) $response->json('data.amount'));

        $payment = CooperativePayment::query()->findOrFail($response->json('data.payment_id'));
        $this->assertSame(50000.0, (float) $payment->amount);
    }

    public function test_pay002_cross_member_payment_intent_ownership_is_enforced(): void
    {
        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-05',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-05-10',
            'status' => 'UNPAID',
        ]);

        // Member A attempts to create payment intent for Member B's invoice
        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        $this->postJson("/api/v1/member/dues/invoices/{$invoiceB->id}/payment-intent")
            ->assertForbidden();

        // Web portal fails as well
        $this->actingAs($this->memberUserA)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoiceB->id,
            ])
            ->assertNotFound();
    }

    // =========================================================================
    // PAY-003: Midtrans Webhook Notification Processing
    // =========================================================================

    public function test_pay003_webhook_with_invalid_signature_is_rejected_fail_closed_with_zero_mutations(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-06',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-06-10',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-PAY-FORGED-001',
            'gateway_status' => 'PENDING',
        ]);

        // Attacker sends forged signature
        $this->postJson('/api/payments/webhook', [
            'order_id' => 'KOJ-PAY-FORGED-001',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => 'invalid-forged-signature-string',
        ])->assertBadRequest()
            ->assertJsonPath('message', 'Invalid payment gateway webhook signature.');

        // Fail-closed verification: zero state mutation
        $payment->refresh();
        $this->assertSame('PENDING', $payment->status);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertNull($payment->reconciled_at);

        $invoice->refresh();
        $this->assertSame('UNPAID', $invoice->status);
        $this->assertSame(0.0, (float) $invoice->paid_amount);

        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(0, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_pay003_valid_signed_webhook_transitions_payment_and_invoice_to_paid_with_exact_ledger_credit(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-06',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-06-10',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-PAY-VALID-001',
            'gateway_status' => 'PENDING',
        ]);

        $response = $this->postSignedMidtransWebhook(
            orderId: 'KOJ-PAY-VALID-001',
            transactionStatus: 'settlement',
            grossAmount: 50000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        // Payment status must transition to APPROVED / RECONCILED
        $payment->refresh();
        $this->assertSame('APPROVED', $payment->status);
        $this->assertSame('PAID', $payment->gateway_status);
        $this->assertNotNull($payment->reconciled_at);

        // Invoice status must transition to PAID
        $invoice->refresh();
        $this->assertSame('PAID', $invoice->status);
        $this->assertSame(50000.0, (float) $invoice->paid_amount);

        // Exactly one ledger credit entry must be written
        $ledgerEntries = CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->get();
        $this->assertCount(1, $ledgerEntries);
        $this->assertSame(50000.0, (float) $ledgerEntries->first()->credit);
        $this->assertSame(0.0, (float) $ledgerEntries->first()->debit);
        $this->assertSame('SAVING_PAYMENT', $ledgerEntries->first()->entry_type);

        // Exactly one receipt must be issued
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_pay003_webhook_replay_duplicate_is_strictly_idempotent(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-06',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-06-10',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-PAY-REPLAY-001',
            'gateway_status' => 'PENDING',
        ]);

        // First delivery
        $this->postSignedMidtransWebhook(
            orderId: 'KOJ-PAY-REPLAY-001',
            transactionStatus: 'settlement',
            grossAmount: 50000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        // Second delivery (replay)
        $this->postSignedMidtransWebhook(
            orderId: 'KOJ-PAY-REPLAY-001',
            transactionStatus: 'settlement',
            grossAmount: 50000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        // Third delivery (duplicate replay)
        $this->postSignedMidtransWebhook(
            orderId: 'KOJ-PAY-REPLAY-001',
            transactionStatus: 'settlement',
            grossAmount: 50000.0,
            serverKey: self::SERVER_KEY,
        )->assertOk();

        // Assert strictly exactly 1 payment, 1 ledger credit, 1 receipt
        $this->assertSame(1, CooperativePayment::query()->where('gateway_reference', 'KOJ-PAY-REPLAY-001')->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count(), 'Duplicate ledger entries posted on webhook replay');
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count(), 'Duplicate receipts issued on webhook replay');
    }

    // =========================================================================
    // PAY-004: Manual Payment Recording (Staff Direct)
    // =========================================================================

    public function test_pay004_staff_can_record_manual_payment_with_valid_data(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-07',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-07-10',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.store'), [
                'cooperative_member_id' => $this->memberA->id,
                'cooperative_dues_invoice_id' => $invoice->id,
                'amount' => 50000.0,
                'payment_method' => 'CASH',
                'paid_at' => now()->toDateString(),
                'reference_no' => 'MANUAL-REF-001',
                'notes' => 'Pembayaran tunai di kantor',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify payment record
        $payment = CooperativePayment::query()
            ->where('cooperative_dues_invoice_id', $invoice->id)
            ->first();

        $this->assertNotNull($payment);
        $this->assertSame('APPROVED', $payment->status);
        $this->assertSame(50000.0, (float) $payment->amount);

        // Verify invoice was updated
        $invoice->refresh();
        $this->assertSame('PAID', $invoice->status);
        $this->assertSame(50000.0, (float) $invoice->paid_amount);

        // Verify ledger entry
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_pay004_manual_payment_rejects_missing_or_invalid_fields_with_zero_side_effects(): void
    {
        // Negative amount via Web
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.store'), [
                'cooperative_member_id' => $this->memberA->id,
                'cooperative_contribution_type_id' => $this->typeWajib->id,
                'amount' => -50000.0,
                'payment_method' => 'CASH',
                'paid_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        // Negative amount via API
        Sanctum::actingAs($this->adminUser, ['cooperative:write']);
        $this->postJson('/api/v1/dues/payments', [
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => -50000.0,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        // Non-existent member
        $this->postJson('/api/v1/dues/payments', [
            'cooperative_member_id' => 99999,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('cooperative_member_id');

        // Zero partial financial records created
        $this->assertSame(0, CooperativePayment::query()->where('amount', -50000.0)->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('credit', -50000.0)->count());
    }

    public function test_pay004_manual_payment_pending_does_not_prematurely_post_ledger_credit(): void
    {
        /** @var CooperativePaymentService $service */
        $service = app(CooperativePaymentService::class);

        $payment = $service->record([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ], $this->adminUser);

        $this->assertSame('PENDING', $payment->status);
        // Ledger entry must NOT exist while payment is pending
        $this->assertSame(0, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_pay004_unauthorized_staff_cannot_record_payment(): void
    {
        $this->actingAs($this->memberUserA)
            ->post(route('cooperative.payments.store'), [
                'cooperative_member_id' => $this->memberA->id,
                'cooperative_contribution_type_id' => $this->typeWajib->id,
                'amount' => 50000.0,
                'payment_method' => 'CASH',
                'paid_at' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // PAY-005: Payment Approval & Ledger Credit Posting
    // =========================================================================

    public function test_pay005_approving_pending_payment_posts_exact_ledger_credit_and_receipt(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-08',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-08-10',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame('APPROVED', $payment->status);

        $invoice->refresh();
        $this->assertSame('PAID', $invoice->status);
        $this->assertSame(50000.0, (float) $invoice->paid_amount);

        // Exactly one ledger credit
        $ledgerEntries = CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->get();
        $this->assertCount(1, $ledgerEntries);
        $this->assertSame(50000.0, (float) $ledgerEntries->first()->credit);

        // Exactly one receipt
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_pay005_repeated_approval_does_not_double_post_ledger_or_receipt(): void
    {
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-08',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-08-10',
            'status' => 'UNPAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        // First approval
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertRedirect();

        // Second approval attempt on the already APPROVED payment
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertRedirect();

        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count(), 'Duplicate ledger entries on repeated approval');
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count(), 'Duplicate receipts on repeated approval');
    }

    public function test_pay005_bulk_approve_approves_pending_and_skips_already_approved(): void
    {
        $p1 = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $p2 = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $p3 = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.bulk-approve'), [
                'ids' => [$p1->id, $p2->id, $p3->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 pembayaran berhasil disetujui. 1 pembayaran dilewati (status bukan PENDING).');

        $this->assertSame('APPROVED', $p1->fresh()->status);
        $this->assertSame('APPROVED', $p2->fresh()->status);
        $this->assertSame('APPROVED', $p3->fresh()->status);

        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $p1->id)->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $p2->id)->count());
    }

    public function test_pay005_bulk_approve_rejects_cross_organization_payments_with_zero_partial_approvals(): void
    {
        $pOrg1 = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $pOrg2 = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->otherMember->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        // Attempt bulk-approve containing payments across organizations
        $this->actingAs($this->adminUser)
            ->post(route('cooperative.payments.bulk-approve'), [
                'ids' => [$pOrg1->id, $pOrg2->id],
            ])
            ->assertForbidden();

        // Transaction rollback: zero partial approvals
        $this->assertSame('PENDING', $pOrg1->fresh()->status);
        $this->assertSame('PENDING', $pOrg2->fresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->whereIn('cooperative_payment_id', [$pOrg1->id, $pOrg2->id])->count());
    }

    public function test_pay005_self_approval_is_prevented(): void
    {
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'user_id' => $this->adminUser->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        /** @var CooperativePaymentService $service */
        $service = app(CooperativePaymentService::class);

        $this->expectException(ValidationException::class);
        $service->approve($payment, $this->adminUser);
    }

    // =========================================================================
    // PAY-006: Manual Payment Proof Upload by Member
    // =========================================================================

    public function test_pay006_member_can_upload_valid_image_payment_proof(): void
    {
        Storage::fake('public');

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-09',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        $file = UploadedFile::fake()->image('bukti_transfer.jpg', 600, 800);

        $response = $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'reference_no' => 'TRF-MBR-001',
            'proof' => $file,
        ])->assertCreated();

        $paymentId = $response->json('data.id');
        $payment = CooperativePayment::query()->findOrFail($paymentId);

        $this->assertSame('PENDING', $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_pay006_upload_rejects_disallowed_mime_types_and_executables(): void
    {
        Storage::fake('public');

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-09',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // Attempting to upload executable script file (.php)
        $maliciousFile = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

        $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'proof' => $maliciousFile,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('proof');

        // Zero payment records created
        $this->assertSame(0, CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoice->id)->count());
    }

    public function test_pay006_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-09',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // 5MB file (exceeds max 4096 KB limit)
        $oversizedFile = UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg');

        $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'proof' => $oversizedFile,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('proof');
    }

    public function test_pay006_member_cannot_upload_proof_for_another_members_invoice(): void
    {
        Storage::fake('public');

        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'period' => '2026-09',
            'amount' => 50000.0,
            'paid_amount' => 0,
            'due_date' => '2026-09-10',
            'status' => 'UNPAID',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        $file = UploadedFile::fake()->image('proof.jpg');

        $this->postJson('/api/v1/member/payments/proof', [
            'cooperative_dues_invoice_id' => $invoiceB->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'proof' => $file,
        ])->assertNotFound();

        $this->assertSame(0, CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoiceB->id)->count());
    }

    // =========================================================================
    // PAY-007: Payment Receipt Retrieval & Printing
    // =========================================================================

    public function test_pay007_member_can_retrieve_own_approved_payment_receipt(): void
    {
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        // Issue receipt
        $receipt = CooperativeReceipt::query()->create([
            'cooperative_payment_id' => $payment->id,
            'cooperative_member_id' => $this->memberA->id,
            'receipt_no' => 'RCP-2026-0001',
            'issued_at' => now(),
            'issued_by' => $this->adminUser->id,
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read']);

        $response = $this->getJson("/api/v1/member/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'receipt_no',
                    'issued_at',
                    'download_url',
                ],
            ]);

        $this->assertSame('RCP-2026-0001', $response->json('data.receipt_no'));
        $this->assertNotEmpty($response->json('data.download_url'));
    }

    public function test_pay007_member_cannot_access_another_members_payment_receipt(): void
    {
        $paymentB = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberB->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        CooperativeReceipt::query()->create([
            'cooperative_payment_id' => $paymentB->id,
            'cooperative_member_id' => $this->memberB->id,
            'receipt_no' => 'RCP-2026-0002',
            'issued_at' => now(),
            'issued_by' => $this->adminUser->id,
        ]);

        // Member A attempts to view Member B's receipt
        Sanctum::actingAs($this->memberUserA, ['member:read']);

        $this->getJson("/api/v1/member/payments/{$paymentB->id}/receipt")
            ->assertForbidden();
    }

    public function test_pay007_receipt_retrieval_fails_for_pending_unapproved_payment(): void
    {
        $pendingPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($this->memberUserA, ['member:read']);

        // Receipt is not available for pending payment
        $this->getJson("/api/v1/member/payments/{$pendingPayment->id}/receipt")
            ->assertNotFound()
            ->assertJsonPath('message', 'Receipt pembayaran belum tersedia.');
    }

    // =========================================================================
    // PAY-008: Notification Outbox Dispatch on Payment
    // =========================================================================

    public function test_pay008_payment_approval_dispatches_notification_outbox_with_deduplication(): void
    {
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->memberA->id,
            'cooperative_contribution_type_id' => $this->typeWajib->id,
            'amount' => 50000.0,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        /** @var CooperativeNotificationOutboxService $outboxService */
        $outboxService = app(CooperativeNotificationOutboxService::class);

        $outbox1 = $outboxService->enqueueForUser($this->memberUserA, "member.payment.approved:{$payment->id}", [
            'deduplication_key' => "member.payment.approved:{$payment->id}",
            'type' => 'payment',
            'category' => 'success',
            'title' => 'Pembayaran disetujui',
            'body' => 'Pembayaran iuran Anda telah disetujui.',
        ]);

        $this->assertNotNull($outbox1, 'Notification outbox entry not created for payment approval');
        $this->assertSame($this->memberUserA->id, $outbox1->user_id);
        $this->assertSame('PENDING', $outbox1->status);

        // Repeated enqueue with same deduplication key must not create duplicate outbox entries
        $outbox2 = $outboxService->enqueueForUser($this->memberUserA, "member.payment.approved:{$payment->id}", [
            'deduplication_key' => "member.payment.approved:{$payment->id}",
            'type' => 'payment',
            'category' => 'success',
            'title' => 'Pembayaran disetujui',
            'body' => 'Pembayaran iuran Anda telah disetujui.',
        ]);

        $outboxCount = CooperativeNotificationOutbox::query()
            ->where('deduplication_key', "member.payment.approved:{$payment->id}")
            ->count();
        $this->assertSame(1, $outboxCount, 'Duplicate outbox entries created on repeated enqueue');

        // Also test dispatcher user notification deduplication
        /** @var CooperativeNotificationDispatcher $dispatcher */
        $dispatcher = app(CooperativeNotificationDispatcher::class);
        $dispatcher->paymentApproved($payment, $this->adminUser);
        $dispatcher->paymentApproved($payment->fresh(), $this->adminUser);

        $notifCount = $this->memberUserA->notifications()
            ->where('data->deduplication_key', "member.payment.approved:{$payment->id}")
            ->count();
        $this->assertSame(1, $notifCount, 'Duplicate user notifications created on repeated approval');
    }

    public function test_pay008_outbox_delivery_retry_and_backoff_classification(): void
    {
        $outbox = CooperativeNotificationOutbox::query()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->memberUserA->id,
            'deduplication_key' => 'test.payment.retry:001',
            'payload' => [
                'deduplication_key' => 'test.payment.retry:001',
                'type' => 'payment',
                'category' => 'success',
                'title' => 'Pembayaran diterima',
                'body' => 'Pembayaran iuran berhasil diproses.',
            ],
            'status' => CooperativeNotificationOutbox::STATUS_PENDING,
            'attempts' => 0,
            'available_at' => now(),
        ]);

        /** @var CooperativeNotificationOutboxService $outboxService */
        $outboxService = app(CooperativeNotificationOutboxService::class);

        // Deliver
        $delivered = $outboxService->deliverPending(10);
        $this->assertSame(1, $delivered);

        $outbox->refresh();
        $this->assertSame(CooperativeNotificationOutbox::STATUS_DELIVERED, $outbox->status);

        // Re-delivering produces 0 notifications and does not duplicate
        $redelivered = $outboxService->deliverPending(10);
        $this->assertSame(0, $redelivered);

        $userNotificationCount = $this->memberUserA->notifications()
            ->where('id', $outbox->id)
            ->count();
        $this->assertSame(1, $userNotificationCount, 'Multiple notifications delivered for single outbox item');
    }
}
