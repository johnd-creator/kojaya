<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->query('organization_id') ?? session('active_organization_id');
        $status = $request->query('status');

        $query = Asset::with('organization');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $assets = $query->orderBy('code')->get();

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['organization_id', 'status']),
        ]);
    }

    public function create()
    {
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return Inertia::render('Assets/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:assets,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'status' => 'required|in:ACTIVE,INACTIVE,UNDER_MAINTENANCE',
            'purchase_date' => 'nullable|date',
            'serial_number' => 'nullable|string|max:100',
        ]);

        Asset::create($validated);

        return redirect()->route('assets.index')->with('success', 'Asset created successfully.');
    }

    public function show(string $id)
    {
        $asset = Asset::with([
            'organization',
            'workOrders' => function ($query) {
                $query->latest()->limit(10);
            },
        ])->findOrFail($id);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
        ]);
    }

    public function edit(string $id)
    {
        $asset = Asset::findOrFail($id);
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return Inertia::render('Assets/Edit', [
            'asset' => $asset,
            'organizations' => $organizations,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:assets,code,'.$asset->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'status' => 'required|in:ACTIVE,INACTIVE,UNDER_MAINTENANCE',
            'purchase_date' => 'nullable|date',
            'serial_number' => 'nullable|string|max:100',
        ]);

        $asset->update($validated);

        return redirect()->route('assets.index')->with('success', 'Asset updated successfully.');
    }

    public function destroy(string $id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->workOrders()->count() > 0) {
            return back()->with('error', 'Cannot delete asset with existing work orders.');
        }

        $asset->delete();

        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }
}
