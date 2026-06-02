<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectGanttLinkRequest;
use App\Models\Project;
use App\Models\ProjectTaskDependency;
use Illuminate\Http\JsonResponse;

class ProjectGanttController extends Controller
{
    public function getData(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->select([
                'id',
                'name as text',
                'start_date',
                'parent_task_id as parent',
                'progress_percentage as progress',
                'status',
                'end_date',
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($task) {
                $duration = null;
                if ($task->start_date && $task->end_date) {
                    $duration = $task->start_date->diffInDays($task->end_date) + 1;
                }

                return [
                    'id' => $task->id,
                    'text' => $task->text,
                    'start_date' => $task->start_date ? $task->start_date->format('Y-m-d 00:00') : null,
                    'end_date' => $task->end_date ? $task->end_date->format('Y-m-d 00:00') : null,
                    'duration' => $duration,
                    'parent' => $task->parent ?? 0,
                    'progress' => $task->progress / 100,
                    'open' => true,
                    'status' => $task->status,
                ];
            });

        $links = ProjectTaskDependency::query()
            ->whereHas('task', fn ($query) => $query->where('project_id', $project->id))
            ->get()
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'source' => $link->predecessor_id,
                    'target' => $link->task_id,
                    'type' => match ($link->type) {
                        'SS' => '1',
                        'FF' => '2',
                        'SF' => '3',
                        default => '0',
                    },
                ];
            });

        return response()->json([
            'data' => $tasks,
            'links' => $links,
        ]);
    }

    public function storeLink(StoreProjectGanttLinkRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $dependency = ProjectTaskDependency::query()->create([
            'predecessor_id' => $validated['source'],
            'task_id' => $validated['target'],
            'type' => match ($validated['type'] ?? null) {
                '1', 'SS' => 'SS',
                '2', 'FF' => 'FF',
                '3', 'SF' => 'SF',
                default => 'FS',
            },
        ]);

        return response()->json([
            'action' => 'inserted',
            'tid' => $dependency->id,
        ]);
    }

    public function destroyLink(Project $project, string $link)
    {
        $this->authorize('update', $project);

        ProjectTaskDependency::query()
            ->whereHas('task', fn ($query) => $query->where('project_id', $project->id))
            ->where('id', $link)
            ->delete();

        return response()->json([
            'action' => 'deleted',
        ]);
    }
}
