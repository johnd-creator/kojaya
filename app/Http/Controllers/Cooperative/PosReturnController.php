<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosReturnRequest;
use App\Models\PosTransaction;
use App\Services\Cooperative\PosReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosReturnController extends Controller
{
    public function __construct(private PosReturnService $service) {}

    public function create(string $transaction, Request $request, OrganizationScopedQueryService $scopedQuery): Response
    {
        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(
            PosTransaction::query()->with(['items.product', 'member', 'cashier']),
            $request->user(),
            $transaction
        );

        $existingReturns = $transactionModel->returns()->with('items')->get();
        $returnedQuantities = $existingReturns
            ->flatMap(fn ($return) => $return->items->map(fn ($item) => [
                'pos_transaction_item_id' => $item->pos_transaction_item_id,
                'quantity' => $item->quantity,
            ]))
            ->groupBy('pos_transaction_item_id')
            ->map(fn ($items) => $items->sum('quantity'))
            ->toArray();

        $items = $transactionModel->items->map(fn ($item) => [
            'id' => $item->id,
            'product' => $item->product?->name,
            'quantity' => $item->quantity,
            'returned' => $returnedQuantities[$item->id] ?? 0,
            'max_returnable' => max($item->quantity - ($returnedQuantities[$item->id] ?? 0), 0),
            'unit_price' => (float) $item->unit_price,
        ]);

        return Inertia::render('Cooperative/Pos/Returns/Create', [
            'transaction' => $transactionModel,
            'items' => $items,
        ]);
    }

    public function store(StorePosReturnRequest $request, string $transaction, OrganizationScopedQueryService $scopedQuery): RedirectResponse
    {
        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(PosTransaction::class, $request->user(), $transaction);

        $this->service->create([
            'pos_transaction_id' => $transactionModel->id,
            'reason' => $request->validated('reason'),
            'items' => $request->validated('items'),
        ], $request->user());

        return to_route('cooperative.pos.transactions.show', $transactionModel)
            ->with('success', 'Retur POS berhasil dibuat.');
    }
}
