<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get organizations with their parent relationship loaded
        $organizations = Organization::with('parent')->orderBy('level')->orderBy('name')->get();

        return Inertia::render('Organization/Index', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:organizations,code',
            'type' => 'required|in:HEAD_OFFICE,REGIONAL,BRANCH,SITE',
            'level' => 'required|in:L0,L1,L2,L3',
            'parent_id' => 'nullable|exists:organizations,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        Organization::create($validated);

        return redirect()->route('organizations.index')->with('success', 'Organization created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:organizations,code,'.$organization->id,
            'type' => 'required|in:HEAD_OFFICE,REGIONAL,BRANCH,SITE',
            'level' => 'required|in:L0,L1,L2,L3',
            'parent_id' => 'nullable|exists:organizations,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $organization->update($validated);

        return redirect()->route('organizations.index')->with('success', 'Organization updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $organization = Organization::findOrFail($id);

        // Optional: Prevent deletion if it has children or users
        if ($organization->children()->count() > 0) {
            return redirect()->route('organizations.index')->with('error', 'Cannot delete organization with child units.');
        }

        if ($organization->users()->count() > 0) {
            return redirect()->route('organizations.index')->with('error', 'Cannot delete organization with assigned users.');
        }

        $organization->delete();

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully.');
    }
}
