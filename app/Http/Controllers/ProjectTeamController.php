<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkAssignProjectTeamRequest;
use App\Http\Requests\CheckProjectTeamAvailabilityRequest;
use App\Http\Requests\StoreProjectTeamRequest;
use App\Http\Requests\UpdateProjectTeamMobilizationRequest;
use App\Http\Requests\UpdateProjectTeamRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTeam;
use Inertia\Inertia;
use Inertia\Response;

class ProjectTeamController extends Controller
{
    public function availability(CheckProjectTeamAvailabilityRequest $request, Project $project)
    {
        $validated = $request->validated();

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

    public function store(StoreProjectTeamRequest $request, Project $project)
    {
        $validated = $request->validated();

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

    public function update(UpdateProjectTeamRequest $request, ProjectTeam $teamMember)
    {
        $validated = $request->validated();

        $teamMember->update($validated);

        return back()->with('success', 'Team member updated successfully.');
    }

    public function updateMobilization(UpdateProjectTeamMobilizationRequest $request, ProjectTeam $teamMember)
    {
        $validated = $request->validated();

        $teamMember->update($validated);

        return back()->with('success', 'Mobilization status updated successfully.');
    }

    public function destroy(ProjectTeam $teamMember)
    {
        $teamMember->delete();

        return back()->with('success', 'Team member removed successfully.');
    }

    public function bulkAssign(BulkAssignProjectTeamRequest $request, Project $project)
    {
        $validated = $request->validated();

        foreach ($validated['employee_ids'] as $employeeId) {
            ProjectTeam::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'role' => $validated['role'],
                    'start_date' => $validated['start_date'],
                    'daily_rate_cost' => $validated['daily_rate_cost'],
                ]
            );
        }

        return back()->with('success', count($validated['employee_ids']).' team members assigned successfully.');
    }
}
