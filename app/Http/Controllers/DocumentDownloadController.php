<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.paystub', compact('payroll'));

        return $pdf->download("payslip-{$payroll->period}.pdf");
    }

    public function medicalCheckup(Request $request, MedicalCheckup $mcu): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if (! $request->user()->can('view_employee_all')
            && ! $request->user()->can('view_employee_unit')
        ) {
            abort(403);
        }

        if (! $mcu->document_path) {
            abort(404, 'Dokumen medical checkup tidak tersedia.');
        }

        $this->logDownload($request, 'mcu', $mcu->id);

        return Storage::disk('public')->download(
            $mcu->document_path,
            "mcu-{$mcu->employee_id}-{$mcu->checkup_date}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function certificate(Request $request, Employee $employee, EmployeeCertificate $certificate): mixed
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link download tidak valid atau sudah kadaluarsa.');
        }

        if ($certificate->employee_id !== $employee->id) {
            abort(404);
        }

        if (! $request->user()->can('view_employee_all')
            && ! $request->user()->can('view_employee_unit')
        ) {
            abort(403);
        }

        if (! $certificate->document_path) {
            abort(404, 'Dokumen sertifikat tidak tersedia.');
        }

        $this->logDownload($request, 'certificate', $certificate->id);

        return Storage::disk('public')->download(
            $certificate->document_path,
            "cert-{$certificate->certificate_type}-{$employee->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
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
