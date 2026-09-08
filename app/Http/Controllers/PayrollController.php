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
use App\Services\Authorization\OrganizationScopeService;
use App\Services\BankExportService;
use App\Services\Hr\ThrEntitlementService;
use App\Services\PayrollGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollGenerationService $payrollGenerationService,
        private readonly BankExportService $bankExportService,
    ) {}

    public function index(Request $request, OrganizationScopeService $scopeService): Response
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::query()
            ->with(['employee', 'organization']);

        $query = $scopeService->scopeVisibleTo($query, $request->user(), 'view_payroll_all');

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->filled('organization_id')) {
            $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'), 'view_payroll_all');
            $query->where('organization_id', $targetOrgId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payrolls = $query->orderByDesc('period')
            ->orderBy('organization_id')
            ->paginate(20)
            ->withQueryString();

        $organizations = $request->user()->can('view_payroll_all')
            ? Organization::orderBy('name')->get()
            : Organization::where('id', $request->user()->organization_id)->orderBy('name')->get();

        $period = $request->input('period', now()->format('Y-m'));

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'organizations' => $organizations,
            'filters' => $request->only(['period', 'organization_id', 'status']),
            'stats' => Inertia::defer(function () use ($scopeService, $request, $period) {
                $statsQuery = $scopeService->scopeVisibleTo(Payroll::query(), $request->user(), 'view_payroll_all')
                    ->where('period', $period);

                if ($request->filled('organization_id')) {
                    $statsQuery->where('organization_id', $request->input('organization_id'));
                }

                return [
                    'total_net_salary' => (float) $statsQuery->sum('net_salary'),
                    'total_records' => $statsQuery->count(),
                    'current_period' => $period,
                ];
            }, 'payroll-stats'),
        ]);
    }

    public function show(Request $request, string $payroll, OrganizationScopeService $scopeService): Response
    {
        /** @var Payroll $payrollModel */
        $payrollModel = $scopeService->resolveVisible(Payroll::class, $request->user(), $payroll, 'view_payroll_all');

        $this->authorize('view', $payrollModel);

        return Inertia::render('Payroll/Show', [
            'payroll' => $payrollModel->load(['employee.organization', 'components']),
        ]);
    }

    public function generate(GeneratePayrollRequest $request, OrganizationScopeService $scopeService)
    {
        $validated = $request->validated();

        $targetOrgId = $scopeService->resolveTargetOrganization(
            $request->user(),
            $validated['organization_id'] ?? null,
            'view_payroll_all'
        );

        $result = $this->payrollGenerationService->generateForOrganization(
            $targetOrgId,
            $validated['period'],
        );

        return redirect()->route('payrolls.index', ['period' => $validated['period'], 'organization_id' => $targetOrgId])
            ->with('success', "Generated payroll for {$result['generated']} employees.");
    }

    public function downloadPdf(Request $request, string $payroll, OrganizationScopeService $scopeService)
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Payroll $payrollModel */
        $payrollModel = $scopeService->resolveVisible(Payroll::class, $user, $payroll, 'view_payroll_all');

        // Employees can only download their own paystub
        if ($user->can('view_own_payslip') && ! $user->can('view_payroll_all')) {
            $employee = $user->employee;
            if (! $employee || $employee->id !== $payrollModel->employee_id) {
                abort(403, 'Unauthorized. You may only download your own paystub.');
            }
        }

        $payrollModel->load(['employee.organization', 'organization', 'components']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.paystub', ['payroll' => $payrollModel]);

        $filename = 'Slip-Gaji-'.$payrollModel->period.'-'.str_replace(' ', '-', $payrollModel->employee->first_name).'.pdf';

        return $pdf->download($filename);
    }

    public function thrIndex(Request $request, OrganizationScopeService $scopeService): Response
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::query()
            ->with(['employee', 'organization'])
            ->where('is_thr', true);

        $query = $scopeService->scopeVisibleTo($query, $request->user(), 'view_payroll_all');

        if ($request->filled('year')) {
            $query->where('period', 'like', $request->input('year').'%');
        }

        if ($request->filled('organization_id')) {
            $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'), 'view_payroll_all');
            $query->where('organization_id', $targetOrgId);
        }

        $payrolls = $query->orderByDesc('period')
            ->orderBy('organization_id')
            ->paginate(20)
            ->withQueryString();

        $organizations = $request->user()->can('view_payroll_all')
            ? Organization::orderBy('name')->get()
            : Organization::where('id', $request->user()->organization_id)->orderBy('name')->get();

        $year = $request->input('year', now()->format('Y'));
        $statsQuery = $scopeService->scopeVisibleTo(Payroll::query(), $request->user(), 'view_payroll_all')
            ->where('is_thr', true)
            ->where('period', 'like', $year.'%');

        if ($request->filled('organization_id')) {
            $statsQuery->where('organization_id', $request->input('organization_id'));
        }

        $totalThr = (float) $statsQuery->sum('thr_amount');

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

    public function previewThr(PreviewThrRequest $request, ThrEntitlementService $thrEntitlementService, OrganizationScopeService $scopeService)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();
        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $validated['organization_id'] ?? null, 'view_payroll_all');

        $preview = $thrEntitlementService->previewOrganization($targetOrgId, (int) $validated['year']);

        $organization = Organization::find($targetOrgId);

        return response()->json([
            'total_employees' => $preview['total_employees'],
            'total_thr' => $preview['total_thr'],
            'organization_name' => $organization->name,
            'breakdown' => $preview['breakdown'],
        ]);
    }

    public function generateThr(PreviewThrRequest $request, ThrEntitlementService $thrEntitlementService, OrganizationScopeService $scopeService)
    {
        $this->authorize('create', Payroll::class);

        $validated = $request->validated();
        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $validated['organization_id'] ?? null, 'view_payroll_all');

        $thrPeriod = $validated['year'].'-05';

        $entitlements = $thrEntitlementService->calculateForOrganization($targetOrgId, (int) $validated['year']);

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

        return redirect()->route('payrolls.thr', ['year' => $validated['year'], 'organization_id' => $targetOrgId])
            ->with('success', "Generated THR for {$generated} employees.");
    }

    public function submitForApproval(SubmitPayrollApprovalRequest $request, OrganizationScopeService $scopeService)
    {
        $this->authorize('submitForApproval', Payroll::class);

        $validated = $request->validated();
        $user = $request->user();

        // 1. Resolve and validate all supplied payroll IDs before any persistent mutation
        $resolvedPayrolls = [];
        foreach ($validated['payroll_ids'] as $payrollId) {
            /** @var Payroll $payroll */
            $payroll = $scopeService->resolveVisible(Payroll::class, $user, $payrollId, 'view_payroll_all');
            $resolvedPayrolls[] = $payroll;
        }

        // 2. Enforce batch invariant: one payroll approval batch == one organization
        $orgIds = collect($resolvedPayrolls)->pluck('organization_id')->unique()->values();
        if ($orgIds->count() > 1) {
            throw ValidationException::withMessages([
                'payroll_ids' => 'Semua payroll dalam satu batch persetujuan harus berasal dari organisasi yang sama.',
            ]);
        }

        // 3. Mutation phase wrapped in database transaction as defense in depth
        $batchId = Str::uuid()->toString();

        DB::transaction(function () use ($resolvedPayrolls, $validated, $batchId) {
            foreach ($resolvedPayrolls as $payroll) {
                if ($payroll->status !== PayrollStatus::Draft->value) {
                    continue;
                }

                $hasPendingApproval = PayrollApproval::query()
                    ->where('payroll_id', $payroll->id)
                    ->where('status', PayrollApprovalStatus::Pending->value)
                    ->exists();

                if ($hasPendingApproval) {
                    continue;
                }

                PayrollApproval::create([
                    'payroll_id' => $payroll->id,
                    'payroll_batch_id' => $batchId,
                    'requester_id' => Auth::id(),
                    'status' => PayrollApprovalStatus::Pending->value,
                    'requester_notes' => $validated['notes'],
                    'requested_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Payroll submitted for approval.');
    }

    public function exportBankTransfer(ExportPayrollBankTransferRequest $request, string $batchId, OrganizationScopeService $scopeService)
    {
        $this->authorize('exportBankTransfer', Payroll::class);

        $validated = $request->validated();

        // 1. Resolve the complete approved batch server-side
        $payrolls = Payroll::query()->whereHas('approvals', function ($query) use ($batchId) {
            $query->where('payroll_batch_id', $batchId)
                ->where('status', PayrollApprovalStatus::Approved->value);
        })->with(['employee', 'organization'])->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'No approved payrolls found for this batch.');
        }

        // 2. Determine distinct underlying payroll organization IDs
        $orgIds = $payrolls->pluck('organization_id')->unique()->values();

        // 3. Fail closed if the complete batch contains more than one organization
        if ($orgIds->count() > 1) {
            return back()->with('error', 'Batch persetujuan payroll tidak valid karena mencakup beberapa organisasi.');
        }

        // 4. Authorize the actor against that single organization
        $batchOrgId = (string) $orgIds->first();
        $visibility = $scopeService->visibilityFor($request->user(), 'view_payroll_all');

        if (! $visibility->global && (string) $visibility->organizationId !== $batchOrgId) {
            return back()->with('error', 'Anda tidak memiliki akses ke batch payroll organisasi ini.');
        }

        // 5. Only then export the complete authorized batch
        $content = $this->bankExportService->exportPayrollToBank($batchId, $validated['bank'], $payrolls);

        $filename = 'payroll-export-'.$batchId.'-'.$validated['bank'].'.txt';

        return $this->bankExportService->downloadFile($content, $filename);
    }
}
