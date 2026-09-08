<?php

namespace App\Http\Controllers;

use App\Enums\PayrollStatus;
use App\Http\Requests\ApprovePayrollApprovalRequest;
use App\Http\Requests\RejectPayrollApprovalRequest;
use App\Models\PayrollApproval;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PayrollApprovalController extends Controller
{
    public function index(Request $request, OrganizationScopeService $scopeService): Response
    {
        $this->authorize('viewAny', PayrollApproval::class);

        $query = PayrollApproval::query()
            ->with(['payroll.employee', 'payroll.organization', 'requester']);

        $query = $scopeService->scopeVisibleTo($query, $request->user(), 'view_payroll_all');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'), 'view_payroll_all');
            $query->whereHas('payroll', function ($q) use ($targetOrgId) {
                $q->where('organization_id', $targetOrgId);
            });
        }

        $approvals = $query->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = $scopeService->scopeVisibleTo(PayrollApproval::query(), $request->user(), 'view_payroll_all');
        if ($request->filled('organization_id')) {
            $statsQuery->whereHas('payroll', function ($q) use ($request) {
                $q->where('organization_id', $request->input('organization_id'));
            });
        }

        return Inertia::render('Payroll/Approval', [
            'approvals' => $approvals,
            'filters' => $request->only(['status', 'organization_id']),
            'stats' => [
                'pending_count' => (clone $statsQuery)->pending()->count(),
                'approved_count' => (clone $statsQuery)->approved()->count(),
                'rejected_count' => (clone $statsQuery)->rejected()->count(),
            ],
        ]);
    }

    public function approve(ApprovePayrollApprovalRequest $request, string $approval, OrganizationScopeService $scopeService)
    {
        /** @var PayrollApproval $approvalModel */
        $approvalModel = $scopeService->resolveVisible(PayrollApproval::class, $request->user(), $approval, 'view_payroll_all');

        $this->authorize('approve', $approvalModel);

        $validated = $request->validated();

        $approvalModel->approve(Auth::user(), $validated['notes']);

        $approvalModel->payroll->update(['status' => PayrollStatus::Approved->value]);

        return back()->with('success', 'Payroll approved successfully.');
    }

    public function reject(RejectPayrollApprovalRequest $request, string $approval, OrganizationScopeService $scopeService)
    {
        /** @var PayrollApproval $approvalModel */
        $approvalModel = $scopeService->resolveVisible(PayrollApproval::class, $request->user(), $approval, 'view_payroll_all');

        $this->authorize('reject', $approvalModel);

        $validated = $request->validated();

        $approvalModel->reject(Auth::user(), $validated['notes']);

        $approvalModel->payroll->update(['status' => PayrollStatus::Draft->value]);

        return back()->with('success', 'Payroll rejected and returned to draft.');
    }
}
