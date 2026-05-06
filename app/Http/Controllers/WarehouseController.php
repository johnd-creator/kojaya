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
        $this->authorizePermission('manage_warehouses');

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
        $this->authorizePermission('manage_warehouses');

        $organizations = \App\Models\Organization::orderBy('name')->get();

        return Inertia::render('Warehouses/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $this->authorizePermission('manage_warehouses');

        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function show(string $id)
    {
        $this->authorizePermission('manage_warehouses');

        $warehouse = Warehouse::with(['organization', 'stocks.sparePart'])->findOrFail($id);

        return Inertia::render('Warehouses/Show', [
            'warehouse' => $warehouse,
        ]);
    }
}
