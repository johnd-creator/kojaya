<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Integrations\PaymentGatewayProvider;
use App\Models\CooperativePayment;
use App\Models\MemberPaymentIntent;
use App\Services\Integrations\WebhookEvent;

/**
 * Fake provider for concurrency tests that simulates real provider behavior:
 *
 * - Atomic create-call counter via flock()
 * - Durable charge store in a shared JSON file
 * - Blocks response until release signal file appears
 * - Supports reconciliation by reading from the shared store
 *
 * All synchronization is file-based so it works across separate PHP processes.
 */
class ConcurrencyPaymentGatewayProvider implements PaymentGatewayProvider
{
    public string $counterFile = '';

    public string $storeFile = '';

    public string $createdSignal = '';

    public string $releaseSignal = '';

    public string $reconcileCalledSignal = '';

    public int $releaseTimeoutSeconds = 30;

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Simulate a real provider charge creation.
     *
     * 1. Increment the atomic counter (shared across processes)
     * 2. Persist charge to the shared store
     * 3. Signal that the charge was created
     * 4. Block until the release signal appears (simulating response delay)
     */
    public function createIntentCharge(MemberPaymentIntent $intent): array
    {
        $orderId = $this->providerOrderId($intent);
        $idempotencyKey = $this->idempotencyKey($intent);

        $this->incrementCounter();

        $charge = [
            'provider' => 'fake',
            'reference' => $orderId,
            'status' => 'PENDING',
            'channel' => $intent->channel,
            'amount' => (float) $intent->amount,
            'checkout_url' => null,
            'qr_string' => 'fake-qr-string-'.$orderId,
            'idempotency_key' => $idempotencyKey,
        ];

        $this->persistCharge($orderId, $charge);

        if ($this->createdSignal !== '') {
            @touch($this->createdSignal);
        }

        // Block until released (simulating slow provider response)
        $this->waitUntilReleased();

        return $charge;
    }

    /**
     * Reconcile by reading the shared store.
     */
    public function reconcileIntentCharge(string $providerOrderId): ?array
    {
        if ($this->reconcileCalledSignal !== '') {
            @touch($this->reconcileCalledSignal);
        }

        $store = $this->readStore();

        return $store[$providerOrderId] ?? null;
    }

    public function createCharge(CooperativePayment $payment, string $channel): array
    {
        return [];
    }

    public function verifyWebhook(array $payload, array $headers): bool
    {
        return false;
    }

    public function parseWebhook(array $payload): WebhookEvent
    {
        throw new \RuntimeException('Not implemented');
    }

    public function acknowledgeResponse(): mixed
    {
        return null;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function providerOrderId(MemberPaymentIntent $intent): string
    {
        return sprintf('KOJ-MPI-%d-%d', $intent->id, $intent->charge_attempt ?: 1);
    }

    private function idempotencyKey(MemberPaymentIntent $intent): string
    {
        return sprintf('member-intent:%s:%s', $intent->id, $intent->charge_attempt ?: 1);
    }

    /**
     * Atomically increment the shared counter using flock().
     */
    private function incrementCounter(): void
    {
        if ($this->counterFile === '') {
            return;
        }

        $fp = fopen($this->counterFile, 'c+');
        if (! $fp) {
            return;
        }

        flock($fp, LOCK_EX);
        $count = (int) trim((string) fread($fp, 32));
        $count++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) $count);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Read the current counter value.
     */
    public function getCreateCallCount(): int
    {
        if ($this->counterFile === '' || ! file_exists($this->counterFile)) {
            return 0;
        }

        return (int) trim((string) file_get_contents($this->counterFile));
    }

    /**
     * Persist a charge to the shared store (atomic via flock).
     *
     * @param  array<string, mixed>  $charge
     */
    private function persistCharge(string $orderId, array $charge): void
    {
        if ($this->storeFile === '') {
            return;
        }

        $fp = fopen($this->storeFile, 'c+');
        if (! $fp) {
            return;
        }

        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $store = json_decode($contents ?: '{}', true);
        if (! is_array($store)) {
            $store = [];
        }
        $store[$orderId] = $charge;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($store, JSON_THROW_ON_ERROR));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Read the shared store.
     *
     * @return array<string, array<string, mixed>>
     */
    public function readStore(): array
    {
        if ($this->storeFile === '' || ! file_exists($this->storeFile)) {
            return [];
        }

        $contents = file_get_contents($this->storeFile);
        $store = json_decode($contents ?: '{}', true);

        return is_array($store) ? $store : [];
    }

    /**
     * Block until the release signal file appears or timeout.
     */
    private function waitUntilReleased(): void
    {
        if ($this->releaseSignal === '') {
            return;
        }

        $deadline = time() + $this->releaseTimeoutSeconds;

        while (! file_exists($this->releaseSignal)) {
            if (time() > $deadline) {
                return;
            }
            usleep(50000); // 50ms poll interval
        }
    }

    /**
     * Wait for the created signal file to appear (used by test orchestrator).
     */
    public function waitForCreatedSignal(int $timeoutSeconds = 10): bool
    {
        if ($this->createdSignal === '') {
            return true;
        }

        $deadline = time() + $timeoutSeconds;

        while (! file_exists($this->createdSignal)) {
            if (time() > $deadline) {
                return false;
            }
            usleep(50000);
        }

        return true;
    }
}
