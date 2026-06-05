<?php

namespace App\Http\Controllers;

use App\Enums\PayrollStatus;
use App\Http\Requests\ApprovePayrollApprovalRequest;
use App\Http\Requests\RejectPayrollApprovalRequest;
use App\Models\PayrollApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PayrollApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PayrollApproval::query()
            ->with(['payroll.employee', 'payroll.organization', 'requester']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->where('organization_id', $request->input('organization_id'));
            });
        }

        $approvals = $query->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Payroll/Approval', [
            'approvals' => $approvals,
            'filters' => $request->only(['status', 'organization_id']),
            'stats' => [
                'pending_count' => PayrollApproval::pending()->count(),
                'approved_count' => PayrollApproval::approved()->count(),
                'rejected_count' => PayrollApproval::rejected()->count(),
            ],
        ]);
    }

    public function approve(ApprovePayrollApprovalRequest $request, PayrollApproval $approval)
    {
        $this->authorizePayrollApproval($request);

        $validated = $request->validated();

        $approval->approve(Auth::user(), $validated['notes']);

        $approval->payroll->update(['status' => PayrollStatus::Approved->value]);

        return back()->with('success', 'Payroll approved successfully.');
    }

    public function reject(RejectPayrollApprovalRequest $request, PayrollApproval $approval)
    {
        $this->authorizePayrollApproval($request);

        $validated = $request->validated();

        $approval->reject(Auth::user(), $validated['notes']);

        $approval->payroll->update(['status' => PayrollStatus::Draft->value]);

        return back()->with('success', 'Payroll rejected and returned to draft.');
    }

    private function authorizePayrollApproval(Request $request): void
    {
        abort_unless($request->user()?->can('approve_payroll'), 403);
    }
}
