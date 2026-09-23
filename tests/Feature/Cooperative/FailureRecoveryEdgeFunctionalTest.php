<?php

declare(strict_types=1);

namespace Tests\Feature\Cooperative;

use App\Http\Middleware\EnsureIdempotentWrite;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\TestCase;

class FailureRecoveryEdgeFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_edge003_late_paid_webhook_after_manual_approval_and_replay_is_exactly_once(): void
    {
        $serverKey = 'test-midtrans-server-key';
        config([
            'services.midtrans.server_key' => $serverKey,
            'services.midtrans.is_production' => false,
            'services.payment_gateway.allow_simulation' => false,
        ]);

        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);
        $approver = User::factory()->create(['organization_id' => $organization->id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'EDGE-11',
            'name' => 'FUNC-11 dues',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 0,
            'due_date' => '2026-09-30',
            'status' => 'UNPAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $type->id,
            'user_id' => $memberUser->id,
            'amount' => 50000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'FUNC11-LATE-MANUAL-001',
            'gateway_status' => 'PENDING',
            'gateway_payload' => ['reference' => 'FUNC11-LATE-MANUAL-001', 'status' => 'PENDING'],
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        app(CooperativePaymentService::class)->approve($payment, $approver);

        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(50000.0, (float) $invoice->fresh()->paid_amount);
        $this->assertSame(1, $payment->approvalLogs()->where('to_status', 'APPROVED')->count());

        $this->postSignedMidtransWebhook(
            orderId: 'FUNC11-LATE-MANUAL-001',
            transactionStatus: 'settlement',
            grossAmount: 50000,
            serverKey: $serverKey,
        )->assertOk();

        $this->assertSame('APPROVED', $payment->fresh()->status);
        $this->assertSame('PAID', $payment->fresh()->gateway_status);
        $this->assertNotNull($payment->fresh()->reconciled_at);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(50000.0, (float) $invoice->fresh()->paid_amount);
        $this->assertSame(1, $payment->approvalLogs()->where('to_status', 'APPROVED')->count());
        $this->assertSame(1, $payment->approvalLogs()->where('to_status', 'RECONCILED')->count());

        $this->postSignedMidtransWebhook(
            orderId: 'FUNC11-LATE-MANUAL-001',
            transactionStatus: 'settlement',
            grossAmount: 50000,
            serverKey: $serverKey,
        )->assertOk();

        $this->assertSame('APPROVED', $payment->fresh()->status);
        $this->assertSame('PAID', $payment->fresh()->gateway_status);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(1, CooperativeReceipt::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame(50000.0, (float) $invoice->fresh()->paid_amount);
        $this->assertSame(1, $payment->approvalLogs()->where('to_status', 'APPROVED')->count());
        $this->assertSame(1, $payment->approvalLogs()->where('to_status', 'RECONCILED')->count());
    }

    public function test_edge004_idempotency_replay_conflict_expiry_and_server_errors(): void
    {
        Cache::flush();
        $middleware = app(EnsureIdempotentWrite::class);
        $calls = 0;
        $next = function (Request $request) use (&$calls): JsonResponse {
            $calls++;

            return response()->json(['data' => ['mutation' => $calls]], 201);
        };

        $firstRequest = Request::create('/api/edge-recovery', 'POST', ['amount' => 100]);
        $firstRequest->headers->set('Idempotency-Key', 'edge-key-0001');
        $first = $middleware->handle($firstRequest, $next);
        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(1, $calls);

        $sameKeyReplay = Request::create('/api/edge-recovery', 'POST', ['amount' => 100]);
        $sameKeyReplay->headers->set('Idempotency-Key', 'edge-key-0001');
        $replay = $middleware->handle($sameKeyReplay, $next);
        $this->assertSame(201, $replay->getStatusCode());
        $this->assertSame($first->getContent(), $replay->getContent());
        $this->assertSame('true', $replay->headers->get('X-Idempotency-Replayed'));
        $this->assertSame(1, $calls, 'Replay does not execute the mutation again.');

        $changedPayload = Request::create('/api/edge-recovery', 'POST', ['amount' => 200]);
        $changedPayload->headers->set('Idempotency-Key', 'edge-key-0001');
        $this->assertSame(409, $middleware->handle($changedPayload, $next)->getStatusCode());
        $this->assertSame(1, $calls);

        $malformed = Request::create('/api/edge-recovery', 'POST', ['amount' => 300]);
        $malformed->headers->set('Idempotency-Key', 'bad key');
        $this->assertSame(422, $middleware->handle($malformed, $next)->getStatusCode());
        $this->assertSame(1, $calls);

        $fileOne = UploadedFile::fake()->createWithContent('proof.pdf', 'first content');
        $uploadA = Request::create('/api/upload-edge', 'POST', ['amount' => 10], [], ['proof' => $fileOne]);
        $uploadA->headers->set('Idempotency-Key', 'edge-file-key-0001');
        $this->assertSame(201, $middleware->handle($uploadA, $next)->getStatusCode());
        $fileReplay = UploadedFile::fake()->createWithContent('proof.pdf', 'first content');
        $uploadReplay = Request::create('/api/upload-edge', 'POST', ['amount' => 10], [], ['proof' => $fileReplay]);
        $uploadReplay->headers->set('Idempotency-Key', 'edge-file-key-0001');
        $this->assertSame('true', $middleware->handle($uploadReplay, $next)->headers->get('X-Idempotency-Replayed'));
        $differentFile = UploadedFile::fake()->createWithContent('proof.pdf', 'different content');
        $uploadConflict = Request::create('/api/upload-edge', 'POST', ['amount' => 10], [], ['proof' => $differentFile]);
        $uploadConflict->headers->set('Idempotency-Key', 'edge-file-key-0001');
        $this->assertSame(409, $middleware->handle($uploadConflict, $next)->getStatusCode());

        $errorCalls = 0;
        $serverError = static function (Request $request) use (&$errorCalls): JsonResponse {
            $errorCalls++;

            return response()->json(['message' => 'temporary'], 500);
        };
        $serverErrorRequest = Request::create('/api/error-edge', 'POST', []);
        $serverErrorRequest->headers->set('Idempotency-Key', 'edge-500-key-0001');
        $middleware->handle($serverErrorRequest, $serverError);
        $retry = Request::create('/api/error-edge', 'POST', []);
        $retry->headers->set('Idempotency-Key', 'edge-500-key-0001');
        $middleware->handle($retry, $serverError);
        $this->assertSame(2, $errorCalls, '5xx responses must not be cached.');

        $expired = Request::create('/api/expired-edge', 'POST', ['amount' => 1]);
        $expired->headers->set('Idempotency-Key', 'edge-expired-key-0001');
        $middleware->handle($expired, $next);
        $this->travel(2)->days();
        $expiredRetry = Request::create('/api/expired-edge', 'POST', ['amount' => 1]);
        $expiredRetry->headers->set('Idempotency-Key', 'edge-expired-key-0001');
        $expiredResponse = $middleware->handle($expiredRetry, $next);
        $this->assertNull($expiredResponse->headers->get('X-Idempotency-Replayed'));
    }

    public function test_edge005_unconfigured_gateway_keeps_member_invoice_unpaid_and_reuses_pending_payment(): void
    {
        config([
            'services.midtrans.server_key' => '',
            'services.midtrans.is_production' => false,
            'services.payment_gateway.allow_simulation' => false,
        ]);

        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'EDGE-11-UNAVAILABLE',
            'name' => 'FUNC-11 unavailable gateway dues',
            'category' => 'WAJIB',
            'default_amount' => 25000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-09',
            'amount' => 25000,
            'paid_amount' => 0,
            'due_date' => '2026-09-30',
            'status' => 'UNPAID',
        ]);

        $request = fn () => $this->actingAs($user)->postJson(route('member.payments.intent'), [
            'cooperative_dues_invoice_id' => $invoice->id,
            'channel' => 'QRIS',
        ]);

        $request()->assertUnprocessable()
            ->assertJsonPath('error_code', 'PAYMENT_GATEWAY_ERROR');
        $request()->assertUnprocessable()
            ->assertJsonPath('error_code', 'PAYMENT_GATEWAY_ERROR');

        $this->assertSame(1, CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoice->id)->count());
        $this->assertSame('PENDING', CooperativePayment::query()->where('cooperative_dues_invoice_id', $invoice->id)->value('status'));
        $this->assertSame('UNPAID', $invoice->fresh()->status);
        $this->assertSame(0.0, (float) $invoice->fresh()->paid_amount);
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
        $this->assertSame(0, CooperativeReceipt::query()->count());
    }
}
