<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectDocumentRequest;
use App\Http\Requests\UpdateProjectDocumentStatusRequest;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('view', $project);

        $documents = $project->documents()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ProjectDocument $doc) => [
                'id' => $doc->id,
                'project_id' => $doc->project_id,
                'name' => $doc->name,
                'type' => $doc->type,
                'status' => $doc->status,
                'expiry_date' => $doc->expiry_date?->format('Y-m-d') ?? $doc->expiry_date,
                'download_url' => route('projects.documents.download', [$project, $doc]),
                'created_at' => $doc->created_at,
                'updated_at' => $doc->updated_at,
            ]);

        return Inertia::render('ProjectDocuments/Index', [
            'project' => $project,
            'documents' => $documents,
        ]);
    }

    public function store(StoreProjectDocumentRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $path = $request->file('file')->store('project-documents', 'project_documents');

        try {
            ProjectDocument::create([
                'project_id' => $project->id,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'file_path' => $path,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'status' => 'VALID',
            ]);
        } catch (\Throwable $e) {
            if ($path && Storage::disk('project_documents')->exists($path)) {
                Storage::disk('project_documents')->delete($path);
            }

            throw $e;
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(Project $project, ProjectDocument $document): StreamedResponse|SymfonyResponse
    {
        $this->authorize('view', $project);

        if ($document->project_id !== $project->id) {
            abort(404, 'Project document not found.');
        }

        $path = $document->file_path;
        if (! $path || ! is_string($path)) {
            abort(404, 'Document file not found.');
        }

        if (
            str_contains($path, '..') ||
            str_contains($path, '\\') ||
            str_starts_with($path, '/') ||
            ! str_starts_with($path, 'project-documents/')
        ) {
            abort(404, 'Document file not found.');
        }

        $disk = null;
        if (Storage::disk('project_documents')->exists($path)) {
            $disk = 'project_documents';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if (! $disk) {
            abort(404, 'Document file not found.');
        }

        $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $document->name);
        if ($extension && ! str_ends_with(strtolower($safeName), '.'.strtolower($extension))) {
            $safeName .= '.'.$extension;
        }

        return Storage::disk($disk)->download($path, $safeName, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function destroy(Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->authorize('update', $project);

        if ($document->project_id !== $project->id) {
            abort(404, 'Project document not found.');
        }

        $filePath = $document->file_path;

        if ($filePath && is_string($filePath)) {
            if (Storage::disk('project_documents')->exists($filePath)) {
                Storage::disk('project_documents')->delete($filePath);
            }
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function updateStatus(UpdateProjectDocumentStatusRequest $request, Project $project, ProjectDocument $document): RedirectResponse
    {
        $this->authorize('update', $project);

        if ($document->project_id !== $project->id) {
            abort(404, 'Project document not found.');
        }

        $document->update($request->validated());

        return back()->with('success', 'Document status updated successfully.');
    }
}
