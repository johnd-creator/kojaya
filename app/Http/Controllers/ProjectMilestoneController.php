<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectMilestoneProgressRequest;
use App\Http\Requests\UpsertProjectMilestoneRequest;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Inertia\Inertia;

class ProjectMilestoneController extends Controller
{
    public function index(Project $project)
    {
        $milestones = $project->milestones()->orderBy('due_date')->get();

        return Inertia::render('ProjectMilestone/Index', [
            'project' => $project,
            'milestones' => $milestones,
        ]);
    }

    public function store(UpsertProjectMilestoneRequest $request, Project $project)
    {
        $validated = $request->validated();

        $validated['project_id'] = $project->id;
        $validated['status'] = 'PENDING';

        $project->milestones()->create($validated);

        return back()->with('success', 'Milestone created successfully.');
    }

    public function update(UpsertProjectMilestoneRequest $request, Project $project, ProjectMilestone $milestone)
    {
        // Ensure milestone belongs to project
        if ($milestone->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validated();

        $milestone->update($validated);

        return back()->with('success', 'Milestone updated successfully.');
    }

    public function updateProgress(UpdateProjectMilestoneProgressRequest $request, Project $project, ProjectMilestone $milestone)
    {
        if ($milestone->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validated();

        $milestone->update($validated);

        if ($milestone->progress_percentage == 100) {
            $milestone->update(['status' => 'COMPLETED']);
        } elseif ($milestone->progress_percentage > 0 && $milestone->status === 'PENDING') {
            $milestone->update(['status' => 'IN_PROGRESS']);
        }

        return back()->with('success', 'Milestone progress updated.');
    }

    public function destroy(Project $project, ProjectMilestone $milestone)
    {
        if ($milestone->project_id !== $project->id) {
            abort(404);
        }

        $milestone->delete();

        return back()->with('success', 'Milestone deleted successfully.');
    }
}
