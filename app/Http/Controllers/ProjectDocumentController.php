<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectDocumentRequest;
use App\Http\Requests\UpdateProjectDocumentStatusRequest;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectDocumentController extends Controller
{
    public function index(Project $project): Response
    {
        $documents = $project->documents()->orderBy('created_at', 'desc')->get();

        return Inertia::render('ProjectDocuments/Index', [
            'project' => $project,
            'documents' => $documents,
        ]);
    }

    public function store(StoreProjectDocumentRequest $request, Project $project)
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('project-documents', 'public');
            $validated['file_path'] = $path;
        }

        $validated['project_id'] = $project->id;
        $validated['status'] = isset($validated['expiry_date']) ? 'VALID' : 'VALID';

        ProjectDocument::create($validated);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroy(ProjectDocument $document)
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function updateStatus(UpdateProjectDocumentStatusRequest $request, ProjectDocument $document)
    {
        $document->update($request->validated());

        return back()->with('success', 'Document status updated successfully.');
    }
}
