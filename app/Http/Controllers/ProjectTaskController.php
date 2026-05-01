<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function store(Request $request, Project $project)
    {
        if ($request->expectsJson()) {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'text' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'parent_task_id' => 'nullable|uuid|exists:project_tasks,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'assigned_to' => 'nullable|exists:employees,id',
                'estimated_hours' => 'nullable|integer|min:0',
                'sort_order' => 'nullable|integer',
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_task_id' => 'nullable|uuid|exists:project_tasks,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'assigned_to' => 'nullable|exists:employees,id',
                'estimated_hours' => 'required|integer|min:0',
                'sort_order' => 'nullable|integer',
            ]);
        }

        $validated['id'] = Str::uuid();
        $validated['project_id'] = $project->id;
        $validated['name'] = $validated['name'] ?? $validated['text'] ?? null;
        $validated['status'] = 'PENDING';
        $validated['progress_percentage'] = 0;
        $validated['actual_hours'] = 0;
        $validated['estimated_hours'] = $validated['estimated_hours'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ProjectTask::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['id' => $validated['id']]);
        }

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Project $project, ProjectTask $task)
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        if ($request->expectsJson()) {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'text' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'parent_task_id' => 'sometimes|nullable|uuid|exists:project_tasks,id',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
                'assigned_to' => 'sometimes|nullable|exists:employees,id',
                'status' => 'sometimes|required|in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
                'estimated_hours' => 'sometimes|integer|min:0',
                'actual_hours' => 'sometimes|integer|min:0',
                'progress_percentage' => 'sometimes|integer|min:0|max:100',
                'progress' => 'sometimes|numeric|min:0|max:1',
                'sort_order' => 'sometimes|integer|min:0',
            ]);

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
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_task_id' => 'nullable|uuid|exists:project_tasks,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'assigned_to' => 'nullable|exists:employees,id',
                'status' => 'required|in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED',
                'estimated_hours' => 'required|integer|min:0',
                'actual_hours' => 'required|integer|min:0',
                'progress_percentage' => 'required|integer|min:0|max:100',
                'sort_order' => 'required|integer|min:0',
            ]);

            $task->update($validated);
        }

        $this->updateProjectProgress($task->project);

        if ($request->expectsJson()) {
            return response()->json(['id' => $task->id]);
        }

        return back()->with('success', 'Task updated successfully.');
    }

    public function updateProgress(Request $request, Project $project, ProjectTask $task)
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100',
            'actual_hours' => 'required|integer|min:0',
        ]);

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
