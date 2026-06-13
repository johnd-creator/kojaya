<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PosProduct;
use App\Models\PosSyncRequest;
use App\Services\Cooperative\PosSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PosSyncApiController extends Controller
{
    public function __construct(private PosSyncService $service) {}

    public function catalog(Request $request): JsonResponse
    {
        $products = PosProduct::query()
            ->where('is_active', true)
            ->where('is_discontinued', false)
            ->get(['id', 'sku', 'barcode', 'name', 'cost_price', 'sale_price', 'stock', 'image_path', 'brand', 'variant', 'unit']);

        return response()->json([
            'data' => $products,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    public function enqueue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'client_id' => ['nullable', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'pos_cashier_shift_id' => ['nullable', 'integer'],
            'endpoint' => ['required', 'string', 'max:120'],
            'method' => ['required', 'string', 'max:10'],
            'payload' => ['required', 'array'],
        ]);

        try {
            $syncRequest = $this->service->enqueue(
                $request,
                $data['endpoint'],
                $data['method'],
                $data['payload'],
                $data['idempotency_key'],
                $data['client_id'] ?? null,
            );
        } catch (ValidationException $e) {
            $status = isset($e->errors()['endpoint']) ? 422 : 409;
            throw ValidationException::withMessages($e->errors())->status($status);
        }

        return response()->json([
            'idempotency_key' => $syncRequest->idempotency_key,
            'status' => $syncRequest->status,
        ], 202);
    }

    public function process(Request $request, string $idempotencyKey): JsonResponse
    {
        $syncRequest = $this->locateRequest($request, $idempotencyKey);

        $result = $this->service->process($syncRequest);

        return response()->json($result, $result['status'] ?? 200);
    }

    public function processBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idempotency_keys' => ['required', 'array', 'min:1', 'max:100'],
            'idempotency_keys.*' => ['string', 'max:120'],
        ]);

        $results = $this->service->processBatch($request, $data['idempotency_keys']);

        return response()->json([
            'data' => $results,
        ]);
    }

    public function status(Request $request, string $idempotencyKey): JsonResponse
    {
        $syncRequest = $this->locateRequest($request, $idempotencyKey, allowNotFound: true);

        if (! $syncRequest) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'idempotency_key' => $syncRequest->idempotency_key,
            'status' => $syncRequest->status,
            'response_status' => $syncRequest->response_status,
            'response_body' => $syncRequest->response_body,
            'error_message' => $syncRequest->error_message,
            'processed_at' => $syncRequest->processed_at?->toIso8601String(),
        ]);
    }

    private function locateRequest(Request $request, string $idempotencyKey, bool $allowNotFound = false): ?PosSyncRequest
    {
        $userId = $request->user()?->id;
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-Id');

        $query = PosSyncRequest::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('user_id', $userId)
            ->where('device_id', $deviceId);

        $syncRequest = $query->first();

        if (! $syncRequest && ! $allowNotFound) {
            throw new NotFoundHttpException('Sync request tidak ditemukan atau bukan milik Anda.');
        }

        return $syncRequest;
    }
}
