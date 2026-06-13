<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosStockCountRequest;
use App\Models\PosInventoryLocation;
use App\Models\PosStockCount;
use App\Services\Cooperative\PosInventoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosInventoryCountController extends Controller
{
    public function __construct(private PosInventoryService $service) {}

    public function index(): Response
    {
        $counts = PosStockCount::query()
            ->with(['location', 'requester', 'approver', 'items.product'])
            ->orderByDesc('counted_at')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Cooperative/Inventory/Counts/Index', [
            'counts' => $counts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cooperative/Inventory/Counts/Create', [
            'locations' => PosInventoryLocation::query()->where('is_active', true)->get(),
        ]);
    }

    public function store(StorePosStockCountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $count = $this->service->createCount(
            (int) $data['pos_inventory_location_id'],
            $data['items'],
            $request->user(),
        );

        return to_route('cooperative.pos.inventory.counts.show', $count)
            ->with('success', 'Stock opname berhasil dibuat (draft).');
    }

    public function show(PosStockCount $count): Response
    {
        $count->load(['items.product', 'location', 'requester', 'approver']);

        return Inertia::render('Cooperative/Inventory/Counts/Show', [
            'count' => $count,
        ]);
    }

    public function submit(PosStockCount $count): RedirectResponse
    {
        $this->service->submitForReview($count);

        return back()->with('success', 'Stock opname dikirim untuk review.');
    }

    public function approve(PosStockCount $count): RedirectResponse
    {
        $this->service->approveCount($count, request()->user());

        return to_route('cooperative.pos.inventory.counts.show', $count)
            ->with('success', 'Stock opname disetujui dan disesuaikan.');
    }
}
