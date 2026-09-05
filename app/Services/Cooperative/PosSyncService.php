<?php

namespace App\Services\Cooperative;

use App\Models\PosSyncRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosSyncService
{
    public const ENDPOINT_TRANSACTION_STORE = 'pos.transactions.store';

    /**
     * @var array<int, string>
     */
    public const ALLOWED_ENDPOINTS = [
        self::ENDPOINT_TRANSACTION_STORE,
    ];

    public function __construct(private PosTransactionService $transactionService) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function enqueue(Request $request, string $endpoint, string $method, array $payload, string $idempotencyKey, ?string $clientId = null): PosSyncRequest
    {
        if (! in_array($endpoint, self::ALLOWED_ENDPOINTS, true)) {
            throw ValidationException::withMessages([
                'endpoint' => "Endpoint {$endpoint} belum didukung untuk sinkronisasi offline.",
            ]);
        }

        $payloadHash = $this->hashPayload($payload);
        $userId = $request->user()?->id;
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-Id');

        $existing = PosSyncRequest::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            if (! $this->isOwnedBy($existing, $userId, $deviceId)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'Idempotency key sudah digunakan device atau pengguna lain.',
                ]);
            }

            if ($existing->payload_hash !== null && $existing->payload_hash !== $payloadHash) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'Idempotency key dipakai dengan payload berbeda.',
                ]);
            }

            return $existing;
        }

        $existingClientRequest = PosSyncRequest::query()
            ->where('device_id', $deviceId)
            ->where('client_id', $clientId ?? $idempotencyKey)
            ->first();

        if ($existingClientRequest) {
            throw ValidationException::withMessages([
                'client_id' => 'Client id sudah pernah dipakai oleh device ini.',
            ]);
        }

        return PosSyncRequest::query()->create([
            'client_id' => $clientId ?? $idempotencyKey,
            'device_id' => $deviceId,
            'user_id' => $userId,
            'pos_cashier_shift_id' => $request->input('pos_cashier_shift_id'),
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'headers' => $this->captureHeaders($request),
            'idempotency_key' => $idempotencyKey,
            'status' => PosSyncRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function process(PosSyncRequest $syncRequest): array
    {
        if ($syncRequest->status === PosSyncRequest::STATUS_DONE) {
            return [
                'idempotency_key' => $syncRequest->idempotency_key,
                'status' => $syncRequest->response_status,
                'data' => $syncRequest->response_body,
                'replay' => true,
            ];
        }

        try {
            $syncRequest->forceFill(['status' => PosSyncRequest::STATUS_PROCESSING])->save();

            $result = $this->dispatch($syncRequest);
            $response = [
                'idempotency_key' => $syncRequest->idempotency_key,
                'status' => 201,
                'data' => $result,
                'replay' => false,
            ];

            $syncRequest->forceFill([
                'status' => PosSyncRequest::STATUS_DONE,
                'response_status' => 201,
                'response_body' => $result,
                'processed_at' => now(),
            ])->save();

            return $response;
        } catch (ValidationException $e) {
            $syncRequest->forceFill([
                'status' => PosSyncRequest::STATUS_FAILED,
                'response_status' => 422,
                'response_body' => ['errors' => $e->errors()],
                'error_message' => 'validation_failed',
                'processed_at' => now(),
            ])->save();

            return [
                'idempotency_key' => $syncRequest->idempotency_key,
                'status' => 422,
                'data' => ['errors' => $e->errors()],
                'replay' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('POS sync failed', [
                'idempotency_key' => $syncRequest->idempotency_key,
                'error' => $e->getMessage(),
            ]);

            $syncRequest->forceFill([
                'status' => PosSyncRequest::STATUS_FAILED,
                'error_message' => substr($e->getMessage(), 0, 250),
            ])->save();

            return [
                'idempotency_key' => $syncRequest->idempotency_key,
                'status' => 500,
                'data' => ['error' => 'internal_error'],
                'replay' => false,
            ];
        }
    }

    /**
     * @param  array<int, string>  $idempotencyKeys
     * @return array<int, array<string, mixed>>
     */
    public function processBatch(Request $request, array $idempotencyKeys): array
    {
        $userId = $request->user()?->id;
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-Id');

        $requests = PosSyncRequest::query()
            ->with('user')
            ->whereIn('idempotency_key', $idempotencyKeys)
            ->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->orderBy('id')
            ->get();

        return $requests->map(fn (PosSyncRequest $r): array => $this->process($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatch(PosSyncRequest $syncRequest): array
    {
        return match ($syncRequest->endpoint) {
            self::ENDPOINT_TRANSACTION_STORE => $this->dispatchTransaction($syncRequest),
            default => throw new \RuntimeException("Unsupported endpoint: {$syncRequest->endpoint}"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchTransaction(PosSyncRequest $syncRequest): array
    {
        $payload = $syncRequest->payload;
        $payload['pos_cashier_shift_id'] = $syncRequest->pos_cashier_shift_id;

        $user = $syncRequest->loadMissing('user')->user;

        return $this->transactionService->create($payload, $user)->toArray();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalize($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * Recursively ksorts the array so equivalent payloads with different key
     * order produce the same hash.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function canonicalize(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonicalize($value);
            }
        }

        return $payload;
    }

    /**
     * Strict ownership: a sync request is owned by (user_id, device_id) only
     * if BOTH match. A request missing either value cannot be claimed.
     */
    private function isOwnedBy(PosSyncRequest $request, ?int $userId, ?string $deviceId): bool
    {
        if ($userId === null || $deviceId === null) {
            return false;
        }

        return (int) $request->user_id === $userId && (string) $request->device_id === $deviceId;
    }

    /**
     * @return array<string, string>
     */
    private function captureHeaders(Request $request): array
    {
        $whitelisted = ['x-device-id', 'x-shift-id', 'x-location-id'];
        $headers = [];
        foreach ($whitelisted as $key) {
            if ($request->headers->has($key)) {
                $headers[$key] = $request->header($key);
            }
        }

        return $headers;
    }

    /**
     * @return array<int, string>
     */
    public function allowedEndpoints(): array
    {
        return self::ALLOWED_ENDPOINTS;
    }
}
