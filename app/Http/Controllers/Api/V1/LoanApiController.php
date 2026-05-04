<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ApplyLoanRequest;
use App\Http\Requests\Cooperative\PreviewLoanCalculationRequest;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Services\Cooperative\LoanCalculatorService;
use App\Services\Cooperative\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request);
        $query = Loan::query()->with(['member', 'loanType', 'installments']);

        if ($user->hasRole('Anggota')) {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function apply(ApplyLoanRequest $request, LoanService $loanService): JsonResponse
    {
        $user = $this->authorizedUser($request);
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
        $user = $this->authorizedUser($request);

        if ($user->hasRole('Anggota')) {
            abort_unless($loan->member?->user_id === $user->id, 403);
        }

        return response()->json([
            'data' => $loan->load(['member', 'loanType', 'installments', 'payments']),
        ]);
    }

    public function calculator(PreviewLoanCalculationRequest $request, LoanCalculatorService $calculatorService): JsonResponse
    {
        $this->authorizedUser($request);
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

    private function authorizedUser(Request $request): \App\Models\User
    {
        $user = $request->user();

        abort_unless($user && $user->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi', 'Anggota']), 403);

        return $user;
    }

    private function resolveMember(Request $request, \App\Models\User $user): CooperativeMember
    {
        if ($user->hasRole('Anggota')) {
            return CooperativeMember::query()->where('user_id', $user->id)->firstOrFail();
        }

        $memberId = $request->integer('cooperative_member_id');

        abort_unless($memberId, 422, 'cooperative_member_id wajib diisi.');

        return CooperativeMember::query()->findOrFail($memberId);
    }
}
