<?php

namespace App\Services\Integrations;

use App\Models\CooperativePayment;
use App\Models\MemberPaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransPaymentProvider implements PaymentGatewayProvider
{
    private const BASE_URL_SANDBOX = 'https://api.sandbox.midtrans.com';

    private const BASE_URL_PRODUCTION = 'https://api.midtrans.com';

    private const ALLOWED_TRANSITIONS = [
        'PENDING' => ['PAID', 'EXPIRED', 'FAILED', 'CANCELLED'],
        'PAID' => [],
        'EXPIRED' => [],
        'FAILED' => ['PAID'],
        'CANCELLED' => [],
    ];

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.midtrans.is_production', false)
            ? self::BASE_URL_PRODUCTION
            : self::BASE_URL_SANDBOX;
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds: int, gateway_payload: array<string, mixed>}
     */
    public function createCharge(CooperativePayment $payment, string $channel): array
    {
        $orderId = $this->generateOrderId($payment);
        $amount = (int) round($payment->amount);

        $transactionDetails = [
            'order_id' => $orderId,
            'gross_amount' => $amount,
        ];

        $customerDetails = $this->buildCustomerDetails($payment);
        $items = $this->buildItems($payment, $amount);

        $payload = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $items,
        ];

        $endpoint = $this->endpointForChannel($channel);
        $payload = $this->applyChannelPayload($payload, $channel);

        $response = $this->sendChargeRequest(
            idempotencyKey: 'charge-'.$payment->id.'-'.$payment->gateway_status,
            endpoint: $endpoint,
            payload: $payload,
        );

        $body = $response->json() ?: [];

        $this->ensureChargeSuccessful($response, $body, $orderId, $payment->id, 'charge');

        $qrString = null;
        $checkoutUrl = null;
        $qrActionUrl = null;

        if ($channel === 'QRIS') {
            $qrString = $body['qr_string'] ?? null;
            $qrActionUrl = $this->qrActionUrl($body);
        } elseif ($channel === 'E_WALLET') {
            $actions = $body['actions'] ?? [];
            $checkoutUrl = collect($actions)
                ->firstWhere('name', 'deeplink-redirect')['url'] ?? null;
            $checkoutUrl ??= collect($actions)
                ->firstWhere('name', 'activate-deeplink')['url'] ?? null;
        }

        if (! $checkoutUrl && ! $qrString) {
            $checkoutUrl = $body['redirect_url'] ?? null;
        }

        return [
            'provider' => 'midtrans',
            'reference' => $orderId,
            'status' => 'PENDING',
            'channel' => $channel,
            'amount' => (float) $payment->amount,
            'checkout_url' => $checkoutUrl,
            'qr_string' => $qrString,
            'qr_image_url' => route('api.v1.member.payments.qris-image', $payment, false),
            'expires_at' => $body['expiry_time'] ?? null,
            'instructions' => $this->buildInstructions($body, $channel, $qrActionUrl),
            'poll_after_seconds' => 5,
            'gateway_payload' => $body,
        ];
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, expires_at?: string|null, instructions?: array<string, mixed>}
     */
    public function createIntentCharge(MemberPaymentIntent $intent): array
    {
        $orderId = $this->generateIntentOrderId($intent);
        $amount = (int) round((float) $intent->amount);
        $channel = $intent->channel;
        $metadata = $intent->metadata ?? [];

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $intent->member?->name ?? 'Member',
                'last_name' => '',
                'email' => $intent->member?->user?->email ?? 'member@kojaya.test',
                'phone' => $intent->member?->phone ?? '08123456789',
            ],
            'item_details' => [
                [
                    'id' => (string) ($intent->payable_id ?? $intent->id),
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => Str::limit((string) ($metadata['description'] ?? 'Pembayaran Anggota Kojaya'), 45, ''),
                ],
            ],
        ];

        $payload = $this->applyChannelPayload($payload, $channel);

        $idempotencyKey = sprintf(
            'member-intent:%s:%s',
            $intent->id,
            $intent->charge_attempt ?: 1
        );

        $response = $this->sendChargeRequest(
            idempotencyKey: $idempotencyKey,
            endpoint: $this->endpointForChannel($channel),
            payload: $payload,
        );

        $body = $response->json() ?: [];

        $this->ensureChargeSuccessful($response, $body, $orderId, $intent->id, 'member payment intent charge');

        $qrString = null;
        $checkoutUrl = null;

        if ($channel === 'QRIS') {
            $qrString = $body['qr_string'] ?? null;
        } elseif ($channel === 'E_WALLET') {
            $actions = $body['actions'] ?? [];
            $checkoutUrl = collect($actions)
                ->firstWhere('name', 'deeplink-redirect')['url'] ?? null;
            $checkoutUrl ??= collect($actions)
                ->firstWhere('name', 'activate-deeplink')['url'] ?? null;
        }

        if (! $checkoutUrl && ! $qrString) {
            $checkoutUrl = $body['redirect_url'] ?? null;
        }

        return [
            'provider' => 'midtrans',
            'reference' => $orderId,
            'status' => 'PENDING',
            'channel' => $channel,
            'amount' => (float) $intent->amount,
            'checkout_url' => $checkoutUrl,
            'qr_string' => $qrString,
            'expires_at' => $body['expiry_time'] ?? null,
            'instructions' => $this->buildInstructions($body, $channel),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function verifyWebhook(array $payload, array $headers): bool
    {
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($signatureKey === '') {
            $signatureKey = $this->header($headers, 'x-midtrans-signature')
                ?: $this->header($headers, 'signature-key');
        }

        if ($signatureKey === '') {
            Log::warning('Midtrans webhook missing signature header');

            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');

        $computed = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey());

        if (! hash_equals($computed, $signatureKey)) {
            Log::warning('Midtrans webhook signature mismatch', [
                'order_id' => $orderId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): WebhookEvent
    {
        $transactionStatus = strtoupper((string) ($payload['transaction_status'] ?? 'UNKNOWN'));
        $fraudStatus = strtoupper((string) ($payload['fraud_status'] ?? 'accept'));

        if ($fraudStatus === 'CHALLENGE') {
            $mappedStatus = 'PENDING';
        } elseif (in_array($transactionStatus, ['CAPTURE', 'SETTLEMENT'], true)) {
            $mappedStatus = 'PAID';
        } elseif ($transactionStatus === 'EXPIRE') {
            $mappedStatus = 'EXPIRED';
        } elseif ($transactionStatus === 'CANCEL') {
            $mappedStatus = 'CANCELLED';
        } elseif (in_array($transactionStatus, ['DENY', 'FAILURE'], true)) {
            $mappedStatus = 'FAILED';
        } elseif ($transactionStatus === 'PENDING') {
            $mappedStatus = 'PENDING';
        } elseif ($transactionStatus === 'REFUND') {
            $mappedStatus = 'REFUNDED';
        } else {
            $mappedStatus = 'UNKNOWN';
        }

        $paymentType = (string) ($payload['payment_type'] ?? '');
        $channel = match (true) {
            $paymentType === 'gopay' => 'E_WALLET',
            $paymentType === 'bank_transfer' => 'VA',
            str_contains($paymentType, 'qris') => 'QRIS',
            default => strtoupper($paymentType),
        };

        return new WebhookEvent(
            gatewayReference: (string) ($payload['order_id'] ?? ''),
            status: $mappedStatus,
            paymentMethod: $paymentType,
            channel: $channel,
            amount: (float) ($payload['gross_amount'] ?? 0),
            reconciliationReference: null,
            fraudStatus: $fraudStatus,
            rawPayload: $payload,
        );
    }

    public function acknowledgeResponse(): mixed
    {
        return response()->json(['status' => 'OK'], 200);
    }

    /**
     * @param  array<string, string|array<int, string>>  $headers
     */
    private function header(array $headers, string $name): string
    {
        $value = $headers[strtolower($name)] ?? $headers[$name] ?? null;

        if (is_array($value)) {
            return (string) ($value[0] ?? '');
        }

        return (string) ($value ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomerDetails(CooperativePayment $payment): array
    {
        $member = $payment->member;

        return [
            'first_name' => $member?->name ?? 'Member',
            'last_name' => '',
            'email' => $member?->user?->email ?? 'member@kojaya.test',
            'phone' => $member?->phone ?? '08123456789',
        ];
    }

    /**
     * @return array<int, array{id: string, price: int, quantity: int, name: string}>
     */
    private function buildItems(CooperativePayment $payment, int $amount): array
    {
        $label = $payment->invoice?->contributionType?->name ?? 'Iuran Koperasi';

        return [
            [
                'id' => (string) ($payment->invoice?->id ?? $payment->id),
                'price' => $amount,
                'quantity' => 1,
                'name' => $label.' - '.($payment->invoice?->period ?? now()->format('Y-m')),
            ],
        ];
    }

    private function generateOrderId(CooperativePayment $payment): string
    {
        return sprintf('KOJ-%d-%s', $payment->id, Str::upper(Str::random(8)));
    }

    private function generateIntentOrderId(MemberPaymentIntent $intent): string
    {
        return sprintf('KOJ-MPI-%d-%s', $intent->id, Str::upper(Str::random(8)));
    }

    private function endpointForChannel(string $channel): string
    {
        // All Core API direct charges (QRIS, bank_transfer, gopay, etc.) use the
        // same /v2/charge endpoint; the payment method is declared in the body.
        return '/v2/charge';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyChannelPayload(array $payload, string $channel): array
    {
        if (in_array($channel, ['VA', 'TRANSFER'], true)) {
            $payload['payment_type'] = 'bank_transfer';
            $payload['bank_transfer'] = ['bank' => $this->bankTransferCode()];
        } elseif ($channel === 'E_WALLET') {
            $payload['payment_type'] = 'gopay';
        } elseif ($channel === 'QRIS') {
            $payload['payment_type'] = 'qris';
            $payload['qris'] = [
                'acquirer' => config('services.midtrans.qris_acquirer', 'gopay'),
            ];
        }

        return $payload;
    }

    /**
     * Midtrans sometimes returns HTTP 200 with an application-level error in
     * the body (e.g. status_code "402" "Payment channel is not activated.").
     * Surface those as failures instead of silently returning an empty charge.
     *
     * @param  array<string, mixed>  $body
     */
    private function ensureChargeSuccessful(\Illuminate\Http\Client\Response $response, array $body, string $orderId, int $entityId, string $context): void
    {
        $httpStatus = $response->status();
        $appStatusCode = (int) ($body['status_code'] ?? $httpStatus);

        if (! $response->successful() || $appStatusCode >= 400) {
            Log::error('Midtrans '.$context.' failed', [
                'entity_id' => $entityId,
                'order_id' => $orderId,
                'http_status' => $httpStatus,
                'midtrans_status_code' => $appStatusCode,
                'body' => $body,
            ]);

            throw new \RuntimeException('Midtrans '.$context.' failed: '.($body['status_message'] ?? 'HTTP '.$httpStatus));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendChargeRequest(string $idempotencyKey, string $endpoint, array $payload): \Illuminate\Http\Client\Response
    {
        $request = fn () => Http::withBasicAuth($this->serverKey(), '')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($this->baseUrl.$endpoint, $payload);

        $response = $request();

        if ($response->status() === 404 && ($response->json() ?: []) === []) {
            usleep(500_000);

            return $request();
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function buildInstructions(array $body, string $channel, ?string $qrActionUrl = null): array
    {
        if ($channel === 'QRIS') {
            return array_filter([
                'title' => 'Scan QRIS untuk membayar',
                'description' => 'Status pembayaran diperbarui setelah Midtrans mengonfirmasi transaksi.',
                'qr_action_url' => $qrActionUrl,
            ], fn (mixed $value): bool => $value !== null && $value !== '');
        }

        if (! in_array($channel, ['VA', 'TRANSFER'], true)) {
            return [];
        }

        $instructions = [];
        $vaNumbers = $body['va_numbers'] ?? [];

        if (is_array($vaNumbers) && $vaNumbers !== []) {
            $firstVa = $vaNumbers[0] ?? [];

            if (is_array($firstVa)) {
                $instructions['bank'] = strtoupper((string) ($firstVa['bank'] ?? ''));
                $instructions['va_number'] = (string) ($firstVa['va_number'] ?? '');
            }
        }

        foreach (['permata_va_number', 'bill_key', 'biller_code'] as $key) {
            if (! empty($body[$key])) {
                $instructions[$key] = (string) $body[$key];
            }
        }

        if (empty($instructions['va_number']) && ! empty($instructions['permata_va_number'])) {
            $instructions['bank'] = $instructions['bank'] ?? 'PERMATA';
            $instructions['va_number'] = $instructions['permata_va_number'];
        }

        return array_filter($instructions, fn (mixed $value): bool => $value !== '');
    }

    private function serverKey(): string
    {
        return (string) config('services.midtrans.server_key', '');
    }

    private function bankTransferCode(): string
    {
        return strtolower((string) config('services.midtrans.va_bank', 'permata'));
    }

    public function isConfigured(): bool
    {
        return $this->serverKey() !== '';
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null, expires_at?: string|null, instructions?: array<string, mixed>}
     */
    private function createChargeInternal(CooperativePayment $payment, string $channel): array
    {
        $orderId = $this->generateOrderId($payment);

        return [
            'provider' => 'midtrans',
            'reference' => $orderId,
            'status' => 'PENDING',
            'channel' => $channel,
            'amount' => (float) $payment->amount,
            'checkout_url' => url("/api/payments/{$orderId}/checkout"),
            'qr_string' => null,
        ];
    }

    /**
     * Validate status transition is allowed.
     */
    public static function isTransitionAllowed(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function qrActionUrl(array $body): ?string
    {
        $actions = $body['actions'] ?? [];

        if (! is_array($actions)) {
            return null;
        }

        foreach (['generate-qr-code-v2', 'generate-qr-code'] as $name) {
            $action = collect($actions)->firstWhere('name', $name);

            if (is_array($action) && ! empty($action['url'])) {
                return (string) $action['url'];
            }
        }

        return null;
    }
}
