<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobGrade;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(Request $request): Response
    {
        $positions = Position::query()
            ->with(['department', 'jobGrade'])
            ->withCount('employees')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->input('search')}%")
                ->orWhere('code', 'like', "%{$request->input('search')}%"))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->input('department_id')))
            ->when($request->filled('job_grade_id'), fn ($q) => $q->where('job_grade_id', $request->input('job_grade_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Position/Index', [
            'positions' => $positions,
            'departments' => Department::orderBy('name')->get(),
            'jobGrades' => JobGrade::orderBy('level')->get(),
            'filters' => $request->only(['search', 'department_id', 'job_grade_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:positions,code',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'job_grade_id' => 'required|exists:job_grades,id',
        ]);

        Position::create($validated);

        return redirect()->route('positions.index')->with('success', 'Position created.');
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:positions,code,'.$position->id,
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'job_grade_id' => 'required|exists:job_grades,id',
        ]);

        $position->update($validated);

        return redirect()->route('positions.index')->with('success', 'Position updated.');
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Position deleted.');
    }
}
