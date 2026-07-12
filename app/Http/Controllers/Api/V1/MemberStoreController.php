<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMemberStoreOrderRequest;
use App\Models\MemberPaymentIntent;
use App\Models\PosProduct;
use App\Services\AuditLogService;
use App\Services\Cooperative\MemberOrderIntentService;
use App\Services\Integrations\PaymentIntentChargeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberStoreController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        $products = PosProduct::query()
            ->sellable()
            ->with('category')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $query->whereHas('category', function (Builder $query) use ($request): void {
                    $category = $request->string('category')->toString();
                    $query->where('name', $category)->orWhere('slug', $category);
                });
            })
            ->orderBy('name')
            ->get();

        $categories = $products
            ->map(fn (PosProduct $product): ?string => $product->category?->name)
            ->filter()
            ->unique()
            ->values()
            ->prepend('Semua')
            ->all();

        return response()->json([
            'data' => [
                'categories' => $categories,
                'items' => $products->map(fn (PosProduct $product): array => $this->formatProduct($product)),
            ],
        ]);
    }

    public function store(
        StoreMemberStoreOrderRequest $request,
        PaymentIntentChargeService $chargeService,
        MemberOrderIntentService $intentService,
        AuditLogService $audit,
    ): JsonResponse {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null, 403, 'Akun belum terhubung dengan anggota koperasi aktif.');

        $clientReference = (string) ($request->validated('client_reference')
            ?: 'STORE-'.$member->id.'-'.now()->format('YmdHisv'));
        $items = $this->validatedItems($request);
        $subtotal = array_sum(array_map(fn (array $item): float => (float) $item['line_total'], $items));
        abort_if($subtotal <= 0, 422, 'Total belanja harus lebih dari nol.');

        $channel = (string) ($request->validated('channel') ?? $request->validated('payment_method') ?? 'QRIS');
        $fulfillmentMethod = (string) ($request->validated('fulfillment_method') ?? 'PICKUP');
        $pickupLocation = $request->validated('pickup_location');

        $resolution = $intentService->resolveOrCreate(
            member: $member,
            payableType: MemberPaymentIntent::PAYABLE_STORE_ORDER,
            clientReference: $clientReference,
            canonicalRequest: [
                'user_id' => $request->user()?->id,
                'amount' => $subtotal,
                'channel' => $channel,
                'description' => 'Belanja Toko Koperasi',
                'fulfillment_method' => $fulfillmentMethod,
                'pickup_location' => $pickupLocation,
                'notes' => $request->validated('notes'),
                'items' => $items,
                'client_reference' => $clientReference,
            ],
            items: $items,
        );

        $intent = $resolution->intent->refresh();

        if ($resolution->wasCreated()) {
            $audit->log('reservation.created', 'member_payment_intent', $intent, [
                'reason' => 'Store order stock reservation created.',
            ]);
        }

        $charge = $chargeService->ensureCharge($intent);

        return response()->json([
            'data' => $this->formatPendingOrder($intent, $intent->metadata['items'] ?? $items, $charge, $fulfillmentMethod, $pickupLocation),
        ], 201);
    }

    public function showIntent(Request $request, MemberPaymentIntent $intent): JsonResponse
    {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null && (int) $intent->cooperative_member_id === (int) $member->id, 403);

        return response()->json([
            'data' => $this->formatIntentStatus($intent),
        ]);
    }

    private function formatProduct(PosProduct $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'description' => trim(implode(' ', array_filter([$product->brand, $product->variant]))),
            'price' => (float) $product->sale_price,
            'category' => $product->category?->name ?? 'Lainnya',
            'stock' => (int) $product->stock,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'image_url' => $product->image_url,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedItems(StoreMemberStoreOrderRequest $request): array
    {
        $items = [];

        foreach ($request->validated('items') as $item) {
            $product = PosProduct::query()
                ->sellable()
                ->whereKey($item['pos_product_id'])
                ->firstOrFail();

            $quantity = (int) ($item['quantity'] ?? 1);
            abort_if($product->stock < $quantity, 422, "Stok {$product->name} tidak cukup.");

            $lineTotal = round((float) $product->sale_price * $quantity, 2);

            $items[] = [
                'pos_product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => (float) $product->sale_price,
                'line_total' => $lineTotal,
                'product' => $this->formatProduct($product),
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $charge
     * @return array<string, mixed>
     */
    private function formatPendingOrder(
        MemberPaymentIntent $intent,
        array $items,
        array $charge,
        string $fulfillmentMethod,
        ?string $pickupLocation
    ): array {
        return [
            'id' => 'intent:'.$intent->id,
            'payment_intent_id' => $intent->id,
            'order_code' => $intent->gateway_reference,
            'status' => 'PENDING_PAYMENT',
            'status_label' => 'Menunggu Pembayaran',
            'fulfillment_method' => $fulfillmentMethod,
            'pickup_location' => $pickupLocation ?? 'Kantor Koperasi',
            'total_amount' => (float) $intent->amount,
            'payment_intent' => [
                'id' => $intent->id,
                'amount' => (float) $intent->amount,
                'channel' => $intent->channel,
                'gateway_provider' => $intent->gateway_provider,
                'gateway_reference' => $intent->gateway_reference,
                'gateway_status' => $intent->gateway_status,
                'expires_at' => $intent->expires_at?->toISOString(),
            ],
            'charge' => $charge,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatIntentStatus(MemberPaymentIntent $intent): array
    {
        $gatewayStatus = (string) ($intent->gateway_status ?? 'PENDING');
        $isPaid = strtoupper($gatewayStatus) === 'PAID';

        $settledResource = null;
        if ($intent->settled_at && $intent->settled_by_service) {
            $parts = explode(':', (string) $intent->settled_by_service);
            if (count($parts) === 2 && $parts[1] !== '') {
                $settledResource = [
                    'type' => $parts[0],
                    'id' => (int) $parts[1],
                ];
            }
        }

        return [
            'id' => $intent->id,
            'status' => $intent->settled_at ? 'SETTLED' : ($isPaid ? 'PAID' : 'PENDING'),
            'gateway_status' => $gatewayStatus,
            'is_paid' => $isPaid,
            'is_settled' => $intent->settled_at !== null,
            'amount' => (float) $intent->amount,
            'channel' => $intent->channel,
            'payable_type' => $intent->payable_type,
            'settled_resource' => $settledResource,
        ];
    }
}
