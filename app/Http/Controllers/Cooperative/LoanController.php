<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Contracts\OrganizationScopedQueryService;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ApproveLoanRequest;
use App\Http\Requests\Cooperative\DisburseLoanRequest;
use App\Http\Requests\Cooperative\PreviewLoanCalculationPageRequest;
use App\Http\Requests\Cooperative\RejectLoanRequest;
use App\Http\Requests\Cooperative\StoreLoanPaymentRequest;
use App\Http\Requests\Cooperative\StoreLoanRequest;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Services\Cooperative\LoanCalculatorService;
use App\Services\Cooperative\LoanPageDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(
        Request $request,
        OrganizationScopedQueryService $scopeService,
        LoanPageDataService $pageData,
    ): Response {
        $this->authorize('viewAny', Loan::class);

        $user = $request->user();
        $isAdmin = $user?->can('view_cooperative_all') || $user?->can('manage_cooperative_loan');

        $query = Loan::query()->with(['member', 'loanType']);
        $scopeService->scopeVisibleTo($query, $user);

        if (! $isAdmin) {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('user_id', $user?->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('cooperative_member_id')) {
            $query->where('cooperative_member_id', $request->input('cooperative_member_id'));
        }

        // Scope supporting data and stats to the same authorization as the main query.
        // Non-admin users (who only see their own loans) should not receive
        // the full member list or global statistics.
        $props = [
            'loans' => $query->latest()->paginate(15)->withQueryString()->through(
                fn (Loan $loan): array => $pageData->list($loan),
            ),
            'loanTypes' => LoanType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'cooperative_member_id']),
        ];

        if ($isAdmin) {
            $memberQuery = CooperativeMember::query()->active();
            $scopeService->scopeVisibleTo($memberQuery, $user);
            $props['members'] = $memberQuery->orderBy('name')->get(['id', 'member_no', 'name']);
            $statsQuery = Loan::query();
            $scopeService->scopeVisibleTo($statsQuery, $user);
            $props['stats'] = [
                'applied' => (clone $statsQuery)->where('status', LoanStatus::Applied)->count(),
                'manager_approved' => (clone $statsQuery)->where('status', LoanStatus::ManagerApproved)->count(),
                'active' => (clone $statsQuery)->where('status', LoanStatus::Active)->count(),
                'paid_off' => (clone $statsQuery)->where('status', LoanStatus::PaidOff)->count(),
            ];
        }

        return Inertia::render('Cooperative/Loans/Index', $props);
    }

    public function create(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('manage', Loan::class);

        $memberQuery = CooperativeMember::query()->active();
        $scopeService->scopeVisibleTo($memberQuery, $request->user());

        return Inertia::render('Cooperative/Loans/Create', [
            'members' => $memberQuery->orderBy('name')->get(['id', 'member_no', 'name']),
            'loanTypes' => LoanType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLoanRequest $request, LoanServiceContract $loanService, OrganizationScopedQueryService $scopeService): RedirectResponse
    {
        $this->authorize('manage', Loan::class);

        $memberQuery = CooperativeMember::query()->whereKey($request->validated('cooperative_member_id'));
        $scopeService->scopeVisibleTo($memberQuery, $request->user());
        $member = $memberQuery->firstOrFail();

        $loan = $loanService->apply([
            ...$request->validated(),
            'organization_id' => $member->organization_id,
        ], $request->user());

        return redirect()->route('cooperative.loans.show', $loan)->with('success', 'Pinjaman berhasil diajukan.');
    }

    public function show(Request $request, Loan $loan, LoanPageDataService $pageData): Response
    {
        $this->authorize('view', $loan);

        $loan->load([
            'member.organization',
            'loanType',
            'installments',
            'payments',
            'managerReviewer',
            'approver',
        ]);

        $approvalLogs = \App\Models\ApprovalLog::query()
            ->where('subject_type', Loan::class)
            ->where('subject_id', (string) $loan->id)
            ->latest()
            ->get();

        return Inertia::render('Cooperative/Loans/Show', [
            'loan' => $pageData->detail($loan),
            'approvalLogs' => $approvalLogs->map(fn ($log): array => [
                'id' => $log->id,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ])->all(),
        ]);
    }

    public function review(ApproveLoanRequest $request, Loan $loan, LoanServiceContract $loanService): RedirectResponse
    {
        $this->authorize('managerReview', $loan);

        $loanService->managerReview($loan, $request->user(), $request->validated('notes'));

        return back()->with('success', 'Review manajer berhasil dicatat.');
    }

    public function approve(ApproveLoanRequest $request, Loan $loan, LoanServiceContract $loanService): RedirectResponse
    {
        $this->authorize('approve', $loan);

        $loanService->approve($loan, $request->user(), $request->validated('notes'));

        return back()->with('success', 'Pinjaman berhasil disetujui.');
    }

    public function reject(RejectLoanRequest $request, Loan $loan, LoanServiceContract $loanService): RedirectResponse
    {
        $this->authorize('reject', $loan);

        $loanService->reject($loan, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'Pinjaman berhasil ditolak.');
    }

    public function disburse(DisburseLoanRequest $request, Loan $loan, LoanServiceContract $loanService): RedirectResponse
    {
        $this->authorize('disburse', $loan);

        $loanService->disburse($loan, $request->user(), $request->validated('reference_no'));

        return back()->with('success', 'Pinjaman berhasil dicairkan.');
    }

    public function pay(StoreLoanPaymentRequest $request, Loan $loan, LoanServiceContract $loanService): RedirectResponse
    {
        $this->authorize('recordPayment', $loan);

        $loanService->recordPayment($loan, $request->validated(), $request->user());

        return back()->with('success', 'Pembayaran angsuran berhasil dicatat.');
    }

    public function calculator(PreviewLoanCalculationPageRequest $request, LoanCalculatorService $calculatorService): Response
    {
        $this->authorize('viewAny', Loan::class);

        $preview = null;
        $input = $request->only(['loan_type_id', 'principal_amount', 'term_months', 'first_due_date']);

        if ($request->filled(['loan_type_id', 'principal_amount', 'term_months', 'first_due_date'])) {
            $validated = $request->validated();
            $loanType = LoanType::query()->findOrFail($validated['loan_type_id']);
            $preview = $calculatorService->calculate(
                $loanType,
                (float) $validated['principal_amount'],
                (int) $validated['term_months'],
                (string) $validated['first_due_date'],
            );
        }

        return Inertia::render('Cooperative/Loans/Calculator', [
            'loanTypes' => LoanType::query()->where('is_active', true)->orderBy('name')->get(),
            'input' => $input,
            'preview' => $preview,
        ]);
    }
}
