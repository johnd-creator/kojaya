<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:SIKA,PERMIT,DRAWING,OTHER',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('project-documents', 'public');
            $validated['file_path'] = $path;
        }

        $validated['id'] = Str::uuid();
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

    public function updateStatus(Request $request, ProjectDocument $document)
    {
        $validated = $request->validate([
            'status' => 'required|in:VALID,EXPIRED',
        ]);

        $document->update($validated);

        return back()->with('success', 'Document status updated successfully.');
    }
}
