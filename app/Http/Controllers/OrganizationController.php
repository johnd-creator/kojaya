<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertOrganizationRequest;
use App\Models\Organization;
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
    public function store(UpsertOrganizationRequest $request)
    {
        Organization::create($request->validated());

        return redirect()->route('organizations.index')->with('success', 'Organization created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpsertOrganizationRequest $request, string $id)
    {
        $organization = Organization::findOrFail($id);

        $organization->update($request->validated());

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
