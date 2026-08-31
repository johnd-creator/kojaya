<?php

namespace Tests\Feature\MemberPortal;

use App\Enums\InstallmentStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\MemberPaymentIntent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberPaymentIntentWebTest extends TestCase
{
    use RefreshDatabase;

    private User $memberUser;

    private CooperativeMember $member;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => '',
            'services.payment_gateway.allow_simulation' => true,
        ]);
        $this->seed(RolePermissionSeeder::class);

        $this->memberUser = User::factory()->create();
        $this->memberUser->assignRole('Anggota');

        $this->member = CooperativeMember::factory()->active()->create([
            'user_id' => $this->memberUser->id,
            'name' => $this->memberUser->name,
            'email' => $this->memberUser->email,
        ]);
    }

    public function test_member_can_create_core_api_payment_intent_for_dues_invoice(): void
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-QRIS',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 20000,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'PARTIAL',
        ]);

        $response = $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'channel' => 'QRIS',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.invoice_id', $invoice->id)
            ->assertJsonPath('data.amount', 80000)
            ->assertJsonPath('data.provider', 'internal')
            ->assertJsonPath('data.channel', 'QRIS')
            ->assertJsonMissingPath('data.qr_string')
            ->assertJsonPath('data.qr_image_url', '/api/v1/member/payments/'.$response->json('data.payment_id').'/qris-image')
            ->assertJsonPath('data.gateway_reference', $response->json('data.gateway_reference'));

        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 80000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'internal',
            'gateway_status' => 'PENDING',
            'status' => 'PENDING',
        ]);
    }

    public function test_payment_intent_is_idempotent_for_pending_payment(): void
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'POKOK-QRIS',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 500000,
            'frequency' => 'ONE_TIME',
            'is_active' => true,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 500000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        $first = $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $second = $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $this->assertSame($first->json('data.gateway_reference'), $second->json('data.gateway_reference'));
        $this->assertSame(1, CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoice->id)->count());
    }

    public function test_payment_intent_falls_back_to_va_when_selected_midtrans_channel_is_inactive(): void
    {
        config([
            'services.midtrans.server_key' => 'midtrans-server-key',
            'services.midtrans.is_production' => false,
            'services.midtrans.va_bank' => 'permata',
        ]);

        Http::fakeSequence('api.sandbox.midtrans.com/*')
            ->push([
                'status_code' => '402',
                'status_message' => 'Payment channel is not activated.',
            ], 200)
            ->push([
                'status_code' => '201',
                'transaction_status' => 'pending',
                'permata_va_number' => '8877000012345678',
                'expiry_time' => now()->addDay()->format('Y-m-d H:i:s'),
            ], 201);

        $invoice = $this->unpaidInvoice();

        $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'VA')
            ->assertJsonPath('data.requested_channel', 'QRIS')
            ->assertJsonPath('data.fallback_reason', 'MIDTRANS_CHANNEL_INACTIVE')
            ->assertJsonPath('data.instructions.bank', 'PERMATA')
            ->assertJsonPath('data.instructions.va_number', '8877000012345678');

        Http::assertSentCount(2);

        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $invoice->id,
            'payment_method' => 'VA',
            'gateway_provider' => 'midtrans',
            'gateway_status' => 'PENDING',
        ]);
    }

    public function test_midtrans_qris_payment_intent_returns_server_qr_image_url_without_raw_action_url(): void
    {
        config([
            'services.midtrans.server_key' => 'midtrans-server-key',
            'services.midtrans.is_production' => false,
            'services.midtrans.qris_acquirer' => 'gopay',
        ]);

        Http::fake(function ($request) {
            $payload = $request->data();

            $this->assertSame('https://api.sandbox.midtrans.com/v2/charge', $request->url());
            $this->assertSame('qris', $payload['payment_type'] ?? null);
            $this->assertSame('gopay', $payload['qris']['acquirer'] ?? null);
            $this->assertSame(75000, $payload['transaction_details']['gross_amount'] ?? null);

            return Http::response([
                'status_code' => '201',
                'transaction_status' => 'pending',
                'order_id' => $payload['transaction_details']['order_id'],
                'gross_amount' => '75000.00',
                'actions' => [
                    [
                        'name' => 'generate-qr-code-v2',
                        'method' => 'GET',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/qr-code',
                    ],
                ],
                'expiry_time' => '2026-07-02 10:00:00',
            ], 201);
        });

        $invoice = $this->unpaidInvoice();

        $response = $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'channel' => 'QRIS',
            ]);

        $paymentId = $response->json('data.payment_id');

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'midtrans')
            ->assertJsonPath('data.channel', 'QRIS')
            ->assertJsonPath('data.amount', 75000)
            ->assertJsonPath('data.qr_image_url', '/api/v1/member/payments/'.$paymentId.'/qris-image')
            ->assertJsonPath('data.instructions.title', 'Scan QRIS untuk membayar')
            ->assertJsonMissingPath('data.qr_string')
            ->assertJsonMissingPath('data.instructions.qr_action_url');

        $payment = CooperativePayment::query()->findOrFail($paymentId);

        $this->assertSame('midtrans', $payment->gateway_provider);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/qr-code', $payment->gateway_payload['actions'][0]['url'] ?? null);
        $this->assertSame('/api/v1/member/payments/'.$paymentId.'/qris-image', $payment->gateway_payload['presentation']['qr_image_url'] ?? null);
    }

    public function test_payment_status_reflects_gateway_status(): void
    {
        $invoice = $this->unpaidInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 75000,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_status' => 'PENDING',
        ]);

        $this->actingAs($this->memberUser)
            ->getJson(route('member.payments.status', $payment))
            ->assertOk()
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.gateway_status', 'PENDING')
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.is_failed', false)
            ->assertJsonPath('data.is_terminal', false);

        $payment->forceFill(['gateway_status' => 'PAID', 'status' => 'APPROVED'])->save();

        $this->actingAs($this->memberUser)
            ->getJson(route('member.payments.status', $payment))
            ->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID')
            ->assertJsonPath('data.is_paid', true)
            ->assertJsonPath('data.is_terminal', true);
    }

    public function test_payment_status_signals_terminal_failure_and_expiry(): void
    {
        $expiresAt = now()->addHour();

        $invoice = $this->unpaidInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 75000,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'provider' => 'midtrans',
                'reference' => 'KOJ-1-EXPIRY',
                'status' => 'PENDING',
                'channel' => 'QRIS',
                'amount' => 75000,
                'checkout_url' => null,
                'qr_string' => '000201...',
                'expires_at' => $expiresAt->toIso8601String(),
                'instructions' => [],
            ],
        ]);

        // Pending payment exposes expiry so frontend can stop polling when due.
        $this->actingAs($this->memberUser)
            ->getJson(route('member.payments.status', $payment))
            ->assertOk()
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.is_failed', false)
            ->assertJsonPath('data.is_terminal', false)
            ->assertJsonPath('data.gateway_expires_at', $expiresAt->startOfSecond()->toIso8601String());

        // Midtrans maps expire/cancel/deny to FAILED/CANCELLED via webhook.
        $payment->forceFill(['gateway_status' => 'FAILED'])->save();

        $this->actingAs($this->memberUser)
            ->getJson(route('member.payments.status', $payment))
            ->assertOk()
            ->assertJsonPath('data.gateway_status', 'FAILED')
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.is_failed', true)
            ->assertJsonPath('data.is_terminal', true);
    }

    public function test_payment_intent_blocks_other_members_invoice(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Anggota');
        $otherMember = CooperativeMember::factory()->active()->create([
            'user_id' => $otherUser->id,
            'name' => $otherUser->name,
            'email' => $otherUser->email,
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-OTHER',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
            ])
            ->assertNotFound();
    }

    public function test_payment_intent_rejects_paid_invoice(): void
    {
        $invoice = $this->unpaidInvoice();
        $invoice->forceFill(['status' => 'PAID', 'paid_amount' => $invoice->amount])->save();

        $this->actingAs($this->memberUser)
            ->postJson(route('member.payments.intent'), [
                'cooperative_dues_invoice_id' => $invoice->id,
            ])
            ->assertNotFound();
    }

    public function test_payment_status_blocks_other_members_payment(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Anggota');
        $otherMember = CooperativeMember::factory()->active()->create([
            'user_id' => $otherUser->id,
            'name' => $otherUser->name,
            'email' => $otherUser->email,
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'amount' => 75000,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_status' => 'PENDING',
        ]);

        $this->actingAs($this->memberUser)
            ->getJson(route('member.payments.status', $payment))
            ->assertForbidden();
    }

    public function test_member_cannot_create_loan_payment_intent_for_another_member_installment(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Anggota');
        $otherMember = CooperativeMember::factory()->active()->create(['user_id' => $otherUser->id]);
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $otherMember->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'status' => InstallmentStatus::Pending,
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
            ])
            ->assertNotFound();
    }

    public function test_fully_paid_loan_installment_is_rejected(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 100000,
            'status' => InstallmentStatus::Paid,
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
            ])
            ->assertNotFound();
    }

    public function test_pending_loan_payment_intent_is_reused_safely(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);

        $first = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
            ])
            ->assertCreated();
        $second = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
            ])
            ->assertCreated();

        $this->assertSame(
            $first->json('data.payment_intent.id'),
            $second->json('data.payment_intent.id'),
        );
        $this->assertSame(
            $first->json('data.charge.reference'),
            $second->json('data.charge.reference'),
        );
        $this->assertFalse($first->json('data.charge.reused'));
        $this->assertTrue($second->json('data.charge.reused'));
        $this->assertSame(1, MemberPaymentIntent::query()->count());
    }

    public function test_expired_internal_loan_intent_is_replaced_with_fresh_charge(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);

        $first = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $oldIntent = MemberPaymentIntent::query()->findOrFail($first->json('data.payment_intent.id'));
        $oldReference = $oldIntent->gateway_reference;
        $oldIntent->forceFill(['expires_at' => now()->subMinute()])->save();

        $second = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $newIntent = MemberPaymentIntent::query()->findOrFail($second->json('data.payment_intent.id'));

        $this->assertNotSame($oldIntent->id, $newIntent->id);
        $this->assertNotSame($oldReference, $newIntent->gateway_reference);
        $this->assertSame('EXPIRED', $oldIntent->refresh()->gateway_status);
        $this->assertTrue($newIntent->expires_at?->isFuture());
        $this->assertFalse($second->json('data.charge.reused'));
        $this->assertSame(2, MemberPaymentIntent::query()->count());
    }

    public function test_channel_change_reuses_active_charge_and_reports_actual_channel(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $second = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'VA',
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment_intent.channel', 'QRIS')
            ->assertJsonPath('data.payment_intent.requested_channel', 'VA')
            ->assertJsonPath('data.charge.channel', 'QRIS')
            ->assertJsonPath('data.charge.requested_channel', 'VA')
            ->assertJsonPath('data.charge.reused', true);

        $this->assertSame(1, MemberPaymentIntent::query()->count());
        $this->assertSame('QRIS', $second->json('data.charge.channel'));
    }

    public function test_active_stale_amount_fails_closed_without_parallel_intent(): void
    {
        config(['services.midtrans.server_key' => 'midtrans-server-key']);

        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 20000,
            'status' => InstallmentStatus::Partial,
        ]);
        $reference = 'KOJ-MPI-STALE-AMOUNT-1';

        $intent = MemberPaymentIntent::factory()->create([
            'user_id' => $this->memberUser->id,
            'cooperative_member_id' => $this->member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $installment->id,
            'amount' => 100000,
            'channel' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => $reference,
            'gateway_status' => 'PENDING',
            'settlement_status' => 'NOT_SETTLED',
            'expires_at' => now()->addHour(),
            'gateway_payload' => [
                'provider' => 'midtrans',
                'reference' => $reference,
                'status' => 'PENDING',
                'channel' => 'QRIS',
                'amount' => '100000.00',
                'amount_minor' => 10000000,
                'qr_string' => '000201...',
                'expires_at' => now()->addHour()->toIso8601String(),
                'instructions' => [],
            ],
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'transaction_status' => 'pending',
                'order_id' => $reference,
                'gross_amount' => '100000.00',
                'payment_type' => 'qris',
            ], 200),
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'LOAN_PAYMENT_INTENT_AMOUNT_STALE');

        $this->assertSame(1, MemberPaymentIntent::query()->count());
        $this->assertSame('PENDING', $intent->refresh()->gateway_status);
        Http::assertSentCount(1);
    }

    public function test_expired_midtrans_intent_gets_new_order_and_idempotency_identity(): void
    {
        config(['services.midtrans.server_key' => 'midtrans-server-key']);

        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);

        Http::fake(function ($request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'transaction_status' => 'expire',
                    'order_id' => 'KOJ-MPI-OLD-1',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'qris',
                ], 200);
            }

            $payload = $request->data();
            $orderId = $payload['transaction_details']['order_id'];

            return Http::response([
                'status_code' => '201',
                'transaction_status' => 'pending',
                'order_id' => $orderId,
                'gross_amount' => '100000.00',
                'qr_string' => '000201-'.$orderId,
                'expiry_time' => now()->addDay()->format('Y-m-d H:i:s'),
            ], 201);
        });

        $first = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $oldIntent = MemberPaymentIntent::query()->findOrFail($first->json('data.payment_intent.id'));
        $oldIntent->forceFill(['expires_at' => now()->subMinute()])->save();

        $second = $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
                'channel' => 'QRIS',
            ])
            ->assertCreated();

        $postRequests = collect(Http::recorded())->filter(fn ($pair): bool => $pair[0]->method() === 'POST');
        $idempotencyKeys = $postRequests->map(fn ($pair): ?string => $pair[0]->header('Idempotency-Key')[0] ?? null)->values();

        $this->assertNotSame($first->json('data.charge.reference'), $second->json('data.charge.reference'));
        $this->assertCount(2, $idempotencyKeys->unique());
        $this->assertSame(2, MemberPaymentIntent::query()->count());
        $this->assertSame('EXPIRED', $oldIntent->refresh()->gateway_status);
    }

    public function test_paid_loan_intent_is_not_replaced(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Pending,
        ]);
        $intent = MemberPaymentIntent::factory()->create([
            'user_id' => $this->memberUser->id,
            'cooperative_member_id' => $this->member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $installment->id,
            'gateway_status' => 'PAID',
            'settlement_status' => 'SETTLING',
            'settled_at' => null,
        ]);

        $this->actingAs($this->memberUser)
            ->postJson(route('member.loans.installments.payment-intent'), [
                'loan_installment_id' => $installment->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'LOAN_PAYMENT_INTENT_ALREADY_PAID');

        $this->assertSame(1, MemberPaymentIntent::query()->count());
        $this->assertSame('PAID', $intent->refresh()->gateway_status);
    }

    public function test_expiry_service_expires_non_order_loan_intent_without_reservation_logic(): void
    {
        $loan = Loan::factory()->active()->create(['cooperative_member_id' => $this->member->id]);
        $installment = LoanInstallment::factory()->create(['loan_id' => $loan->id]);
        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $installment->id,
            'gateway_reference' => null,
            'gateway_status' => 'PENDING',
            'expires_at' => now()->subMinute(),
            'reservation_status' => null,
            'settlement_status' => 'NOT_SETTLED',
        ]);

        $this->assertTrue(app(\App\Services\Integrations\MemberPaymentIntentStateService::class)->expireStaleIntent($intent));
        $this->assertSame('EXPIRED', $intent->refresh()->gateway_status);
        $this->assertNull($intent->refresh()->reservation_status);
    }

    public function test_loan_payment_intent_status_blocks_another_member(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Anggota');
        $otherMember = CooperativeMember::factory()->active()->create(['user_id' => $otherUser->id]);
        $intent = MemberPaymentIntent::factory()->create([
            'user_id' => $otherUser->id,
            'cooperative_member_id' => $otherMember->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'gateway_status' => 'PENDING',
        ]);

        $this->actingAs($this->memberUser)
            ->getJson(route('member.loans.payment-intents.status', $intent))
            ->assertForbidden();
    }

    private function unpaidInvoice(): CooperativeDuesInvoice
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-'.uniqid(),
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 75000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        return CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 75000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
    }
}
