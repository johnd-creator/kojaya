<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:STORAGE,REPAIR,DISPOSAL',
        ]);

        Warehouse::create($validated);

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
