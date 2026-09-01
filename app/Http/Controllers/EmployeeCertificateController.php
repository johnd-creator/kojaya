<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Requests\StoreEmployeeCertificateRequest;
use App\Http\Requests\UpdateEmployeeCertificateRequest;
use App\Http\Requests\UploadEmployeeDocumentRequest;
use App\Http\Resources\EmployeeCertificateResource;
use App\Models\Employee;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeCertificateController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request, string $employeeId)
    {
        $certificates = $this->resolveEmployee($request, $employeeId)
            ->certificates()
            ->orderBy('created_at', 'desc')
            ->paginate($this->apiPageSize($request));

        return EmployeeCertificateResource::collection($certificates);
    }

    public function store(StoreEmployeeCertificateRequest $request, string $employeeId)
    {
        $employee = $this->resolveEmployee($request, $employeeId);

        $certificate = $employee->certificates()->create($request->validated());

        return new EmployeeCertificateResource($certificate);
    }

    public function show(Request $request, string $employeeId, string $id)
    {
        $certificate = $this->resolveEmployee($request, $employeeId)
            ->certificates()
            ->findOrFail($id);

        return new EmployeeCertificateResource($certificate);
    }

    public function update(UpdateEmployeeCertificateRequest $request, string $employeeId, string $id)
    {
        $certificate = $this->resolveEmployee($request, $employeeId)
            ->certificates()
            ->findOrFail($id);

        $certificate->update($request->validated());

        return new EmployeeCertificateResource($certificate);
    }

    public function destroy(Request $request, string $employeeId, string $id): JsonResponse
    {
        $certificate = $this->resolveEmployee($request, $employeeId)
            ->certificates()
            ->findOrFail($id);

        // Delete document if exists
        if ($certificate->document_path) {
            Storage::disk('public')->delete($certificate->document_path);
        }

        $certificate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Certificate deleted successfully',
        ]);
    }

    public function uploadDocument(UploadEmployeeDocumentRequest $request, string $employeeId, string $id): JsonResponse
    {
        $request->validated();

        $certificate = $this->resolveEmployee($request, $employeeId)
            ->certificates()
            ->findOrFail($id);

        // Delete old document if exists
        if ($certificate->document_path) {
            Storage::disk('public')->delete($certificate->document_path);
        }

        $path = $request->file('document')->store('certificates/'.$employeeId, 'public');

        $certificate->update(['document_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => [
                'document_path' => $path,
                'document_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    protected function resolveEmployee(Request $request, string $employeeId): Employee
    {
        return app(OrganizationScopeService::class)
            ->scopeVisibleTo(Employee::query(), $request->user())
            ->findOrFail($employeeId);
    }
}
