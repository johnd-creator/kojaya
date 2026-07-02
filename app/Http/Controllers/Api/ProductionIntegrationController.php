<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentGatewayWebhookVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePaymentChargeRequest;
use App\Http\Requests\Api\PaymentGatewayWebhookRequest;
use App\Http\Requests\Api\RegisterDeviceTokenRequest;
use App\Models\CooperativePayment;
use App\Models\MobileDeviceToken;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Integrations\MemberPaymentSettlementService;
use App\Services\Integrations\PaymentGatewayService;
use App\Services\Integrations\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProductionIntegrationController extends Controller
{
    public function registerDevice(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $token = MobileDeviceToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'app' => $validated['app'],
                'device_id' => $validated['device_id'],
            ],
            [
                'platform' => $validated['platform'] ?? null,
                'push_token' => $validated['push_token'],
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json(['data' => $token]);
    }

    public function createPaymentCharge(CreatePaymentChargeRequest $request, PaymentGatewayService $gateway): JsonResponse
    {
        $validated = $request->validated();
        $payment = CooperativePayment::query()
            ->with('invoice')
            ->findOrFail($validated['cooperative_payment_id']);

        if ($request->user()->tokenCan('member:write')) {
            abort_unless($payment->member?->user_id === $request->user()->id, 403);
        }

        if (! $payment->invoice) {
            throw ValidationException::withMessages([
                'cooperative_payment_id' => ['Only member dues/savings invoice payments can be charged through this endpoint.'],
            ]);
        }

        return response()->json([
            'data' => $gateway->createCharge($payment, $validated['channel']),
        ], 201);
    }

    public function paymentWebhook(
        PaymentGatewayWebhookRequest $request,
        PaymentGatewayService $gateway,
        MemberPaymentSettlementService $memberPaymentSettlementService,
        CooperativePaymentService $paymentService,
        PushNotificationService $pushNotificationService
    ): JsonResponse {
        try {
            $payment = $gateway->applyWebhook($request->validated(), $request->headers->all());
        } catch (PaymentGatewayWebhookVerificationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $intent = null;

        if ($payment && $payment->gateway_status === 'PAID' && ! $payment->reconciled_at) {
            $payment = $paymentService->reconcile(
                $payment,
                null,
                (string) ($request->validated('reconciliation_reference') ?? $payment->gateway_reference),
            );

            if ($payment->member?->user) {
                $pushNotificationService->send(
                    $payment->member->user,
                    'Pembayaran diterima',
                    'Pembayaran koperasi Anda sudah diverifikasi.',
                    ['payment_id' => $payment->id],
                );
            }
        }

        if (! $payment) {
            try {
                $intent = $gateway->applyWebhookToMemberIntent($request->validated(), $request->headers->all());
            } catch (PaymentGatewayWebhookVerificationException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($intent && $intent->gateway_status === 'PAID' && ! $intent->settled_at) {
                $intent = $memberPaymentSettlementService->settle($intent);
            }
        }

        if (! $payment && ! $intent) {
            return response()->json([
                'message' => 'Webhook ignored.',
            ], Response::HTTP_ACCEPTED);
        }

        return response()->json(['data' => $payment ?: $intent]);
    }

    public function monitoring(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'checked_at' => now()->toIso8601String(),
                'pending_payments' => CooperativePayment::query()->where('status', 'PENDING')->count(),
                'failed_gateway_payments' => CooperativePayment::query()->where('gateway_status', 'FAILED')->count(),
            ],
        ]);
    }
}
