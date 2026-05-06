<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MemberPaymentProofRequest;
use App\Http\Requests\Api\MemberSupportTicketRequest;
use App\Http\Requests\Cooperative\ApplyLoanRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\CooperativeSupportTicket;
use App\Models\Loan;
use App\Services\Cooperative\LoanService;
use App\Services\Cooperative\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberSelfServiceController extends Controller
{
    public function dashboard(Request $request, PointService $pointService): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $pointSummary = $pointService->balanceSummary($member);

        return response()->json([
            'data' => [
                'member' => $member->load(['organization', 'user']),
                'summary' => [
                    'savings_balance' => $this->savingsBalance($member),
                    'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                    'active_loans' => $member->loans()->where('status', 'ACTIVE')->count(),
                    'loan_outstanding' => (float) $member->loans()->where('status', 'ACTIVE')->sum('outstanding_amount'),
                    'points_balance' => $pointSummary['total_points'],
                    'member_tier' => $pointSummary['member_tier'],
                    'unread_notifications' => $request->user()?->unreadNotifications()->count() ?? 0,
                ],
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => $request->user(),
                'member' => $this->memberOrAbort($request)->load(['organization']),
            ],
        ]);
    }

    public function updateProfile(UpdateMemberPortalProfileRequest $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $user = $request->user();

        $user?->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        $member->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'address' => $request->validated('address'),
        ]);

        return response()->json([
            'data' => [
                'user' => $user?->refresh(),
                'member' => $member->refresh(),
            ],
        ]);
    }

    public function savingsSummary(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $ledgerTotals = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->selectRaw('entry_type, COALESCE(SUM(credit), 0) as credit_total, COALESCE(SUM(debit), 0) as debit_total')
            ->groupBy('entry_type')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                $row->entry_type => [
                    'credit' => (float) $row->credit_total,
                    'debit' => (float) $row->debit_total,
                    'balance' => (float) $row->credit_total - (float) $row->debit_total,
                ],
            ]);

        return response()->json([
            'data' => [
                'total_balance' => $this->savingsBalance($member),
                'by_entry_type' => $ledgerTotals,
                'total_paid' => (float) $member->payments()->where('status', 'APPROVED')->sum('amount'),
                'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'pending_invoice_amount' => (float) $member->invoices()
                    ->whereIn('status', ['UNPAID', 'PARTIAL'])
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as remaining')
                    ->value('remaining'),
            ],
        ]);
    }

    public function savingsLedger(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $runningBalance = 0.0;

        $entries = CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('posted_at', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('posted_at', '<=', $request->input('end_date')))
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get()
            ->map(function (CooperativeLedgerEntry $entry) use (&$runningBalance): array {
                $runningBalance += (float) $entry->credit - (float) $entry->debit;

                return [
                    'id' => $entry->id,
                    'entry_type' => $entry->entry_type,
                    'description' => $entry->description,
                    'posted_at' => $entry->posted_at,
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'running_balance' => round($runningBalance, 2),
                ];
            })
            ->reverse()
            ->values();

        return response()->json(['data' => $entries]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json($member->invoices()
            ->with('contributionType')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 15)));
    }

    public function payments(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json($member->payments()
            ->with('invoice.contributionType')
            ->orderByDesc('paid_at')
            ->paginate($request->integer('per_page', 15)));
    }

    public function uploadPaymentProof(MemberPaymentProofRequest $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $invoice = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->findOrFail($request->validated('cooperative_dues_invoice_id'));

        $proofPath = $request->file('proof')->store('cooperative/payment-proofs/'.$member->id, 'public');

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $request->user()?->id,
            'amount' => $request->validated('amount'),
            'payment_method' => $request->validated('payment_method'),
            'paid_at' => $request->validated('paid_at'),
            'status' => 'PENDING',
            'proof_path' => $proofPath,
            'reference_no' => $request->validated('reference_no'),
            'notes' => $request->validated('notes'),
        ]);

        return response()->json(['data' => $payment->load('invoice.contributionType')], 201);
    }

    public function loans(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json($member->loans()
            ->with(['loanType', 'installments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    public function applyLoan(ApplyLoanRequest $request, LoanService $loanService): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $loan = $loanService->apply([
            ...$request->validated(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
        ], $request->user());

        return response()->json(['data' => $loan], 201);
    }

    public function loan(Request $request, Loan $loan): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($loan->cooperative_member_id === $member->id, 403);

        return response()->json([
            'data' => $loan->load(['loanType', 'installments', 'payments', 'approvalLogs']),
        ]);
    }

    public function shu(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $periods = CooperativeShuPeriod::query()
            ->with(['allocations' => fn ($query) => $query->where('cooperative_member_id', $member->id)])
            ->where('status', 'CLOSED')
            ->whereHas('allocations', fn ($query) => $query->where('cooperative_member_id', $member->id))
            ->orderByDesc('year')
            ->get();

        return response()->json(['data' => $periods]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json($request->user()
            ->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 15)));
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json($member->supportTickets()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15)));
    }

    public function storeSupportTicket(MemberSupportTicketRequest $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $ticket = CooperativeSupportTicket::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $request->user()?->id,
            'ticket_no' => 'TKT-'.now()->format('YmdHis').'-'.$member->id.'-'.Str::upper(Str::random(6)),
            'category' => $request->validated('category') ?? 'GENERAL',
            'priority' => $request->validated('priority') ?? 'NORMAL',
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'status' => 'OPEN',
        ]);

        return response()->json(['data' => $ticket], 201);
    }

    private function memberOrAbort(Request $request): CooperativeMember
    {
        $member = $request->user()?->cooperativeMember;

        abort_unless($member, 403, 'Akun ini belum terhubung ke anggota koperasi.');

        return $member;
    }

    private function savingsBalance(CooperativeMember $member): float
    {
        return (float) CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
            ->value('balance');
    }
}
