<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->query('organization_id');

        $query = Warehouse::with('organization');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $warehouses = $query->orderBy('code')->get();

        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function create()
    {
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return Inertia::render('Warehouses/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function show(string $id)
    {
        $warehouse = Warehouse::with(['organization', 'stocks.sparePart'])->findOrFail($id);

        return Inertia::render('Warehouses/Show', [
            'warehouse' => $warehouse,
        ]);
    }
}
