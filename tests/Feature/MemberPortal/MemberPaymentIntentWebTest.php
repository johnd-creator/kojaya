<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
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

        config(['services.midtrans.server_key' => '']);
        $this->seed(RolePermissionSeeder::class);

        $this->memberUser = User::factory()->create();
        $this->memberUser->assignRole('Anggota');

        $this->member = CooperativeMember::factory()->active()->create([
            'user_id' => $this->memberUser->id,
            'name' => $this->memberUser->name,
            'email' => $this->memberUser->email,
        ]);
    }

    public function test_member_can_create_snap_payment_intent_for_dues_invoice(): void
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
