<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectProgressRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->with(['organization', 'client', 'team.employee']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('project_code', 'like', "%{$search}%");
            });
        }

        $projects = $query->orderByDesc('start_date')->paginate(20)->withQueryString();

        $organizations = Organization::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        $stats = [
            'total_projects' => Project::count(),
            'ongoing_projects' => Project::ongoing()->count(),
            'completed_projects' => Project::completed()->count(),
            'total_budget' => Project::sum('budget'),
            'total_actual_cost' => Project::sum('actual_cost'),
        ];

        return Inertia::render('Project/Index', [
            'projects' => $projects,
            'organizations' => $organizations,
            'clients' => $clients,
            'filters' => $request->only(['status', 'organization_id', 'search']),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Project/Create', [
            'organizations' => Organization::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        $validated['progress_percentage'] = 0;
        $validated['actual_cost'] = 0;

        Project::create($validated);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project): Response
    {
        $project->load(['organization', 'client', 'team.employee', 'tasks.assignee', 'milestones', 'documents', 'assetAllocations.asset']);

        $rootTasks = $project->tasks()->root()->with(['children.assignee', 'assignee'])->get();

        $documents = $project->documents()->orderBy('created_at', 'desc')->get();

        $availableAssets = \App\Models\Asset::where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $availableEmployees = Employee::where('status', 'ACTIVE')
            ->where('organization_id', $project->organization_id)
            ->whereNotIn('id', $project->team->pluck('employee_id')->toArray())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('Project/Show', [
            'project' => $project,
            'rootTasks' => $rootTasks,
            'documents' => $documents,
            'availableAssets' => $availableAssets,
            'availableEmployees' => $availableEmployees,
        ]);
    }

    public function edit(Project $project): Response
    {
        $project->load(['team.employee']);

        return Inertia::render('Project/Edit', [
            'project' => $project,
            'organizations' => Organization::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $validated = $request->validated();

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    public function updateProgress(UpdateProjectProgressRequest $request, Project $project)
    {
        $validated = $request->validated();

        $project->update($validated);

        return back()->with('success', 'Project progress updated.');
    }
}
