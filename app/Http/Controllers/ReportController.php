<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsolidatedAttendanceReportRequest;
use App\Http\Requests\ConsolidatedPayrollReportRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Reports\Attendance\MonthlyAttendanceReport;
use App\Reports\Compliance\CertificateComplianceReport;
use App\Reports\Compliance\McuComplianceReport;
use App\Reports\Leave\LeaveReport;
use App\Reports\Payroll\PayrollDetailReport;
use App\Reports\Payroll\PayrollSummaryReport;
use App\Reports\Payroll\PayslipReport;
use App\Services\ConsolidatedReportService;
use App\Services\ExcelExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ExcelExportService $excelExportService,
        private readonly ConsolidatedReportService $consolidatedReportService,
    ) {}

    public function page(): InertiaResponse
    {
        return Inertia::render('Reports', [
            'reports' => Inertia::defer(fn () => $this->reportCatalog(), 'reports'),
        ]);
    }

    public function index(): JsonResponse
    {
        return Response::json($this->reportCatalog());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reportCatalog(): array
    {
        return [
            [
                'id' => 'payslip',
                'name' => 'Payslip',
                'description' => 'Generate individual employee payslip PDF',
                'type' => 'pdf',
                'formats' => ['pdf'],
                'filters' => ['employee_id', 'period'],
                'category' => 'payroll',
            ],
            [
                'id' => 'payroll-summary',
                'name' => 'Payroll Summary',
                'description' => 'Consolidated payroll summary per organization',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['period', 'organization_id'],
                'category' => 'payroll',
            ],
            [
                'id' => 'payroll-detail',
                'name' => 'Payroll Detail',
                'description' => 'Detailed payroll breakdown with all components',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['period', 'organization_id'],
                'category' => 'payroll',
            ],
            [
                'id' => 'attendance',
                'name' => 'Attendance Report',
                'description' => 'Monthly attendance summary',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['date_from', 'date_to', 'organization_id', 'unit_id'],
                'category' => 'attendance',
            ],
            [
                'id' => 'leave',
                'name' => 'Leave Report',
                'description' => 'Leave summary and balance report',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['date_from', 'date_to', 'organization_id', 'unit_id'],
                'category' => 'leave',
            ],
            [
                'id' => 'certificate-compliance',
                'name' => 'Certificate Compliance',
                'description' => 'Certificate status and expiry tracking',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['date_from', 'date_to', 'organization_id'],
                'category' => 'compliance',
            ],
            [
                'id' => 'mcu-compliance',
                'name' => 'MCU Compliance',
                'description' => 'Medical check-up compliance status',
                'type' => 'excel',
                'formats' => ['excel'],
                'filters' => ['date_from', 'date_to', 'organization_id'],
                'category' => 'compliance',
            ],
        ];
    }

    public function payslip(Request $request, int $employeeId, string $period): HttpResponse
    {
        $employee = Employee::findOrFail($employeeId);
        $payroll = Payroll::where('employee_id', $employeeId)
            ->where('period', $period)
            ->firstOrFail();

        $report = new PayslipReport($employee, $payroll);
        $data = json_decode($report->generate(), true);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.payroll.payslip', $data)
            ->setPaper('A4')
            ->setOrientation('portrait')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
            ->stream();

        return Response::make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$report->getPdfFilename().'"',
        ]);
    }

    public function payrollSummary(Request $request): BinaryFileResponse
    {
        $filters = [
            'period_from' => $request->input('period_from'),
            'period_to' => $request->input('period_to'),
            'organization_id' => $request->input('organization_id'),
            'department' => $request->input('department'),
        ];

        $fileName = 'payroll_summary_'.now()->format('Y-m-d_His').'.xlsx';

        return $this->excelExportService
            ->export(new PayrollSummaryReport($filters), $fileName)
            ->download();
    }

    public function payrollDetail(Request $request): BinaryFileResponse
    {
        $filters = [
            'period' => $request->input('period'),
            'period_from' => $request->input('period_from'),
            'period_to' => $request->input('period_to'),
            'organization_id' => $request->input('organization_id'),
        ];

        $fileName = 'payroll_detail_'.now()->format('Y-m-d_His').'.xlsx';

        return $this->excelExportService
            ->export(new PayrollDetailReport($filters), $fileName)
            ->download();
    }

    public function attendanceReport(Request $request): BinaryFileResponse
    {
        $filters = [
            'month' => $request->input('month', now()->format('Y-m')),
            'organization_id' => $request->input('organization_id'),
            'department' => $request->input('department'),
        ];

        $fileName = 'attendance_report_'.$filters['month'].'.xlsx';

        return $this->excelExportService
            ->export(new MonthlyAttendanceReport($filters), $fileName)
            ->download();
    }

    public function leaveReport(Request $request): BinaryFileResponse
    {
        $filters = [
            'year' => $request->input('year', now()->year),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'organization_id' => $request->input('organization_id'),
        ];

        $fileName = 'leave_report_'.$filters['year'].'.xlsx';

        return $this->excelExportService
            ->export(new LeaveReport($filters), $fileName)
            ->download();
    }

    public function certificateCompliance(Request $request): BinaryFileResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'organization_id' => $request->input('organization_id'),
            'expiry_days' => $request->input('expiry_days', 90),
        ];

        $fileName = 'certificate_compliance_'.now()->format('Y-m-d_His').'.xlsx';

        return $this->excelExportService
            ->export(new CertificateComplianceReport($filters), $fileName)
            ->download();
    }

    public function mcuCompliance(Request $request): BinaryFileResponse
    {
        $filters = [
            'result' => $request->input('result'),
            'organization_id' => $request->input('organization_id'),
            'due_days' => $request->input('due_days', 30),
        ];

        $fileName = 'mcu_compliance_'.now()->format('Y-m-d_His').'.xlsx';

        return $this->excelExportService
            ->export(new McuComplianceReport($filters), $fileName)
            ->download();
    }

    /**
     * Get consolidated statistics across all organizations.
     */
    public function consolidatedStats(Request $request): JsonResponse
    {
        $stats = $this->consolidatedReportService->getEmployeeStats();

        return Response::json([
            'data' => $stats,
            'message' => 'Consolidated statistics retrieved successfully',
        ]);
    }

    /**
     * Get consolidated payroll summary across all organizations.
     */
    public function consolidatedPayroll(ConsolidatedPayrollReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payroll = $this->consolidatedReportService->getPayrollSummary(
            $validated['period_from'],
            $validated['period_to']
        );

        return Response::json([
            'data' => $payroll,
            'period_from' => $validated['period_from'],
            'period_to' => $validated['period_to'],
            'message' => 'Consolidated payroll summary retrieved successfully',
        ]);
    }

    /**
     * Get consolidated attendance statistics across all organizations.
     */
    public function consolidatedAttendance(ConsolidatedAttendanceReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendance = $this->consolidatedReportService->getAttendanceStats($validated['month']);

        return Response::json([
            'data' => $attendance,
            'message' => 'Consolidated attendance statistics retrieved successfully',
        ]);
    }
}
