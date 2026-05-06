<?php

namespace App\Services\Integrations;

use App\Models\CooperativePayment;
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
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null}
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

        $endpoint = match ($channel) {
            'QRIS' => '/v2/qris/charge',
            'VA' => '/v2/charge',
            'E_WALLET' => '/v2/charge',
            default => '/v2/charge',
        };

        if ($channel === 'VA') {
            $payload['payment_type'] = 'bank_transfer';
            $payload['bank_transfer'] = ['bank' => 'bca'];
        } elseif ($channel === 'E_WALLET') {
            $payload['payment_type'] = 'gopay';
        }

        $response = Http::withBasicAuth(
            $this->serverKey(),
            ''
        )
            ->withHeader('Idempotency-Key', 'charge-'.$payment->id.'-'.$payment->gateway_status)
            ->post($this->baseUrl.$endpoint, $payload);

        $body = $response->json() ?: [];

        if (! $response->successful()) {
            Log::error('Midtrans charge failed', [
                'payment_id' => $payment->id,
                'order_id' => $orderId,
                'status_code' => $response->status(),
                'body' => $body,
            ]);

            throw new \RuntimeException('Midtrans charge failed: '.($body['status_message'] ?? $response->status()));
        }

        $qrString = null;
        $checkoutUrl = null;

        if ($channel === 'QRIS') {
            $qrString = $body['qr_string'] ?? null;
        } elseif ($channel === 'E_WALLET') {
            $actions = $body['actions'] ?? [];
            $checkoutUrl = collect($actions)->firstWhere('name', 'deeplink-redirect')['url'] ?? null;
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
        } elseif (in_array($transactionStatus, ['CANCEL', 'DENY', 'EXPIRE'], true)) {
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
            $paymentType === 'gopay' && $mappedStatus === 'PAID' => 'E_WALLET',
            $paymentType === 'bank_transfer' && $mappedStatus === 'PAID' => 'VA',
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

    private function serverKey(): string
    {
        return (string) config('services.midtrans.server_key', '');
    }

    public function isConfigured(): bool
    {
        return $this->serverKey() !== '';
    }

    /**
     * @return array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_string: string|null}
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
}
