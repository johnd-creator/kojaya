<?php

namespace App\Console\Commands;

use App\Models\CooperativePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateMidtransWebhookCommand extends Command
{
    /**
     * Map of supported simulation scenarios to the Midtrans webhook fields.
     * status_code + transaction_status + fraud_status drive both the
     * signature computation and the gateway status mapping in MidtransPaymentProvider.
     *
     * @var array<string, array{status_code: string, transaction_status: string, fraud_status: string}>
     */
    private const SCENARIOS = [
        'settlement' => ['status_code' => '200', 'transaction_status' => 'settlement', 'fraud_status' => 'accept'],
        'capture' => ['status_code' => '200', 'transaction_status' => 'capture', 'fraud_status' => 'accept'],
        'pending' => ['status_code' => '201', 'transaction_status' => 'pending', 'fraud_status' => 'accept'],
        'deny' => ['status_code' => '202', 'transaction_status' => 'deny', 'fraud_status' => 'deny'],
        'cancel' => ['status_code' => '410', 'transaction_status' => 'cancel', 'fraud_status' => 'accept'],
        'expire' => ['status_code' => '407', 'transaction_status' => 'expire', 'fraud_status' => 'accept'],
    ];

    protected $signature = 'midtrans:simulate-webhook
        {paymentId : ID of the cooperative_payments row to settle}
        {--status=settlement : Scenario to simulate (settlement, capture, pending, deny, cancel, expire)}
        {--payment-type=qris : Midtrans payment_type field (qris, bank_transfer, gopay, ...)}';

    protected $description = 'Simulate a Midtrans HTTP notification locally without ngrok (sandbox only). Posts a signed payload to the local /api/payments/webhook endpoint.';

    public function handle(): int
    {
        if ($this->laravel->environment('production') || config('app.env') === 'production') {
            $this->error('Refusing to simulate a payment webhook in production environment.');

            return self::FAILURE;
        }

        if (config('services.midtrans.is_production')) {
            $this->error('Refusing to simulate a Midtrans webhook while MIDTRANS_IS_PRODUCTION=true.');

            return self::FAILURE;
        }

        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            $this->error('Cannot simulate webhook: Midtrans server key is not configured.');

            return self::FAILURE;
        }

        $scenario = (string) $this->option('status');
        $fields = self::SCENARIOS[$scenario] ?? null;

        if ($fields === null) {
            $this->error("Unsupported status [{$scenario}]. Valid: ".implode(', ', array_keys(self::SCENARIOS)).'.');

            return self::FAILURE;
        }

        $payment = CooperativePayment::query()->find($this->argument('paymentId'));

        if (! $payment) {
            $this->error("CooperativePayment #{$this->argument('paymentId')} not found.");

            return self::FAILURE;
        }

        if (empty($payment->gateway_reference)) {
            $this->error("Payment #{$payment->id} has no gateway_reference yet. Create a charge via the member portal first.");

            return self::FAILURE;
        }

        $payload = $this->buildPayload($payment, $fields);

        $endpoint = rtrim((string) config('app.url'), '/').'/api/payments/webhook';

        $this->info("Simulating Midtrans webhook for payment #{$payment->id} ({$payment->gateway_reference})...");
        $this->line("  Endpoint      : {$endpoint}");
        $this->line("  Scenario      : {$scenario} ({$fields['transaction_status']})");
        $this->line("  Gross amount  : {$payload['gross_amount']}");

        $response = Http::post($endpoint, $payload);

        if ($response->successful()) {
            $this->info('  → Webhook accepted ('.$response->status().').');
            $fresh = $payment->fresh();
            $this->line("  Payment status      : {$fresh->status}");
            $this->line("  Payment gateway     : {$fresh->gateway_status}");
            $this->line('  Reconciled at       : '.($fresh->reconciled_at ?? '-'));

            return self::SUCCESS;
        }

        $this->error('  → Webhook rejected ('.$response->status().'): '.$response->body());

        return self::FAILURE;
    }

    /**
     * @param  array{status_code: string, transaction_status: string, fraud_status: string}  $fields
     * @return array<string, mixed>
     */
    private function buildPayload(CooperativePayment $payment, array $fields): array
    {
        $grossAmount = number_format((float) $payment->amount, 2, '.', '');
        $serverKey = (string) config('services.midtrans.server_key');

        $payload = [
            'order_id' => $payment->gateway_reference,
            'status_code' => $fields['status_code'],
            'gross_amount' => $grossAmount,
            'transaction_status' => $fields['transaction_status'],
            'fraud_status' => $fields['fraud_status'],
            'payment_type' => (string) $this->option('payment-type'),
            'transaction_id' => 'SIM-'.$payment->id.'-'.strtoupper(substr(uniqid(), -6)),
            'reconciliation_reference' => 'LOCAL-SIM-'.$payment->id,
        ];

        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey);

        return $payload;
    }
}
