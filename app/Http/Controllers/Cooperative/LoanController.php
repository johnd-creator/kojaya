<?php

namespace App\Http\Controllers\Cooperative;

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
use App\Services\Cooperative\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeLoanView($request);

        $query = Loan::query()->with(['member', 'loanType']);

        if ($request->user()?->hasRole('Anggota')) {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('user_id', $request->user()?->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('cooperative_member_id')) {
            $query->where('cooperative_member_id', $request->input('cooperative_member_id'));
        }

        return Inertia::render('Cooperative/Loans/Index', [
            'loans' => $query->latest()->paginate(15)->withQueryString(),
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
            'loanTypes' => LoanType::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'cooperative_member_id']),
            'stats' => [
                'applied' => Loan::query()->where('status', LoanStatus::Applied)->count(),
                'active' => Loan::query()->where('status', LoanStatus::Active)->count(),
                'paid_off' => Loan::query()->where('status', LoanStatus::PaidOff)->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeLoanManagement($request);

        return Inertia::render('Cooperative/Loans/Create', [
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
            'loanTypes' => LoanType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLoanRequest $request, LoanService $loanService): RedirectResponse
    {
        $this->authorizeLoanManagement($request);

        $member = CooperativeMember::query()->findOrFail($request->validated('cooperative_member_id'));

        $loan = $loanService->apply([
            ...$request->validated(),
            'organization_id' => $member->organization_id,
        ], $request->user());

        return redirect()->route('cooperative.loans.show', $loan)->with('success', 'Pinjaman berhasil diajukan.');
    }

    public function show(Request $request, Loan $loan): Response
    {
        $this->authorizeSpecificLoanView($request, $loan);

        $loan->load([
            'member.user',
            'member.organization',
            'loanType',
            'installments',
            'payments',
        ]);

        $approvalLogs = \App\Models\ApprovalLog::query()
            ->where('subject_type', Loan::class)
            ->where('subject_id', (string) $loan->id)
            ->latest()
            ->get();

        return Inertia::render('Cooperative/Loans/Show', [
            'loan' => $loan,
            'approvalLogs' => $approvalLogs,
        ]);
    }

    public function approve(ApproveLoanRequest $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $this->authorizeLoanApproval($request);

        $loanService->approve($loan, $request->user(), $request->validated('notes'));

        return back()->with('success', 'Pinjaman berhasil disetujui.');
    }

    public function reject(RejectLoanRequest $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $this->authorizeLoanApproval($request);

        $loanService->reject($loan, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'Pinjaman berhasil ditolak.');
    }

    public function disburse(DisburseLoanRequest $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $this->authorizeLoanApproval($request);

        $loanService->disburse($loan, $request->user(), $request->validated('reference_no'));

        return back()->with('success', 'Pinjaman berhasil dicairkan.');
    }

    public function pay(StoreLoanPaymentRequest $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $this->authorizeLoanManagement($request);

        $loanService->recordPayment($loan, $request->validated(), $request->user());

        return back()->with('success', 'Pembayaran angsuran berhasil dicatat.');
    }

    public function calculator(PreviewLoanCalculationPageRequest $request, LoanCalculatorService $calculatorService): Response
    {
        $this->authorizeLoanView($request);

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

    private function authorizeLoanView(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi', 'Anggota']), 403);
    }

    private function authorizeLoanManagement(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi']), 403);
    }

    private function authorizeLoanApproval(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi']), 403);
    }

    private function authorizeSpecificLoanView(Request $request, Loan $loan): void
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi'])) {
            return;
        }

        abort_unless($user->hasRole('Anggota') && $loan->member?->user_id === $user->id, 403);
    }
}
