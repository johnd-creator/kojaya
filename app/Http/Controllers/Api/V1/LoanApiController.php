<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiPageSize;
use App\Contracts\Cooperative\LoanServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ApplyLoanRequest;
use App\Http\Requests\Cooperative\ApproveLoanRequest;
use App\Http\Requests\Cooperative\PreviewLoanCalculationRequest;
use App\Http\Requests\Cooperative\RejectLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Services\Cooperative\LoanCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request, 'viewAny', Loan::class);
        $query = Loan::query()->with(['member', 'loanType', 'installments']);

        if (! $user->can('view_cooperative_all') && ! $user->can('manage_cooperative_loan')) {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->latest()->paginate($this->apiPageSize($request)));
    }

    public function apply(ApplyLoanRequest $request, LoanServiceContract $loanService): JsonResponse
    {
        $user = $this->authorizedUser($request, 'create', Loan::class);
        $member = $this->resolveMember($request, $user);

        $loan = $loanService->apply([
            ...$request->validated(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
        ], $user);

        return response()->json(['data' => $loan], 201);
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizedUser($request, 'view', $loan);

        return response()->json([
            'data' => new LoanResource($loan->load(['member', 'loanType', 'installments', 'payments', 'approvalLogs'])),
        ]);
    }

    public function review(ApproveLoanRequest $request, Loan $loan, LoanServiceContract $loanService): JsonResponse
    {
        $this->authorizedUser($request, 'managerReview', $loan);

        return response()->json([
            'data' => new LoanResource($loanService->managerReview($loan, $request->user(), $request->validated('notes'))->load(['member', 'loanType', 'installments', 'approvalLogs'])),
        ]);
    }

    public function approve(ApproveLoanRequest $request, Loan $loan, LoanServiceContract $loanService): JsonResponse
    {
        $this->authorizedUser($request, 'approve', $loan);

        return response()->json([
            'data' => new LoanResource($loanService->approve($loan, $request->user(), $request->validated('notes'))->load(['member', 'loanType', 'installments', 'approvalLogs'])),
        ]);
    }

    public function reject(RejectLoanRequest $request, Loan $loan, LoanServiceContract $loanService): JsonResponse
    {
        $this->authorizedUser($request, 'reject', $loan);

        return response()->json([
            'data' => new LoanResource($loanService->reject($loan, $request->user(), $request->validated('rejection_reason'))->load(['member', 'loanType', 'installments', 'approvalLogs'])),
        ]);
    }

    public function calculator(PreviewLoanCalculationRequest $request, LoanCalculatorService $calculatorService): JsonResponse
    {
        $this->authorizedUser($request, 'viewAny', Loan::class);
        $validated = $request->validated();
        $loanType = LoanType::query()->findOrFail($validated['loan_type_id']);

        return response()->json([
            'data' => $calculatorService->calculate(
                $loanType,
                (float) $validated['principal_amount'],
                (int) $validated['term_months'],
                (string) $validated['first_due_date'],
            ),
        ]);
    }

    private function authorizedUser(Request $request, string $ability, mixed $arguments): \App\Models\User
    {
        $user = $request->user();

        abort_unless($user, 401);
        $this->authorize($ability, $arguments);

        return $user;
    }

    private function resolveMember(Request $request, \App\Models\User $user): CooperativeMember
    {
        if ($user->can('view_cooperative_member') && ! $user->can('manage_cooperative_loan')) {
            return CooperativeMember::query()->where('user_id', $user->id)->firstOrFail();
        }

        $memberId = $request->integer('cooperative_member_id');

        abort_unless($memberId, 422, 'cooperative_member_id wajib diisi.');

        return CooperativeMember::query()->findOrFail($memberId);
    }
}
