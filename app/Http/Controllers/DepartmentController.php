<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $departments = Department::query()
            ->with('organization')
            ->withCount('positions')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->input('search')}%")
                ->orWhere('code', 'like', "%{$request->input('search')}%"))
            ->when($request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->input('organization_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Department/Index', [
            'departments' => $departments,
            'organizations' => Organization::orderBy('name')->get(),
            'filters' => $request->only(['search', 'organization_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code,'.$department->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
