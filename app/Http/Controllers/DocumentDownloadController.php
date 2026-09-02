<?php

namespace App\Http\Controllers;

use App\Models\CooperativeReceipt;
use App\Models\DownloadLog;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Security\EmployeeDocumentStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DocumentDownloadController extends Controller
{
    public function payslip(Request $request, string $id): mixed
    {
        $payroll = \App\Models\Payroll::findOrFail($id);

        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if (! $request->user()->can('view_own_payslip')
            && ! $request->user()->can('view_payroll_all')
            && ! $request->user()->can('view_payroll_unit')
        ) {
            abort(403, 'Anda tidak memiliki izin mengunduh payslip ini.');
        }

        if ($request->user()->hasPermissionTo('view_own_payslip')
            && ! $request->user()->hasAnyPermission(['view_payroll_all', 'view_payroll_unit'])
        ) {
            $employee = Employee::query()->where('user_id', $request->user()->id)->first();
            if (! $employee || $payroll->employee_id !== $employee->id) {
                abort(403, 'Anda hanya bisa mengunduh payslip sendiri.');
            }
        }

        $this->logDownload($request, 'payslip', $payroll->id);

        $pdf = Pdf::loadView('payroll.paystub', compact('payroll'));

        return $pdf->download("payslip-{$payroll->period}.pdf");
    }

    public function medicalCheckup(Request $request, MedicalCheckup $mcu): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if (! $request->user()?->can('view_employee_all')
            && ! $request->user()?->can('view_employee_unit')
        ) {
            abort(403);
        }

        $employee = app(OrganizationScopeService::class)
            ->scopeVisibleTo(Employee::query(), $request->user())
            ->where('id', $mcu->employee_id)
            ->first();

        if (! $employee || (int) $mcu->employee_id !== (int) $employee->id) {
            abort(404);
        }

        if (! $mcu->document_path) {
            abort(404, 'Dokumen medical checkup tidak tersedia.');
        }

        $storage = app(EmployeeDocumentStorage::class);

        try {
            $storage->validateOwnedPath(
                $mcu->document_path,
                EmployeeDocumentStorage::PREFIX_MCU,
                $employee->id
            );
        } catch (InvalidArgumentException) {
            abort(404, 'Dokumen medical checkup tidak tersedia.');
        }

        if (! $storage->exists($mcu->document_path, EmployeeDocumentStorage::PREFIX_MCU, $employee->id)) {
            abort(404, 'Dokumen medical checkup tidak tersedia.');
        }

        $ext = pathinfo($mcu->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $checkupDate = $mcu->checkup_date?->format('Y-m-d') ?? 'mcu';
        $filename = "mcu-{$mcu->employee_id}-{$checkupDate}.{$ext}";

        $response = $storage->download(
            $mcu->document_path,
            $filename,
            EmployeeDocumentStorage::PREFIX_MCU,
            $employee->id
        );

        $this->logDownload($request, 'mcu', $mcu->id);

        return $response;
    }

    public function certificate(Request $request, Employee $employee, EmployeeCertificate $certificate): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if (! $request->user()?->can('view_employee_all')
            && ! $request->user()?->can('view_employee_unit')
        ) {
            abort(403);
        }

        $scopedEmployee = app(OrganizationScopeService::class)
            ->scopeVisibleTo(Employee::query(), $request->user())
            ->where('id', $employee->id)
            ->first();

        if (! $scopedEmployee || (int) $certificate->employee_id !== (int) $scopedEmployee->id) {
            abort(404);
        }

        if (! $certificate->document_path) {
            abort(404, 'Dokumen sertifikat tidak tersedia.');
        }

        $storage = app(EmployeeDocumentStorage::class);

        try {
            $storage->validateOwnedPath(
                $certificate->document_path,
                EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                $scopedEmployee->id
            );
        } catch (InvalidArgumentException) {
            abort(404, 'Dokumen sertifikat tidak tersedia.');
        }

        if (! $storage->exists($certificate->document_path, EmployeeDocumentStorage::PREFIX_CERTIFICATES, $scopedEmployee->id)) {
            abort(404, 'Dokumen sertifikat tidak tersedia.');
        }

        $ext = pathinfo($certificate->document_path, PATHINFO_EXTENSION) ?: 'pdf';
        $type = $certificate->certificate_type?->value ?? 'certificate';
        $filename = "cert-{$type}-{$scopedEmployee->id}.{$ext}";

        $response = $storage->download(
            $certificate->document_path,
            $filename,
            EmployeeDocumentStorage::PREFIX_CERTIFICATES,
            $scopedEmployee->id
        );

        $this->logDownload($request, 'certificate', $certificate->id);

        return $response;
    }

    public function kyc(Request $request, string $memberId, string $documentId): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if (! $request->user()->can('view_cooperative_member')) {
            abort(403);
        }

        $doc = \App\Models\CooperativeMemberDocument::query()
            ->where('id', $documentId)
            ->where('cooperative_member_id', $memberId)
            ->firstOrFail();

        if (! $doc->file_path) {
            abort(404, 'Dokumen KYC tidak tersedia.');
        }

        $this->logDownload($request, 'kyc', $doc->id);

        return Storage::disk('public')->download(
            $doc->file_path,
            "kyc-{$memberId}.".($doc->file_type ?? 'pdf'),
            ['Content-Type' => 'application/octet-stream']
        );
    }

    public function cooperativeReceipt(Request $request, CooperativeReceipt $receipt): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        $receipt->load('member');

        if ($request->user() && $receipt->member?->user_id && (int) $receipt->member->user_id !== (int) $request->user()->id) {
            abort_unless($request->user()?->can('manage_cooperative_payment'), 403);
        }

        if (! $receipt->pdf_path || ! Storage::disk('local')->exists($receipt->pdf_path)) {
            abort(404, 'Receipt pembayaran tidak tersedia.');
        }

        $this->logDownload($request, 'cooperative-receipt', $receipt->id);

        return Storage::disk('local')->download(
            $receipt->pdf_path,
            $receipt->receipt_no.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    private function logDownload(Request $request, string $type, int $documentId): void
    {
        try {
            DownloadLog::query()->create([
                'user_id' => $request->user()->id,
                'document_type' => $type,
                'document_id' => $documentId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable) {
        }
    }
}
