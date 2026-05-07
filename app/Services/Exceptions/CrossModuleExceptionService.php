<?php

namespace App\Services\Exceptions;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\PayrollApproval;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Reimbursement;

class CrossModuleExceptionService
{
    public function allModules(?string $period = null): array
    {
        $period ??= today()->format('Y-m');

        return [
            'cooperative' => $this->cooperativeExceptions($period),
            'finance' => $this->financeExceptions($period),
            'procurement' => $this->procurementExceptions($period),
            'hr' => $this->hrExceptions($period),
            'summary' => $this->summary($period),
        ];
    }

    public function cooperativeExceptions(string $period): array
    {
        return [
            'overdue_loans' => LoanInstallment::query()
                ->where('status', 'OVERDUE')
                ->where('due_date', '<', today())
                ->with(['loan.cooperativeMember.user'])
                ->limit(20)
                ->get(),

            'unpaid_dues' => CooperativeDuesInvoice::query()
                ->where('status', 'UNPAID')
                ->where('due_date', '<', today())
                ->with(['cooperativeMember.user'])
                ->limit(20)
                ->get(),

            'pending_payments' => CooperativePayment::query()
                ->where('status', 'PENDING')
                ->with(['cooperativeMember.user'])
                ->limit(20)
                ->get(),

            'pending_loans' => Loan::query()
                ->where('status', 'APPLIED')
                ->with(['cooperativeMember.user'])
                ->limit(20)
                ->get(),
        ];
    }

    public function financeExceptions(string $period): array
    {
        $now = now();

        return [
            'pending_reimbursements' => Reimbursement::query()
                ->whereIn('status', ['SUBMITTED'])
                ->where('created_at', '<', $now->copy()->subDays(7))
                ->with(['employee.user'])
                ->limit(20)
                ->get(),

            'pending_payroll_approvals' => PayrollApproval::query()
                ->where('status', 'PENDING')
                ->where('created_at', '<', $now->copy()->subDays(3))
                ->with(['requester', 'payroll'])
                ->limit(20)
                ->get(),

            'unreconciled_payments' => CooperativePayment::query()
                ->where('status', 'APPROVED')
                ->where('approved_at', '<', $now->copy()->subDays(7))
                ->with(['cooperativeMember.user'])
                ->limit(20)
                ->get(),
        ];
    }

    public function procurementExceptions(string $period): array
    {
        return [
            'pr_without_po' => PurchaseRequest::query()
                ->where('status', 'APPROVED')
                ->whereDoesntHave('purchaseOrders')
                ->where('updated_at', '<', now()->subDays(14))
                ->with(['requester'])
                ->limit(20)
                ->get(),

            'po_overdue' => PurchaseOrder::query()
                ->whereNotIn('status', ['RECEIVED', 'CANCELLED'])
                ->where('issued_at', '<', today())
                ->with(['vendor'])
                ->limit(20)
                ->get(),

            'pr_pending_approval' => PurchaseRequest::query()
                ->where('status', 'SUBMITTED')
                ->where('updated_at', '<', now()->subDays(7))
                ->with(['requester'])
                ->limit(20)
                ->get(),
        ];
    }

    public function hrExceptions(string $period): array
    {
        return [
            'pending_leaves' => \App\Models\Leave::query()
                ->whereIn('status', ['PENDING', 'Pending'])
                ->where('created_at', '<', now()->subDays(3))
                ->with(['employee.user'])
                ->limit(20)
                ->get(),

            'pending_overtimes' => \App\Models\OvertimeRequest::query()
                ->where('status', 'PENDING')
                ->where('created_at', '<', now()->subDays(3))
                ->with(['employee.user'])
                ->limit(20)
                ->get(),
        ];
    }

    public function summary(string $period): array
    {
        return [
            'cooperative' => [
                'overdue_loan_count' => LoanInstallment::query()->where('status', 'OVERDUE')->where('due_date', '<', today())->count(),
                'unpaid_dues_count' => CooperativeDuesInvoice::query()->where('status', 'UNPAID')->where('due_date', '<', today())->count(),
                'pending_payment_count' => CooperativePayment::query()->where('status', 'PENDING')->count(),
                'pending_loan_count' => Loan::query()->where('status', 'APPLIED')->count(),
            ],
            'finance' => [
                'pending_reimbursement_count' => Reimbursement::query()->where('status', 'SUBMITTED')->where('created_at', '<', now()->subDays(7))->count(),
                'pending_payroll_approval_count' => PayrollApproval::query()->where('status', 'PENDING')->where('created_at', '<', now()->subDays(3))->count(),
                'unreconciled_payment_count' => CooperativePayment::query()->where('status', 'APPROVED')->where('approved_at', '<', now()->subDays(7))->count(),
            ],
            'procurement' => [
                'pr_without_po_count' => PurchaseRequest::query()->where('status', 'APPROVED')->whereDoesntHave('purchaseOrders')->where('updated_at', '<', now()->subDays(14))->count(),
                'po_overdue_count' => PurchaseOrder::query()->whereNotIn('status', ['RECEIVED', 'CANCELLED'])->where('issued_at', '<', today())->count(),
                'pr_pending_approval_count' => PurchaseRequest::query()->where('status', 'SUBMITTED')->where('updated_at', '<', now()->subDays(7))->count(),
            ],
            'hr' => [
                'pending_leave_count' => \App\Models\Leave::query()->whereIn('status', ['PENDING', 'Pending'])->where('created_at', '<', now()->subDays(3))->count(),
                'pending_overtime_count' => \App\Models\OvertimeRequest::query()->where('status', 'PENDING')->where('created_at', '<', now()->subDays(3))->count(),
            ],
        ];
    }
}
