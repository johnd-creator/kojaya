<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimulateMidtransWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    private CooperativePayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.server_key' => 'SB-Mid-server-testkey',
            'app.url' => 'http://localhost',
        ]);

        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-SIM',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        $this->payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-1-SIMULATE',
            'gateway_status' => 'PENDING',
        ]);
    }

    public function test_refuses_to_run_in_production_mode(): void
    {
        config(['services.midtrans.is_production' => true]);

        $this->artisan('midtrans:simulate-webhook', ['paymentId' => $this->payment->id])
            ->assertFailed()
            ->expectsOutputToContain('MIDTRANS_IS_PRODUCTION');
    }

    public function test_fails_when_payment_not_found(): void
    {
        $this->artisan('midtrans:simulate-webhook', ['paymentId' => 999999])
            ->assertFailed()
            ->expectsOutputToContain('not found');
    }

    public function test_fails_when_payment_has_no_gateway_reference(): void
    {
        $this->payment->forceFill(['gateway_reference' => null])->save();

        $this->artisan('midtrans:simulate-webhook', ['paymentId' => $this->payment->id])
            ->assertFailed()
            ->expectsOutputToContain('no gateway_reference');
    }

    public function test_rejects_unsupported_status_option(): void
    {
        $this->artisan('midtrans:simulate-webhook', [
            'paymentId' => $this->payment->id,
            '--status' => 'bogus',
        ])
            ->assertFailed()
            ->expectsOutputToContain('Unsupported status');
    }

    public function test_posts_signed_settlement_payload_to_local_webhook(): void
    {
        Http::fake([
            'localhost/api/payments/webhook' => Http::response(['data' => ['status' => 'APPROVED']], 200),
        ]);

        $this->artisan('midtrans:simulate-webhook', ['paymentId' => $this->payment->id])
            ->assertSuccessful()
            ->expectsOutputToContain('Webhook accepted');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $body = $request->data();

            $expectedSignature = hash('sha512',
                $body['order_id'].$body['status_code'].$body['gross_amount'].'SB-Mid-server-testkey'
            );

            return $request->url() === 'http://localhost/api/payments/webhook'
                && $body['order_id'] === 'KOJ-1-SIMULATE'
                && $body['transaction_status'] === 'settlement'
                && $body['gross_amount'] === '100000.00'
                && $body['signature_key'] === $expectedSignature;
        });
    }

    public function test_reports_failure_when_webhook_endpoint_rejects(): void
    {
        Http::fake([
            'localhost/api/payments/webhook' => Http::response(['message' => 'bad signature'], 400),
        ]);

        $this->artisan('midtrans:simulate-webhook', ['paymentId' => $this->payment->id])
            ->assertFailed()
            ->expectsOutputToContain('Webhook rejected');
    }
}
