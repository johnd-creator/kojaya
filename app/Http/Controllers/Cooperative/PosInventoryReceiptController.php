<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosStockReceiptRequest;
use App\Models\PosInventoryLocation;
use App\Models\PosStockReceipt;
use App\Models\PosSupplier;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosProductAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosInventoryReceiptController extends Controller
{
    public function __construct(
        private PosInventoryService $service,
        private PosProductAccessService $productAccess,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        $receipts = PosStockReceipt::query()
            ->with([
                'supplier',
                'location',
                'receiver',
                'items' => fn (Relation $query): Relation => $query
                    ->whereHas('product', fn (Builder $productQuery): Builder => $this->productAccess->scopeVisibleTo($productQuery, $user))
                    ->with('product'),
            ])
            ->whereHas('items.product', fn (Builder $query): Builder => $this->productAccess->scopeVisibleTo($query, $user))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Cooperative/Inventory/Receipts/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cooperative/Inventory/Receipts/Create', [
            'locations' => PosInventoryLocation::query()->where('is_active', true)->get(),
            'suppliers' => PosSupplier::query()->where('is_active', true)->get(),
        ]);
    }

    public function store(StorePosStockReceiptRequest $request): RedirectResponse
    {
        $this->service->createReceipt($request->validated(), $request->user());

        return to_route('cooperative.pos.inventory.receipts.index')
            ->with('success', 'Penerimaan stok berhasil dicatat.');
    }
}
