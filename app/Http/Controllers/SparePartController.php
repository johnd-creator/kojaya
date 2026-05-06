<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSparePartRequest;
use App\Http\Requests\UpdateSparePartStockRequest;
use App\Models\SparePart;
use App\Models\SparePartStock;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('manage_spare_parts');

        $categoryId = $request->query('category');
        $lowStock = $request->query('low_stock');

        $query = SparePart::with(['stocks.warehouse', 'organization']);

        if ($categoryId) {
            $query->where('category', $categoryId);
        }

        if ($lowStock) {
            $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM spare_part_stocks WHERE spare_part_stocks.spare_part_id = spare_parts.id) <= min_stock');
        }

        $spareParts = $query->orderBy('code')->get();

        return Inertia::render('SpareParts/Index', [
            'spareParts' => $spareParts->map(function ($part) {
                return [
                    'id' => $part->id,
                    'code' => $part->code,
                    'name' => $part->name,
                    'specification' => $part->specification,
                    'unit' => $part->unit,
                    'category' => $part->category,
                    'min_stock' => $part->min_stock,
                    'max_stock' => $part->max_stock,
                    'reorder_level' => $part->reorder_level,
                    'total_stock' => $part->total_stock,
                    'available_stock' => $part->available_stock,
                    'is_below_min' => $part->isBelowMinStock(),
                    'is_below_reorder' => $part->isBelowReorderLevel(),
                    'organization' => $part->organization,
                    'stocks' => $part->stocks,
                ];
            }),
        ]);
    }

    public function create()
    {
        $this->authorizePermission('manage_spare_parts');

        $organizations = \App\Models\Organization::orderBy('name')->get();

        return Inertia::render('SpareParts/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreSparePartRequest $request)
    {
        $this->authorizePermission('manage_spare_parts');

        SparePart::create($request->validated());

        return redirect()->route('spare-parts.index')->with('success', 'Spare part created successfully.');
    }

    public function show(string $id)
    {
        $this->authorizePermission('manage_spare_parts');

        $sparePart = SparePart::with(['organization', 'stocks.warehouse'])->findOrFail($id);

        return Inertia::render('SpareParts/Show', [
            'sparePart' => $sparePart,
        ]);
    }

    public function updateStock(UpdateSparePartStockRequest $request, string $id)
    {
        $this->authorizePermission('manage_spare_parts');

        $validated = $request->validated();

        $sparePart = SparePart::findOrFail($id);
        $stock = SparePartStock::firstOrCreate(
            [
                'spare_part_id' => $sparePart->id,
                'warehouse_id' => $validated['warehouse_id'],
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );

        $quantity = $validated['quantity'];

        match ($validated['type']) {
            'IN' => $stock->increment('quantity', $quantity),
            'OUT' => $stock->decrement('quantity', $quantity),
            'ADJUST' => $stock->update(['quantity' => $quantity]),
        };

        return back()->with('success', 'Stock updated successfully.');
    }
}
