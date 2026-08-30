<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\MobileDeviceToken;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\CooperativePeriodLockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase4Phase5OperatorHardeningTest extends TestCase
{
    use DatabaseMigrations;

    private User $operator;

    private CooperativeMember $member;

    private CooperativeDuesInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $organization = Organization::factory()->create();
        $this->operator = User::factory()->create(['organization_id' => $organization->id]);
        $this->operator->assignRole('Pengurus Koperasi');

        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $memberUser->assignRole('Anggota');

        $this->member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'MANDATORY',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $this->invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => '2026-05-10',
            'status' => 'UNPAID',
        ]);
    }

    public function test_operator_can_complete_checklist_lock_period_and_block_late_payment_posting(): void
    {
        $this->actingAs($this->operator)
            ->get('/cooperative/operator/closing/2026-05')
            ->assertOk()
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonCount(5, 'data.checklist');

        foreach (app(CooperativePeriodLockService::class)->defaultSteps() as $step) {
            $this->actingAs($this->operator)
                ->postJson('/cooperative/operator/closing/2026-05/steps', [
                    'step_key' => $step['key'],
                    'notes' => 'Done',
                ])
                ->assertOk();
        }

        $this->actingAs($this->operator)
            ->postJson('/cooperative/operator/closing/2026-05/lock', [
                'reason' => 'Closing bulanan',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'LOCKED');

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $this->invoice->id,
            'user_id' => $this->member->user_id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-12',
            'status' => 'PENDING',
        ]);

        $this->expectException(ValidationException::class);

        app(CooperativePaymentService::class)->approve($payment, $this->operator);
    }

    public function test_operator_dashboard_inbox_reconciliation_export_and_openapi_are_available(): void
    {
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $this->invoice->id,
            'user_id' => $this->member->user_id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-05-12',
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->operator)
            ->get('/cooperative/operator/approval-inbox')
            ->assertOk()
            ->assertJsonPath('data.summary.pending_payments', 1);

        $this->actingAs($this->operator)
            ->postJson("/cooperative/operator/payments/{$payment->id}/reconcile", [
                'reference' => 'BANK-20260512-001',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.reconciliation_reference', 'BANK-20260512-001');

        $this->actingAs($this->operator)
            ->get('/cooperative/operator/export?type=payments&period=2026-05')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->getJson('/api/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3');
    }

    public function test_member_can_register_push_token_create_charge_and_receive_webhook_notification(): void
    {
        $this->markTestSkipped('Menunggu aktivasi Midtrans (review pending). Hapus skip saat payment work-stream dilanjutkan.');

        Sanctum::actingAs($this->member->user, ['profile:read', 'member:write']);

        $this->postJson('/api/devices/push-token', [
            'app' => 'member',
            'device_id' => 'android-member-1',
            'platform' => 'android',
            'push_token' => 'fcm-token',
        ])
            ->assertOk()
            ->assertJsonPath('data.device_id', 'android-member-1');

        $this->assertSame(1, MobileDeviceToken::query()->count());

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_dues_invoice_id' => $this->invoice->id,
            'user_id' => $this->member->user_id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'paid_at' => '2026-05-12',
            'status' => 'PENDING',
        ]);

        $charge = $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'QRIS',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'PENDING')
            ->json('data');

        $this->postSignedMidtransWebhook(
            orderId: $charge['reference'],
            transactionStatus: 'settlement',
            grossAmount: 100000.0,
            extra: ['reconciliation_reference' => 'GW-PAID-001'],
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.reconciliation_reference', 'GW-PAID-001');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->member->user_id,
        ]);
    }
}
