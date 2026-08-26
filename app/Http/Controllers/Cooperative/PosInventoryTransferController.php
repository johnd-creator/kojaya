<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosStockTransferRequest;
use App\Models\PosInventoryLocation;
use App\Models\PosStockTransfer;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosProductAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosInventoryTransferController extends Controller
{
    public function __construct(
        private PosInventoryService $service,
        private PosProductAccessService $productAccess,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        $transfers = PosStockTransfer::query()
            ->with([
                'fromLocation',
                'toLocation',
                'requester',
                'approver',
                'items' => fn (Builder $query): Builder => $query
                    ->whereHas('product', fn (Builder $productQuery): Builder => $this->productAccess->scopeVisibleTo($productQuery, $user))
                    ->with('product'),
            ])
            ->whereHas('items.product', fn (Builder $query): Builder => $this->productAccess->scopeVisibleTo($query, $user))
            ->orderByDesc('transferred_at')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Cooperative/Inventory/Transfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cooperative/Inventory/Transfers/Create', [
            'locations' => PosInventoryLocation::query()->where('is_active', true)->get(),
        ]);
    }

    public function store(StorePosStockTransferRequest $request): RedirectResponse
    {
        $this->service->createTransfer($request->validated(), $request->user());

        return to_route('cooperative.pos.inventory.transfers.index')
            ->with('success', 'Transfer stok berhasil dilakukan.');
    }
}
