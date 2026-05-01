<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
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

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        $validated['project_id'] = $project->id;
        $validated['status'] = 'PENDING';

        $project->milestones()->create($validated);

        return back()->with('success', 'Milestone created successfully.');
    }

    public function update(Request $request, Project $project, ProjectMilestone $milestone)
    {
        // Ensure milestone belongs to project
        if ($milestone->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|in:PENDING,IN_PROGRESS,COMPLETED,OVERDUE',
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        $milestone->update($validated);

        return back()->with('success', 'Milestone updated successfully.');
    }

    public function updateProgress(Request $request, Project $project, ProjectMilestone $milestone)
    {
        if ($milestone->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

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
