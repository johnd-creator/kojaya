<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\UpdateCoffeeOrderStatusRequest;
use App\Models\CoffeeOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoffeeOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CoffeeOrder::query()
            ->with(['transaction.payments', 'member', 'product', 'preparer', 'completer'])
            ->latest('received_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->whereIn('status', [
                CoffeeOrder::STATUS_RECEIVED,
                CoffeeOrder::STATUS_BREWING,
                CoffeeOrder::STATUS_READY,
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search): void {
                $query->whereHas('transaction', function ($query) use ($search): void {
                    $query->where('transaction_no', 'like', "%{$search}%");
                })
                    ->orWhereHas('member', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('member_no', 'like', "%{$search}%")
                            ->orWhere('no_anggota', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return Inertia::render('Cooperative/Pos/CoffeeOrders/Index', [
            'orders' => $query->paginate(20)->withQueryString()->through(fn (CoffeeOrder $order): array => $this->serialize($order)),
            'filters' => $request->only(['status', 'search']),
            'statuses' => CoffeeOrder::statuses(),
        ]);
    }

    public function updateStatus(UpdateCoffeeOrderStatusRequest $request, CoffeeOrder $coffeeOrder): RedirectResponse
    {
        $data = $request->validated();
        $status = (string) $data['status'];
        $updates = [
            'status' => $status,
            'notes' => $data['notes'] ?? $coffeeOrder->notes,
        ];

        if ($status === CoffeeOrder::STATUS_BREWING && ! $coffeeOrder->brewing_at) {
            $updates['brewing_at'] = now();
            $updates['prepared_by'] = $request->user()?->id;
        }

        if ($status === CoffeeOrder::STATUS_READY && ! $coffeeOrder->ready_at) {
            $updates['ready_at'] = now();
            $updates['prepared_by'] = $coffeeOrder->prepared_by ?: $request->user()?->id;
        }

        if ($status === CoffeeOrder::STATUS_PICKED_UP && ! $coffeeOrder->picked_up_at) {
            $updates['picked_up_at'] = now();
            $updates['completed_by'] = $request->user()?->id;
        }

        if ($status === CoffeeOrder::STATUS_CANCELLED && ! $coffeeOrder->cancelled_at) {
            $updates['cancelled_at'] = now();
            $updates['completed_by'] = $request->user()?->id;
        }

        $coffeeOrder->update($updates);

        return back()->with('success', "Status pesanan {$coffeeOrder->transaction?->transaction_no} diperbarui.");
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(CoffeeOrder $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'quantity' => $order->quantity,
            'customization' => $order->customization,
            'received_at' => $order->received_at?->toISOString(),
            'brewing_at' => $order->brewing_at?->toISOString(),
            'ready_at' => $order->ready_at?->toISOString(),
            'picked_up_at' => $order->picked_up_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'notes' => $order->notes,
            'transaction' => [
                'id' => $order->transaction?->id,
                'transaction_no' => $order->transaction?->transaction_no,
                'total_amount' => (float) ($order->transaction?->total_amount ?? 0),
                'payment_method' => $order->transaction?->payments->first()?->payment_method,
            ],
            'member' => [
                'id' => $order->member?->id,
                'member_no' => $order->member?->member_no ?: $order->member?->no_anggota,
                'name' => $order->member?->name ?: $order->member?->nama_anggota,
            ],
            'product' => [
                'id' => $order->product?->id,
                'name' => $order->product?->name,
            ],
            'preparer' => [
                'id' => $order->preparer?->id,
                'name' => $order->preparer?->name,
            ],
            'completer' => [
                'id' => $order->completer?->id,
                'name' => $order->completer?->name,
            ],
        ];
    }
}
