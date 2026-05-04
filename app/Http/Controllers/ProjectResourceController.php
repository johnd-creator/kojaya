<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectAssetAllocationRequest;
use App\Http\Requests\UpdateProjectAssetAllocationRequest;
use App\Models\Asset;
use App\Models\Project;
use App\Models\ProjectAssetAllocation;
use Inertia\Inertia;

class ProjectResourceController extends Controller
{
    public function index(Project $project)
    {
        $project->load(['team.employee']);

        $assetAllocations = $project->assetAllocations()
            ->with('asset')
            ->orderBy('start_date')
            ->get();

        $availableAssets = Asset::where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return Inertia::render('Project/Resources', [
            'project' => $project,
            'assetAllocations' => $assetAllocations,
            'availableAssets' => $availableAssets,
        ]);
    }

    public function storeAsset(StoreProjectAssetAllocationRequest $request, Project $project)
    {
        $validated = $request->validated();

        $newStartDate = $validated['start_date'];
        // Use a far future date if end_date is null (indefinite allocation)
        $newEndDate = $validated['end_date'] ?? '2099-12-31';

        // Check for overlapping allocations for the same asset
        // Logic: (StartA <= EndB) and (EndA >= StartB)
        $conflicts = ProjectAssetAllocation::where('asset_id', $validated['asset_id'])
            ->where('status', '!=', 'demobilized') // Ignore demobilized records
            ->where(function ($query) use ($newStartDate, $newEndDate) {
                $query->where('start_date', '<=', $newEndDate)
                    ->where(function ($q) use ($newStartDate) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $newStartDate);
                    });
            })
            ->exists();

        if ($conflicts) {
            return back()->withErrors(['asset_id' => 'This asset is already allocated to another project during the selected period.']);
        }

        $project->assetAllocations()->create($validated);

        return back()->with('success', 'Asset allocated successfully.');
    }

    public function updateAsset(UpdateProjectAssetAllocationRequest $request, Project $project, ProjectAssetAllocation $allocation)
    {
        $validated = $request->validated();

        $newStartDate = $validated['start_date'];
        $newEndDate = $validated['end_date'] ?? '2099-12-31';

        $conflicts = ProjectAssetAllocation::query()
            ->where('asset_id', $allocation->asset_id)
            ->where('id', '!=', $allocation->id)
            ->where('status', '!=', 'demobilized')
            ->where(function ($query) use ($newStartDate, $newEndDate) {
                $query
                    ->where('start_date', '<=', $newEndDate)
                    ->where(function ($q) use ($newStartDate) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $newStartDate);
                    });
            })
            ->exists();

        if ($conflicts) {
            return back()->withErrors(['asset_id' => 'This asset is already allocated during the selected period.']);
        }

        $allocation->update($validated);

        return back()->with('success', 'Asset allocation updated successfully.');
    }

    public function destroyAsset(Project $project, ProjectAssetAllocation $allocation)
    {
        $allocation->delete();

        return back()->with('success', 'Asset allocation removed.');
    }
}
