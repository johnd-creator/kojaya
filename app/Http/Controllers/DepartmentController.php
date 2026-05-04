<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertDepartmentRequest;
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
            ->when($request->filled('search'), fn ($q) => $q->where(function ($query) use ($request) {
                $search = $request->input('search');

                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
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

    public function store(UpsertDepartmentRequest $request)
    {
        Department::create($request->validated());

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function update(UpsertDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
