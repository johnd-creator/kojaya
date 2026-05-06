<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportPayrollBankTransferRequest;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\PreviewThrRequest;
use App\Http\Requests\SubmitPayrollApprovalRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\PayrollComponent;
use App\Models\User;
use App\Services\BankExportService;
use App\Services\BpjsCalculationService;
use App\Services\OvertimeCalculationService;
use App\Services\Pph21TerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly Pph21TerService $pph21Service,
        private readonly BpjsCalculationService $bpjsService,
        private readonly OvertimeCalculationService $overtimeService,
        private readonly BankExportService $bankExportService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Payroll::query()
            ->with(['employee', 'organization']);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payrolls = $query->orderByDesc('period')
            ->orderBy('organization_id')
            ->paginate(20)
            ->withQueryString();

        $organizations = Organization::orderBy('name')->get();

        $period = $request->input('period', now()->format('Y-m'));

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'organizations' => $organizations,
            'filters' => $request->only(['period', 'organization_id', 'status']),
            'stats' => Inertia::defer(fn () => [
                'total_net_salary' => Payroll::where('period', $period)->sum('net_salary'),
                'total_records' => Payroll::where('period', $period)->count(),
                'current_period' => $period,
            ], 'payroll-stats'),
        ]);
    }

    public function show(Payroll $payroll): Response
    {
        return Inertia::render('Payroll/Show', [
            'payroll' => $payroll->load(['employee.organization', 'components']),
        ]);
    }

    public function generate(GeneratePayrollRequest $request)
    {
        $validated = $request->validated();

        $employees = Employee::where('organization_id', $validated['organization_id'])
            ->where('status', 'ACTIVE')
            ->get();

        $generated = 0;

        foreach ($employees as $employee) {
            if (Payroll::where('employee_id', $employee->id)->where('period', $validated['period'])->exists()) {
                continue;
            }

            $basicSalary = $employee->basic_salary ?? 0;

            $pph21Result = $this->pph21Service->calculate($employee, $basicSalary, 0);
            $bpjsResult = $this->bpjsService->calculate($basicSalary);

            $hourlyRate = $this->overtimeService->calculateHourlyRate($employee);
            $overtimeResult = $this->overtimeService->calculateTotalOvertimeForPeriod($employee->id, $validated['period'], $hourlyRate);

            $totalBpjsEmployee = $bpjsResult['total_employee_deduction'];
            $taxAmount = $pph21Result['monthly_tax'];
            $overtimeAmount = $overtimeResult['total_amount'];
            $totalAllowance = $overtimeAmount;
            $netSalary = $basicSalary + $totalAllowance - $totalBpjsEmployee - $taxAmount;

            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'organization_id' => $employee->organization_id,
                'period' => $validated['period'],
                'basic_salary' => $basicSalary,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalBpjsEmployee,
                'tax_amount' => $taxAmount,
                'bpjs_amount' => $totalBpjsEmployee,
                'net_salary' => $netSalary,
                'status' => 'DRAFT',
                'pph21_calculation_breakdown' => $pph21Result,
                'bpjs_kesehatan_amount' => $bpjsResult['bpjs_kesehatan']['employee'],
                'bpjs_jht_amount' => $bpjsResult['bpjs_jht']['employee'],
                'bpjs_jp_amount' => $bpjsResult['bpjs_jp']['employee'],
                'bpjs_jkk_amount' => $bpjsResult['bpjs_jkk']['amount'],
                'bpjs_jkm_amount' => $bpjsResult['bpjs_jkm']['amount'],
                'bpjs_calculation_breakdown' => $bpjsResult,
            ]);

            $components = [
                ['payroll_id' => $payroll->id, 'type' => 'EARNING', 'description' => 'Gaji Pokok', 'amount' => $basicSalary, 'created_at' => now(), 'updated_at' => now()],
            ];

            if ($overtimeAmount > 0) {
                $components[] = ['payroll_id' => $payroll->id, 'type' => 'EARNING', 'description' => 'Lembur ('.$overtimeResult['total_hours'].' jam)', 'amount' => $overtimeAmount, 'created_at' => now(), 'updated_at' => now()];
            }

            $components = array_merge($components, [
                ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'BPJS Kesehatan (1%)', 'amount' => -$bpjsResult['bpjs_kesehatan']['employee'], 'created_at' => now(), 'updated_at' => now()],
                ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'JHT (2%)', 'amount' => -$bpjsResult['bpjs_jht']['employee'], 'created_at' => now(), 'updated_at' => now()],
                ['payroll_id' => $payroll->id, 'type' => 'BPJS', 'description' => 'JP (1%)', 'amount' => -$bpjsResult['bpjs_jp']['employee'], 'created_at' => now(), 'updated_at' => now()],
                ['payroll_id' => $payroll->id, 'type' => 'TAX', 'description' => 'PPh 21 TER', 'amount' => -$taxAmount, 'created_at' => now(), 'updated_at' => now()],
            ]);

            PayrollComponent::insert($components);

            if ($overtimeAmount > 0) {
                foreach ($overtimeResult['breakdown'] as $otBreakdown) {
                    \App\Models\OvertimePayment::create([
                        'payroll_id' => $payroll->id,
                        'overtime_request_id' => $otBreakdown['request_id'] ?? null,
                        'hours' => $otBreakdown['hours'],
                        'hourly_rate' => $otBreakdown['hourly_rate'],
                        'multiplier' => $otBreakdown['multiplier'],
                        'amount' => $otBreakdown['amount'],
                    ]);
                }
            }

            $generated++;
        }

        return redirect()->route('payrolls.index', ['period' => $validated['period'], 'organization_id' => $validated['organization_id']])
            ->with('success', "Generated payroll for {$generated} employees.");
    }

    public function downloadPdf(Payroll $payroll)
    {
        /** @var User $user */
        $user = Auth::user();

        // Employees can only download their own paystub
        if ($user->can('view_own_payslip') && ! $user->can('view_payroll_all')) {
            $employee = $user->employee;
            if (! $employee || $employee->id !== $payroll->employee_id) {
                abort(403, 'Unauthorized. You may only download your own paystub.');
            }
        }

        $payroll->load(['employee.organization', 'organization', 'components']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.paystub', compact('payroll'));

        $filename = 'Slip-Gaji-'.$payroll->period.'-'.str_replace(' ', '-', $payroll->employee->first_name).'.pdf';

        return $pdf->download($filename);
    }

    public function thrIndex(Request $request): Response
    {
        $query = Payroll::query()
            ->with(['employee', 'organization'])
            ->where('is_thr', true);

        if ($request->filled('year')) {
            $query->where('period', 'like', $request->input('year').'%');
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        $payrolls = $query->orderByDesc('period')
            ->orderBy('organization_id')
            ->paginate(20)
            ->withQueryString();

        $organizations = Organization::orderBy('name')->get();

        $year = $request->input('year', now()->format('Y'));
        $totalThr = Payroll::where('is_thr', true)
            ->where('period', 'like', $year.'%')
            ->sum('thr_amount');

        return Inertia::render('Payroll/Thr', [
            'payrolls' => $payrolls,
            'organizations' => $organizations,
            'filters' => $request->only(['year', 'organization_id']),
            'stats' => [
                'total_thr' => $totalThr,
                'current_year' => $year,
            ],
        ]);
    }

    public function previewThr(PreviewThrRequest $request)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();

        $cutoffDate = \Carbon\Carbon::create($validated['year'], 5, 31);

        $employees = Employee::where('organization_id', $validated['organization_id'])
            ->where('status', 'ACTIVE')
            ->get();

        $totalThr = 0;
        $breakdown = [];
        $monthsWorkedCounts = [];

        foreach ($employees as $employee) {
            $hireDate = \Carbon\Carbon::parse($employee->hire_date);
            $monthsWorked = min(12, $hireDate->diffInMonths($cutoffDate) + 1);

            if (! isset($monthsWorkedCounts[$monthsWorked])) {
                $monthsWorkedCounts[$monthsWorked] = 0;
            }
            $monthsWorkedCounts[$monthsWorked]++;

            $basicSalary = $employee->basic_salary ?? 0;
            $thrAmount = ($basicSalary / 12) * $monthsWorked;
            $totalThr += $thrAmount;
        }

        ksort($monthsWorkedCounts);
        foreach ($monthsWorkedCounts as $months => $count) {
            $breakdown[] = [
                'months' => $months,
                'count' => $count,
            ];
        }

        $organization = Organization::find($validated['organization_id']);

        return response()->json([
            'total_employees' => $employees->count(),
            'total_thr' => $totalThr,
            'organization_name' => $organization->name,
            'breakdown' => $breakdown,
        ]);
    }

    public function generateThr(PreviewThrRequest $request)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();

        $thrPeriod = $validated['year'].'-05';

        $employees = Employee::where('organization_id', $validated['organization_id'])
            ->where('status', 'ACTIVE')
            ->get();

        $generated = 0;

        foreach ($employees as $employee) {
            if (Payroll::where('employee_id', $employee->id)
                ->where('is_thr', true)
                ->where('period', $thrPeriod)
                ->exists()) {
                continue;
            }

            $hireDate = \Carbon\Carbon::parse($employee->hire_date);
            $cutoffDate = \Carbon\Carbon::create($validated['year'], 5, 31);
            $monthsWorked = min(12, $hireDate->diffInMonths($cutoffDate) + 1);

            $basicSalary = $employee->basic_salary ?? 0;
            $thrAmount = ($basicSalary / 12) * $monthsWorked;

            Payroll::create([
                'employee_id' => $employee->id,
                'organization_id' => $employee->organization_id,
                'period' => $thrPeriod,
                'basic_salary' => 0,
                'total_allowance' => $thrAmount,
                'total_deduction' => 0,
                'tax_amount' => 0,
                'bpjs_amount' => 0,
                'net_salary' => $thrAmount,
                'status' => 'DRAFT',
                'is_thr' => true,
                'thr_proportion_months' => $monthsWorked,
                'thr_amount' => $thrAmount,
                'thr_calculation_breakdown' => json_encode([
                    'basic_salary' => $basicSalary,
                    'months_worked' => $monthsWorked,
                    'thr_calculation' => "({$basicSalary} / 12) * {$monthsWorked}",
                ]),
            ]);

            $generated++;
        }

        return redirect()->route('payrolls.thr', ['year' => $validated['year'], 'organization_id' => $validated['organization_id']])
            ->with('success', "Generated THR for {$generated} employees.");
    }

    public function submitForApproval(SubmitPayrollApprovalRequest $request)
    {
        $this->authorize('submitForApproval', Payroll::class);

        $validated = $request->validated();

        $batchId = Str::uuid()->toString();

        foreach ($validated['payroll_ids'] as $payrollId) {
            $payroll = Payroll::find($payrollId);

            if (! $payroll || $payroll->status !== 'DRAFT') {
                continue;
            }

            $hasPendingApproval = PayrollApproval::query()
                ->where('payroll_id', $payrollId)
                ->where('status', 'PENDING')
                ->exists();

            if ($hasPendingApproval) {
                continue;
            }

            PayrollApproval::create([
                'payroll_id' => $payrollId,
                'payroll_batch_id' => $batchId,
                'requester_id' => Auth::id(),
                'status' => 'PENDING',
                'requester_notes' => $validated['notes'],
                'requested_at' => now(),
            ]);
        }

        return back()->with('success', 'Payroll submitted for approval.');
    }

    public function exportBankTransfer(ExportPayrollBankTransferRequest $request, string $batchId)
    {
        $this->authorize('exportBankTransfer', Payroll::class);

        $validated = $request->validated();

        $payrolls = Payroll::whereHas('approval', function ($query) use ($batchId) {
            $query->where('payroll_batch_id', $batchId)
                ->where('status', 'APPROVED');
        })->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'No approved payrolls found for this batch.');
        }

        $content = $this->bankExportService->exportPayrollToBank($batchId, $validated['bank']);

        $filename = 'payroll-export-'.$batchId.'-'.$validated['bank'].'.txt';

        return $this->bankExportService->downloadFile($content, $filename);
    }
}
