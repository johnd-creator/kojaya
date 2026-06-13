<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosStockTransferRequest;
use App\Models\PosInventoryLocation;
use App\Models\PosStockTransfer;
use App\Services\Cooperative\PosInventoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosInventoryTransferController extends Controller
{
    public function __construct(private PosInventoryService $service) {}

    public function index(): Response
    {
        $transfers = PosStockTransfer::query()
            ->with(['fromLocation', 'toLocation', 'requester', 'approver', 'items.product'])
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
