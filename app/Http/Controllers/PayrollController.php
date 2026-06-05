<?php

namespace App\Http\Controllers;

use App\Enums\PayrollApprovalStatus;
use App\Enums\PayrollStatus;
use App\Http\Requests\ExportPayrollBankTransferRequest;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\PreviewThrRequest;
use App\Http\Requests\SubmitPayrollApprovalRequest;
use App\Models\Organization;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\User;
use App\Services\BankExportService;
use App\Services\Hr\ThrEntitlementService;
use App\Services\PayrollGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollGenerationService $payrollGenerationService,
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

        $result = $this->payrollGenerationService->generateForOrganization(
            $validated['organization_id'],
            $validated['period'],
        );

        return redirect()->route('payrolls.index', ['period' => $validated['period'], 'organization_id' => $validated['organization_id']])
            ->with('success', "Generated payroll for {$result['generated']} employees.");
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

    public function previewThr(PreviewThrRequest $request, ThrEntitlementService $thrEntitlementService)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();

        $preview = $thrEntitlementService->previewOrganization($validated['organization_id'], (int) $validated['year']);

        $organization = Organization::find($validated['organization_id']);

        return response()->json([
            'total_employees' => $preview['total_employees'],
            'total_thr' => $preview['total_thr'],
            'organization_name' => $organization->name,
            'breakdown' => $preview['breakdown'],
        ]);
    }

    public function generateThr(PreviewThrRequest $request, ThrEntitlementService $thrEntitlementService)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();

        $thrPeriod = $validated['year'].'-05';

        $entitlements = $thrEntitlementService->calculateForOrganization($validated['organization_id'], (int) $validated['year']);

        $generated = 0;

        foreach ($entitlements as $entitlement) {
            if (Payroll::where('employee_id', $entitlement->employee_id)
                ->where('is_thr', true)
                ->where('period', $thrPeriod)
                ->exists()) {
                continue;
            }

            $thrEntitlementService->createPayrollFromEntitlement($entitlement, $thrPeriod);

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

            if (! $payroll || $payroll->status !== PayrollStatus::Draft->value) {
                continue;
            }

            $hasPendingApproval = PayrollApproval::query()
                ->where('payroll_id', $payrollId)
                ->where('status', PayrollApprovalStatus::Pending->value)
                ->exists();

            if ($hasPendingApproval) {
                continue;
            }

            PayrollApproval::create([
                'payroll_id' => $payrollId,
                'payroll_batch_id' => $batchId,
                'requester_id' => Auth::id(),
                'status' => PayrollApprovalStatus::Pending->value,
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
                ->where('status', PayrollApprovalStatus::Approved->value);
        })->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'No approved payrolls found for this batch.');
        }

        $content = $this->bankExportService->exportPayrollToBank($batchId, $validated['bank']);

        $filename = 'payroll-export-'.$batchId.'-'.$validated['bank'].'.txt';

        return $this->bankExportService->downloadFile($content, $filename);
    }
}
