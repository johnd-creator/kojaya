<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Requests\StoreEmployeeCertificateRequest;
use App\Http\Requests\UpdateEmployeeCertificateRequest;
use App\Http\Requests\UploadEmployeeDocumentRequest;
use App\Http\Resources\EmployeeCertificateResource;
use App\Models\DownloadLog;
use App\Models\Employee;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Security\EmployeeDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        $employee = $this->resolveEmployee($request, $employeeId);
        $certificate = $employee->certificates()->findOrFail($id);

        if ($certificate->document_path) {
            try {
                app(EmployeeDocumentStorage::class)->delete(
                    $certificate->document_path,
                    EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                    $employeeId
                );
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document path or ownership mismatch.',
                ], 400);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to securely delete document file. Please retry.',
                ], 500);
            }
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

        $employee = $this->resolveEmployee($request, $employeeId);
        $certificate = $employee->certificates()->findOrFail($id);

        $storage = app(EmployeeDocumentStorage::class);
        $path = $storage->replace(
            $request->file('document'),
            EmployeeDocumentStorage::PREFIX_CERTIFICATES,
            $employeeId,
            $certificate->document_path,
            function (string $newPath) use ($certificate) {
                $certificate->update(['document_path' => $newPath]);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => [
                'document_path' => $path,
                'has_document' => true,
                'document_download_url' => route('api.employees.certificates.document', [
                    'employeeId' => $employeeId,
                    'id' => $id,
                ]),
            ],
        ]);
    }

    public function downloadDocument(Request $request, string $employeeId, string $id): StreamedResponse|Response
    {
        $employee = $this->resolveEmployee($request, $employeeId);
        $certificate = $employee->certificates()->findOrFail($id);

        if (! $certificate->document_path) {
            abort(404, 'Certificate document not found.');
        }

        $storage = app(EmployeeDocumentStorage::class);

        try {
            $storage->validateOwnedPath(
                $certificate->document_path,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $employeeId
            );
        } catch (InvalidArgumentException) {
            abort(404, 'Certificate document not found.');
        }

        if (! $storage->exists($certificate->document_path, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $employeeId)) {
            abort(404, 'Certificate document not found.');
        }

        $ext = pathinfo($certificate->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $type = $certificate->certificate_type?->value ?? 'certificate';
        $filename = "cert-{$type}-{$employee->id}.{$ext}";

        $response = $storage->download(
            $certificate->document_path,
            $filename,
            EmployeeDocumentStorage::PREFIX_CERTIFICATES,
            $employeeId
        );

        $this->logDownload($request, 'certificate', $certificate->id);

        return $response;
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
        } catch (Throwable) {
        }
    }
}
