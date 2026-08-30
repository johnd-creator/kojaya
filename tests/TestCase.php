<?php

namespace Tests;

use App\Models\CooperativeReceipt;
use App\Services\Cooperative\CooperativeReceiptService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // CRITICAL: Force SQLite for ALL tests to prevent production database wipe
        // This MUST be set before any database operations
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        parent::setUp();

        // Update application config to use SQLite
        app()->config->set('database.default', 'sqlite');
        app()->config->set('database.connections.sqlite.database', ':memory:');
        $this->artisan('migrate', ['--force' => true]);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function fakeCooperativeReceiptIssuance(): void
    {
        $this->mock(CooperativeReceiptService::class)
            ->shouldReceive('issue')
            ->atLeast()
            ->once()
            ->andReturnUsing(static fn (): CooperativeReceipt => new CooperativeReceipt);
    }

    /**
     * Post a signed Midtrans webhook payload for testing.
     *
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    protected function postSignedMidtransWebhook(
        string $orderId,
        string $transactionStatus = 'settlement',
        string|int|float $grossAmount = '100000.00',
        string $statusCode = '200',
        ?string $fraudStatus = 'accept',
        string $serverKey = 'test-midtrans-server-key',
        array $extra = [],
        array $headers = [],
    ): \Illuminate\Testing\TestResponse {
        config([
            'services.midtrans.server_key' => $serverKey,
            'services.midtrans.is_production' => false,
        ]);

        $formattedAmount = is_numeric($grossAmount)
            ? number_format((float) $grossAmount, 2, '.', '')
            : (string) $grossAmount;

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $formattedAmount,
            'transaction_status' => $transactionStatus,
            'signature_key' => hash('sha512', $orderId.$statusCode.$formattedAmount.$serverKey),
            ...$extra,
        ];

        if ($fraudStatus !== null) {
            $payload['fraud_status'] = $fraudStatus;
        }

        return $this->postJson('/api/payments/webhook', $payload, $headers);
    }
}
