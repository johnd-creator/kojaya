<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectTeamController extends Controller
{
    public function availability(Request $request, Project $project)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'] ?? '2099-12-31';

        $conflicts = ProjectTeam::query()
            ->with(['project:id,name,project_code'])
            ->where('employee_id', $validated['employee_id'])
            ->where('project_id', '!=', $project->id)
            ->where('start_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn (ProjectTeam $assignment) => [
                'project_id' => $assignment->project_id,
                'project_name' => $assignment->project?->name,
                'project_code' => $assignment->project?->project_code,
                'start_date' => optional($assignment->start_date)->toDateString(),
                'end_date' => optional($assignment->end_date)?->toDateString(),
                'role' => $assignment->role,
            ]);

        return response()->json([
            'available' => $conflicts->isEmpty(),
            'conflicts' => $conflicts,
        ]);
    }

    public function index(Project $project): Response
    {
        $project->load('team.employee');

        $availableEmployees = Employee::where('status', 'ACTIVE')
            ->where('organization_id', $project->organization_id)
            ->whereNotIn('id', $project->team->pluck('employee_id')->toArray())
            ->get();

        return Inertia::render('ProjectTeam/Index', [
            'project' => $project,
            'team' => $project->team,
            'availableEmployees' => $availableEmployees,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'daily_rate_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['id'] = Str::uuid();
        $validated['project_id'] = $project->id;

        // Check for conflicts
        $conflicts = ProjectTeam::where('employee_id', $validated['employee_id'])
            ->where('project_id', '!=', $project->id)
            ->where(function ($query) use ($validated) {
                $endDate = $validated['end_date'] ?? '2099-12-31';
                $query->where('start_date', '<=', $endDate)
                    ->where(function ($q) use ($validated) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $validated['start_date']);
                    });
            })
            ->exists();

        if ($conflicts) {
            return back()->withErrors(['employee_id' => 'This employee is already assigned to another project during this period.']);
        }

        ProjectTeam::create($validated);

        return back()->with('success', 'Team member added successfully.');
    }

    public function update(Request $request, ProjectTeam $teamMember)
    {
        $validated = $request->validate([
            'role' => 'required|string|max:100',
            'end_date' => 'nullable|date',
            'daily_rate_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:RECRUITMENT,SCREENING,MCU,ONBOARDING,PLACED',
            'has_ppe' => 'nullable|boolean',
            'has_uniform' => 'nullable|boolean',
        ]);

        $teamMember->update($validated);

        return back()->with('success', 'Team member updated successfully.');
    }

    public function updateMobilization(Request $request, ProjectTeam $teamMember)
    {
        $validated = $request->validate([
            'status' => 'required|in:RECRUITMENT,SCREENING,MCU,ONBOARDING,PLACED',
            'has_ppe' => 'nullable|boolean',
            'has_uniform' => 'nullable|boolean',
        ]);

        $teamMember->update($validated);

        return back()->with('success', 'Mobilization status updated successfully.');
    }

    public function destroy(ProjectTeam $teamMember)
    {
        $teamMember->delete();

        return back()->with('success', 'Team member removed successfully.');
    }

    public function bulkAssign(Request $request, Project $project)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'role' => 'required|string|max:100',
            'start_date' => 'required|date',
            'daily_rate_cost' => 'required|numeric|min:0',
        ]);

        foreach ($validated['employee_ids'] as $employeeId) {
            ProjectTeam::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'id' => Str::uuid(),
                    'role' => $validated['role'],
                    'start_date' => $validated['start_date'],
                    'daily_rate_cost' => $validated['daily_rate_cost'],
                ]
            );
        }

        return back()->with('success', count($validated['employee_ids']).' team members assigned successfully.');
    }
}
