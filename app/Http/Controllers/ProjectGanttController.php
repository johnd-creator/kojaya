<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectGanttController extends Controller
{
    public function getData(Project $project): JsonResponse
    {
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

        $links = DB::table('project_task_dependencies')
            ->join('project_tasks', 'project_task_dependencies.task_id', '=', 'project_tasks.id')
            ->where('project_tasks.project_id', $project->id)
            ->select([
                'project_task_dependencies.id',
                'project_task_dependencies.predecessor_id as source',
                'project_task_dependencies.task_id as target',
                'project_task_dependencies.type',
            ])
            ->get()
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'source' => $link->source,
                    'target' => $link->target,
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

    public function storeLink(Request $request, Project $project)
    {
        $validated = $request->validate([
            'source' => 'required|exists:project_tasks,id',
            'target' => 'required|exists:project_tasks,id',
            'type' => 'nullable|string',
        ]);

        $id = Str::uuid();

        DB::table('project_task_dependencies')->insert([
            'id' => $id,
            'predecessor_id' => $validated['source'],
            'task_id' => $validated['target'],
            'type' => match ($validated['type'] ?? null) {
                '1', 'SS' => 'SS',
                '2', 'FF' => 'FF',
                '3', 'SF' => 'SF',
                default => 'FS',
            },
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'action' => 'inserted',
            'tid' => $id,
        ]);
    }

    public function destroyLink(Project $project, string $link)
    {
        DB::table('project_task_dependencies')
            ->join('project_tasks', 'project_task_dependencies.task_id', '=', 'project_tasks.id')
            ->where('project_tasks.project_id', $project->id)
            ->where('project_task_dependencies.id', $link)
            ->delete();

        return response()->json([
            'action' => 'deleted',
        ]);
    }
}
