<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosStockCountRequest;
use App\Models\PosInventoryLocation;
use App\Models\PosStockCount;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosProductAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosInventoryCountController extends Controller
{
    public function __construct(
        private PosInventoryService $service,
        private PosProductAccessService $productAccess,
    ) {}

    public function index(): Response
    {
        $user = request()->user();
        $counts = PosStockCount::query()
            ->with([
                'location',
                'requester',
                'approver',
                'items' => fn (Builder $query): Builder => $query
                    ->whereHas('product', fn (Builder $productQuery): Builder => $this->productAccess->scopeVisibleTo($productQuery, $user))
                    ->with('product'),
            ])
            ->whereHas('items.product', fn (Builder $query): Builder => $this->productAccess->scopeVisibleTo($query, $user))
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
        $this->service->assertCountCanBeOperated($count, request()->user());
        $count->load(['items.product', 'location', 'requester', 'approver']);

        return Inertia::render('Cooperative/Inventory/Counts/Show', [
            'count' => $count,
        ]);
    }

    public function submit(PosStockCount $count): RedirectResponse
    {
        $this->service->assertCountCanBeOperated($count, request()->user());
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
