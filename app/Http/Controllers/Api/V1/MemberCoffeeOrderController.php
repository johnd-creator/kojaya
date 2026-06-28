<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMemberCoffeeOrderRequest;
use App\Models\CoffeeOrder;
use App\Models\PosProduct;
use App\Services\Cooperative\CooperativeNotificationDispatcher;
use App\Services\Cooperative\PosTransactionService;
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
        PosTransactionService $service,
        CooperativeNotificationDispatcher $notificationDispatcher
    ): JsonResponse {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null, 403, 'Akun belum terhubung dengan anggota koperasi aktif.');

        $product = $this->coffeeProductQuery()
            ->whereKey($request->validated('pos_product_id'))
            ->firstOrFail();

        $quantity = (int) ($request->validated('quantity') ?? 1);
        $cupSize = (string) ($request->validated('cup_size') ?? 'Reguler');
        $subtotal = (float) $product->sale_price * $quantity;
        $clientReference = $request->validated('client_reference')
            ?: 'COFFEE-'.$member->id.'-'.now()->format('YmdHisv');

        $transaction = $service->create([
            'client_reference' => $clientReference,
            'cooperative_member_id' => $member->id,
            'payment_method' => (string) ($request->validated('payment_method') ?? 'QRIS'),
            'amount' => $subtotal,
            'cash_received' => $subtotal,
            'discount_amount' => 0,
            'items' => [
                [
                    'pos_product_id' => $product->id,
                    'quantity' => $quantity,
                ],
            ],
        ], $request->user());

        $coffeeOrder = CoffeeOrder::query()->firstOrCreate(
            ['pos_transaction_id' => $transaction->id],
            [
                'cooperative_member_id' => $member->id,
                'pos_product_id' => $product->id,
                'quantity' => $quantity,
                'status' => CoffeeOrder::STATUS_RECEIVED,
                'customization' => [
                    'sugar_level' => (string) ($request->validated('sugar_level') ?? 'Normal'),
                    'ice_level' => (string) ($request->validated('ice_level') ?? 'Normal'),
                    'cup_size' => $cupSize,
                ],
                'received_at' => now(),
            ],
        );

        $coffeeOrder->load(['transaction.payments', 'product']);
        $notificationDispatcher->coffeeOrderReceived($coffeeOrder, $request->user());

        return response()->json([
            'data' => $this->formatOrder($coffeeOrder),
        ], 201);
    }

    public function show(Request $request, CoffeeOrder $coffeeOrder): JsonResponse
    {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null && (int) $coffeeOrder->cooperative_member_id === (int) $member->id, 403);

        $coffeeOrder->load(['transaction.payments', 'product']);

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
            'item' => $product ? $this->formatProduct($product) : null,
            'customization' => $coffeeOrder->customization,
        ];
    }
}
