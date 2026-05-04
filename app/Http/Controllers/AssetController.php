<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAssetRequest;
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

    public function store(UpsertAssetRequest $request)
    {
        Asset::create($request->validated());

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

    public function update(UpsertAssetRequest $request, string $id)
    {
        $asset = Asset::findOrFail($id);

        $asset->update($request->validated());

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
