<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMemberCoffeeOrderRequest;
use App\Models\CoffeeOrder;
use App\Models\MemberPaymentIntent;
use App\Models\PosProduct;
use App\Services\Integrations\PaymentGatewayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCoffeeOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = $this->coffeeProductQuery()
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PosProduct $product): array => $this->formatProduct($product));

        return response()->json([
            'data' => [
                'categories' => ['Semua', 'Signature', 'Espresso', 'Non-Coffee'],
                'items' => $products,
                'options' => [
                    'sugar_levels' => ['Normal', 'Less Sugar', 'No Sugar'],
                    'ice_levels' => ['Normal', 'Less Ice', 'Warm'],
                    'cup_sizes' => [
                        ['label' => 'Reguler', 'price_delta' => 0],
                        ['label' => 'Large', 'price_delta' => 0],
                    ],
                ],
            ],
        ]);
    }

    public function store(
        StoreMemberCoffeeOrderRequest $request,
        PaymentGatewayService $gateway
    ): JsonResponse {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null, 403, 'Akun belum terhubung dengan anggota koperasi aktif.');

        $items = $this->validatedItems($request);
        $subtotal = array_sum(array_map(fn (array $item): float => (float) $item['line_total'], $items));
        $channel = (string) ($request->validated('channel') ?? $request->validated('payment_method') ?? 'QRIS');
        $clientReference = $request->validated('client_reference')
            ?: 'COFFEE-'.$member->id.'-'.now()->format('YmdHisv');

        $existing = MemberPaymentIntent::query()
            ->where('cooperative_member_id', $member->id)
            ->where('payable_type', MemberPaymentIntent::PAYABLE_COFFEE_ORDER)
            ->whereNull('settled_at')
            ->where('metadata->client_reference', $clientReference)
            ->latest('id')
            ->first();

        if ($existing) {
            $charge = $gateway->createIntentCharge($existing->refresh());

            return response()->json([
                'data' => $this->formatPendingOrder($existing->refresh(), $existing->metadata['items'] ?? [], $charge),
            ], 201);
        }

        $intent = MemberPaymentIntent::query()->create([
            'user_id' => $request->user()?->id,
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_COFFEE_ORDER,
            'payable_id' => null,
            'amount' => $subtotal,
            'channel' => $channel,
            'gateway_status' => 'PENDING',
            'metadata' => [
                'description' => 'Pesanan Kopi Kojaya',
                'client_reference' => $clientReference,
                'items' => $items,
            ],
            'expires_at' => now()->addMinutes(30),
        ]);

        $charge = $gateway->createIntentCharge($intent);

        return response()->json([
            'data' => $this->formatPendingOrder($intent->refresh(), $items, $charge),
        ], 201);
    }

    public function show(Request $request, CoffeeOrder $coffeeOrder): JsonResponse
    {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null && (int) $coffeeOrder->cooperative_member_id === (int) $member->id, 403);

        $coffeeOrder->load(['transaction.payments', 'transaction.items.product', 'product']);

        return response()->json([
            'data' => $this->formatOrder($coffeeOrder),
        ]);
    }

    private function coffeeProductQuery(): Builder
    {
        return PosProduct::query()
            ->sellable()
            ->where(function ($query): void {
                $query->whereHas('category', function ($query): void {
                    $query->whereIn('name', ['Signature', 'Espresso', 'Non-Coffee'])
                        ->orWhereIn('slug', ['signature', 'espresso', 'non-coffee', 'noncoffee', 'kopi']);
                })
                    ->orWhere('name', 'like', '%Kopi%')
                    ->orWhere('name', 'like', '%Coffee%')
                    ->orWhere('name', 'like', '%Espresso%')
                    ->orWhere('name', 'like', '%Latte%')
                    ->orWhere('name', 'like', '%Macchiato%')
                    ->orWhere('name', 'like', '%Cappuccino%')
                    ->orWhere('name', 'like', '%Capuccino%')
                    ->orWhere('name', 'like', '%Matcha%')
                    ->orWhere('name', 'like', '%Chocolate%');
            });
    }

    private function formatProduct(PosProduct $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'description' => trim(implode(' ', array_filter([$product->brand, $product->variant]))),
            'price' => (float) $product->sale_price,
            'category' => $product->category?->name ?? 'Signature',
            'stock' => (int) $product->stock,
            'image_url' => $product->image_url,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedItems(StoreMemberCoffeeOrderRequest $request): array
    {
        $validated = $request->validated();
        $rawItems = $validated['items'] ?? [[
            'pos_product_id' => $validated['pos_product_id'],
            'quantity' => $validated['quantity'] ?? 1,
            'sugar_level' => $validated['sugar_level'] ?? 'Normal',
            'ice_level' => $validated['ice_level'] ?? 'Normal',
            'cup_size' => $validated['cup_size'] ?? 'Reguler',
        ]];

        $items = [];

        foreach ($rawItems as $item) {
            $product = $this->coffeeProductQuery()
                ->whereKey($item['pos_product_id'])
                ->firstOrFail();

            $quantity = (int) ($item['quantity'] ?? 1);
            $lineTotal = round((float) $product->sale_price * $quantity, 2);

            $items[] = [
                'pos_product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => (float) $product->sale_price,
                'line_total' => $lineTotal,
                'sugar_level' => (string) ($item['sugar_level'] ?? 'Normal'),
                'ice_level' => (string) ($item['ice_level'] ?? 'Normal'),
                'cup_size' => (string) ($item['cup_size'] ?? 'Reguler'),
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
    private function formatPendingOrder(MemberPaymentIntent $intent, array $items, array $charge): array
    {
        return [
            'id' => 'intent:'.$intent->id,
            'payment_intent_id' => $intent->id,
            'order_code' => $intent->gateway_reference,
            'status' => 'PENDING_PAYMENT',
            'status_label' => 'Menunggu Pembayaran',
            'step' => 0,
            'pickup_location' => 'Kantin Kojaya',
            'estimated_ready_minutes' => 10,
            'received_at' => null,
            'brewing_at' => null,
            'ready_at' => null,
            'picked_up_at' => null,
            'cancelled_at' => null,
            'transaction' => [
                'id' => null,
                'transaction_no' => null,
                'total_amount' => (float) $intent->amount,
                'payment_method' => $intent->channel,
            ],
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
            'item' => $items[0]['product'] ?? null,
            'customization' => [
                'items' => $items,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(CoffeeOrder $coffeeOrder): array
    {
        $transaction = $coffeeOrder->transaction;
        $product = $coffeeOrder->product;

        return [
            'id' => $coffeeOrder->id,
            'order_code' => $transaction?->transaction_no,
            'status' => $coffeeOrder->status,
            'status_label' => $coffeeOrder->statusLabel(),
            'step' => $coffeeOrder->mobileStep(),
            'pickup_location' => 'Kantin Kojaya',
            'estimated_ready_minutes' => 10,
            'received_at' => $coffeeOrder->received_at?->toISOString(),
            'brewing_at' => $coffeeOrder->brewing_at?->toISOString(),
            'ready_at' => $coffeeOrder->ready_at?->toISOString(),
            'picked_up_at' => $coffeeOrder->picked_up_at?->toISOString(),
            'cancelled_at' => $coffeeOrder->cancelled_at?->toISOString(),
            'transaction' => [
                'id' => $transaction?->id,
                'transaction_no' => $transaction?->transaction_no,
                'total_amount' => (float) ($transaction?->total_amount ?? 0),
                'payment_method' => $transaction?->payments->first()?->payment_method,
            ],
            'payment_intent' => null,
            'charge' => null,
            'items' => $transaction?->items?->map(fn ($item): array => [
                'pos_product_id' => $item->pos_product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
                'product' => $item->product ? $this->formatProduct($item->product) : null,
            ])->values()->all() ?? [],
            'item' => $product ? $this->formatProduct($product) : null,
            'customization' => $coffeeOrder->customization,
        ];
    }
}
