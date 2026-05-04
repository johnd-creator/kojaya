<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskProgressRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectTaskController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $query = $project->tasks()->with(['assignee', 'parent']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->orderBy('sort_order')->orderBy('start_date')->get();

        $employees = Employee::where('status', 'ACTIVE')
            ->where('organization_id', $project->organization_id)
            ->get();

        return Inertia::render('ProjectTask/Index', [
            'project' => $project,
            'tasks' => $tasks,
            'employees' => $employees,
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(StoreProjectTaskRequest $request, Project $project)
    {
        $validated = $request->validated();

        $validated['project_id'] = $project->id;
        $validated['name'] = $validated['name'] ?? $validated['text'] ?? null;
        $validated['status'] = 'PENDING';
        $validated['progress_percentage'] = 0;
        $validated['actual_hours'] = 0;
        $validated['estimated_hours'] = $validated['estimated_hours'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $task = ProjectTask::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['id' => $task->id]);
        }

        return back()->with('success', 'Task created successfully.');
    }

    public function update(UpdateProjectTaskRequest $request, Project $project, ProjectTask $task)
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        if ($request->expectsJson()) {
            $validated = $request->validated();

            $update = $validated;
            if (array_key_exists('text', $validated) && ! array_key_exists('name', $validated)) {
                $update['name'] = $validated['text'];
            }
            if (array_key_exists('progress', $validated) && ! array_key_exists('progress_percentage', $validated)) {
                $update['progress_percentage'] = (int) round(((float) $validated['progress']) * 100);
            }
            unset($update['text'], $update['progress']);

            $task->update($update);
        } else {
            $validated = $request->validated();

            $task->update($validated);
        }

        $this->updateProjectProgress($task->project);

        if ($request->expectsJson()) {
            return response()->json(['id' => $task->id]);
        }

        return back()->with('success', 'Task updated successfully.');
    }

    public function updateProgress(UpdateProjectTaskProgressRequest $request, Project $project, ProjectTask $task)
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validated();

        $task->update($validated);

        if ($task->progress_percentage == 100) {
            $task->update(['status' => 'COMPLETED']);
        }

        $this->updateProjectProgress($task->project);

        if ($request->expectsJson()) {
            return response()->json(['id' => $task->id]);
        }

        return back()->with('success', 'Task progress updated.');
    }

    public function destroy(Request $request, Project $project, ProjectTask $task)
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $project = $task->project;
        $task->delete();

        $this->updateProjectProgress($project);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'deleted']);
        }

        return back()->with('success', 'Task deleted successfully.');
    }

    private function updateProjectProgress(Project $project)
    {
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();

        if ($totalTasks > 0) {
            $completedTasks = $tasks->where('status', 'COMPLETED')->count();
            $progress = (int) (($completedTasks / $totalTasks) * 100);

            $project->update(['progress_percentage' => $progress]);
        }
    }
}
