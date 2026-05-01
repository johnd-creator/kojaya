<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeCertificateRequest;
use App\Http\Requests\UpdateEmployeeCertificateRequest;
use App\Http\Resources\EmployeeCertificateResource;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeCertificateController extends Controller
{
    public function index(Request $request, string $employeeId)
    {
        $certificates = Employee::findOrFail($employeeId)
            ->certificates()
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return EmployeeCertificateResource::collection($certificates);
    }

    public function store(StoreEmployeeCertificateRequest $request, string $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $certificate = $employee->certificates()->create($request->validated());

        return new EmployeeCertificateResource($certificate);
    }

    public function show(string $employeeId, string $id)
    {
        $certificate = EmployeeCertificate::where('employee_id', $employeeId)
            ->findOrFail($id);

        return new EmployeeCertificateResource($certificate);
    }

    public function update(UpdateEmployeeCertificateRequest $request, string $employeeId, string $id)
    {
        $certificate = EmployeeCertificate::where('employee_id', $employeeId)
            ->findOrFail($id);

        $certificate->update($request->validated());

        return new EmployeeCertificateResource($certificate);
    }

    public function destroy(string $employeeId, string $id): JsonResponse
    {
        $certificate = EmployeeCertificate::where('employee_id', $employeeId)
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

    public function uploadDocument(Request $request, string $employeeId, string $id): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // 2MB max
        ]);

        $certificate = EmployeeCertificate::where('employee_id', $employeeId)
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
}
