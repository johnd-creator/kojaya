<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Requests\StoreMedicalCheckupRequest;
use App\Http\Requests\UpdateMedicalCheckupRequest;
use App\Http\Requests\UploadEmployeeDocumentRequest;
use App\Http\Resources\MedicalCheckupResource;
use App\Models\DownloadLog;
use App\Models\Employee;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Security\EmployeeDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if ($mcu->document_path) {
            app(EmployeeDocumentStorage::class)->delete($mcu->document_path);
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

        $employee = $this->resolveEmployee($request, $employeeId);
        $mcu = $employee->medicalCheckups()->findOrFail($id);

        $storage = app(EmployeeDocumentStorage::class);
        $path = $storage->replace(
            $request->file('document'),
            EmployeeDocumentStorage::PREFIX_MCU,
            $employeeId,
            $mcu->document_path,
            function (string $newPath) use ($mcu) {
                $mcu->update(['document_path' => $newPath]);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => [
                'document_path' => $path,
                'has_document' => true,
                'document_download_url' => route('api.employees.mcu.document', [
                    'employeeId' => $employeeId,
                    'id' => $id,
                ]),
            ],
        ]);
    }

    public function downloadDocument(Request $request, string $employeeId, string $id): StreamedResponse|Response
    {
        $employee = $this->resolveEmployee($request, $employeeId);
        $mcu = $employee->medicalCheckups()->findOrFail($id);

        if (! $mcu->document_path) {
            abort(404, 'Medical checkup document not found.');
        }

        $ext = pathinfo($mcu->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $checkupDate = $mcu->checkup_date?->format('Y-m-d') ?? 'mcu';
        $filename = "mcu-{$employee->id}-{$checkupDate}.{$ext}";

        $this->logDownload($request, 'mcu', $mcu->id);

        return app(EmployeeDocumentStorage::class)->download($mcu->document_path, $filename);
    }

    protected function resolveEmployee(Request $request, string $employeeId): Employee
    {
        return app(OrganizationScopeService::class)
            ->scopeVisibleTo(Employee::query(), $request->user())
            ->findOrFail($employeeId);
    }

    protected function logDownload(Request $request, string $type, int|string $documentId): void
    {
        try {
            if ($request->user()) {
                DownloadLog::query()->create([
                    'user_id' => $request->user()->id,
                    'document_type' => $type,
                    'document_id' => (int) $documentId,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Throwable) {
        }
    }
}
