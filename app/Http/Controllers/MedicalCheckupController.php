<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Requests\StoreMedicalCheckupRequest;
use App\Http\Requests\UpdateMedicalCheckupRequest;
use App\Http\Requests\UploadEmployeeDocumentRequest;
use App\Http\Resources\MedicalCheckupResource;
use App\Models\Employee;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicalCheckupController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request, string $employeeId)
    {
        $mcuRecords = $this->resolveEmployee($request, $employeeId)
            ->medicalCheckups()
            ->orderBy('checkup_date', 'desc')
            ->paginate($this->apiPageSize($request));

        return MedicalCheckupResource::collection($mcuRecords);
    }

    public function store(StoreMedicalCheckupRequest $request, string $employeeId)
    {
        $employee = $this->resolveEmployee($request, $employeeId);

        $mcu = $employee->medicalCheckups()->create($request->validated());

        return new MedicalCheckupResource($mcu);
    }

    public function show(Request $request, string $employeeId, string $id)
    {
        $mcu = $this->resolveEmployee($request, $employeeId)
            ->medicalCheckups()
            ->findOrFail($id);

        return new MedicalCheckupResource($mcu);
    }

    public function update(UpdateMedicalCheckupRequest $request, string $employeeId, string $id)
    {
        $mcu = $this->resolveEmployee($request, $employeeId)
            ->medicalCheckups()
            ->findOrFail($id);

        $mcu->update($request->validated());

        return new MedicalCheckupResource($mcu);
    }

    public function destroy(Request $request, string $employeeId, string $id): JsonResponse
    {
        $mcu = $this->resolveEmployee($request, $employeeId)
            ->medicalCheckups()
            ->findOrFail($id);

        // Delete document if exists
        if ($mcu->document_path) {
            Storage::disk('public')->delete($mcu->document_path);
        }

        $mcu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medical checkup record deleted successfully',
        ]);
    }

    public function uploadDocument(UploadEmployeeDocumentRequest $request, string $employeeId, string $id): JsonResponse
    {
        $request->validated();

        $mcu = $this->resolveEmployee($request, $employeeId)
            ->medicalCheckups()
            ->findOrFail($id);

        // Delete old document if exists
        if ($mcu->document_path) {
            Storage::disk('public')->delete($mcu->document_path);
        }

        $path = $request->file('document')->store('mcu/'.$employeeId, 'public');

        $mcu->update(['document_path' => $path]);

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
