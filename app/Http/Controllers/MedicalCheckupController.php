<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Requests\StoreMedicalCheckupRequest;
use App\Http\Requests\UpdateMedicalCheckupRequest;
use App\Http\Requests\UploadEmployeeDocumentRequest;
use App\Http\Resources\MedicalCheckupResource;
use App\Models\Employee;
use App\Models\MedicalCheckup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicalCheckupController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request, string $employeeId)
    {
        $mcuRecords = Employee::findOrFail($employeeId)
            ->medicalCheckups()
            ->orderBy('checkup_date', 'desc')
            ->paginate($this->apiPageSize($request));

        return MedicalCheckupResource::collection($mcuRecords);
    }

    public function store(StoreMedicalCheckupRequest $request, string $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $mcu = $employee->medicalCheckups()->create($request->validated());

        return new MedicalCheckupResource($mcu);
    }

    public function show(string $employeeId, string $id)
    {
        $mcu = MedicalCheckup::where('employee_id', $employeeId)
            ->findOrFail($id);

        return new MedicalCheckupResource($mcu);
    }

    public function update(UpdateMedicalCheckupRequest $request, string $employeeId, string $id)
    {
        $mcu = MedicalCheckup::where('employee_id', $employeeId)
            ->findOrFail($id);

        $mcu->update($request->validated());

        return new MedicalCheckupResource($mcu);
    }

    public function destroy(string $employeeId, string $id): JsonResponse
    {
        $mcu = MedicalCheckup::where('employee_id', $employeeId)
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

        $mcu = MedicalCheckup::where('employee_id', $employeeId)
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
}
