<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\CooperativeShuPeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MarkMemberOnboardingStepRequest;
use App\Http\Requests\Api\MemberPaymentProofRequest;
use App\Http\Requests\Api\MemberSupportTicketRequest;
use App\Http\Requests\Api\StoreLoanRestructureRequest;
use App\Http\Requests\Api\StoreSavingsWithdrawalRequest;
use App\Http\Requests\Cooperative\ApplyLoanRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Http\Resources\LoanResource;
use App\Http\Resources\MemberInvoiceResource;
use App\Http\Resources\MemberLoanRestructureResource;
use App\Http\Resources\MemberPaymentResource;
use App\Http\Resources\MemberSavingsWithdrawalResource;
use App\Http\Resources\MemberSelfServiceResource;
use App\Http\Resources\MemberSupportTicketResource;
use App\Http\Resources\MemberUserResource;
use App\Http\Resources\NotificationResource;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\CooperativeSupportTicket;
use App\Models\Loan;
use App\Services\Cooperative\CooperativeReceiptService;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MemberSelfServiceController extends Controller
{
    public function dashboard(
        Request $request,
        PointService $pointService,
        MemberOnboardingService $onboardingService,
        MemberStatusJourneyService $journeyService,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $pointSummary = $pointService->balanceSummary($member);

        return response()->json([
            'data' => [
                'member' => new MemberSelfServiceResource($member->load(['organization', 'user'])),
                'summary' => [
                    'savings_balance' => $this->savingsBalance($member),
                    'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                    'active_loans' => $member->loans()->where('status', 'ACTIVE')->count(),
                    'loan_outstanding' => (float) $member->loans()->where('status', 'ACTIVE')->sum('outstanding_amount'),
                    'points_balance' => $pointSummary['total_points'],
                    'member_tier' => $pointSummary['member_tier'],
                    'unread_notifications' => $request->user()?->unreadNotifications()->count() ?? 0,
                ],
                'onboarding' => $onboardingService->status($member),
                'journeys' => $journeyService->summary($member),
            ],
        ]);
    }

    public function onboardingStatus(Request $request, MemberOnboardingService $service): JsonResponse
    {
        return response()->json(['data' => $service->status($this->memberOrAbort($request))]);
    }

    public function markOnboardingStep(
        MarkMemberOnboardingStepRequest $request,
        MemberOnboardingService $service,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $service->markStep($member, $request->validated('step'));

        return response()->json(['data' => $service->status($member)]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new MemberUserResource($request->user()?->loadMissing('roles')),
                'member' => new MemberSelfServiceResource($this->memberOrAbort($request)->load(['organization'])),
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
                'user' => new MemberUserResource($user?->refresh()->loadMissing('roles')),
                'member' => new MemberSelfServiceResource($member->refresh()->load(['organization'])),
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

    public function requestSavingsWithdrawal(
        StoreSavingsWithdrawalRequest $request,
        SavingsWithdrawalService $service,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $withdrawal = $service->request($member, $request->validated(), $request->user());

        return (new MemberSavingsWithdrawalResource($withdrawal))
            ->response()
            ->setStatusCode(201);
    }

    public function invoices(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberInvoiceResource::collection($member->invoices()
            ->with('contributionType')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 15)))
            ->response();
    }

    public function payments(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberPaymentResource::collection($member->payments()
            ->with(['invoice.contributionType', 'receipt'])
            ->orderByDesc('paid_at')
            ->paginate($request->integer('per_page', 15)))
            ->response();
    }

    public function statusJourney(Request $request, MemberStatusJourneyService $service): JsonResponse
    {
        return response()->json(['data' => $service->summary($this->memberOrAbort($request))]);
    }

    public function paymentReceipt(Request $request, CooperativePayment $payment, CooperativeReceiptService $receiptService): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($payment->cooperative_member_id === $member->id, 403);
        abort_unless($payment->status === 'APPROVED', 404, 'Receipt pembayaran belum tersedia.');

        $receipt = $payment->receipt ?: $receiptService->issue($payment, $request->user());

        return response()->json([
            'data' => [
                'receipt_no' => $receipt->receipt_no,
                'issued_at' => $receipt->issued_at,
                'download_url' => URL::temporarySignedRoute(
                    'download.cooperative-receipt',
                    now()->addMinutes(10),
                    ['receipt' => $receipt->id],
                ),
            ],
        ]);
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

        return (new MemberPaymentResource($payment->load('invoice.contributionType')))
            ->response()
            ->setStatusCode(201);
    }

    public function loans(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return LoanResource::collection($member->loans()
            ->with(['loanType', 'installments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15)))
            ->response();
    }

    public function applyLoan(ApplyLoanRequest $request, LoanServiceContract $loanService): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $loan = $loanService->apply([
            ...$request->validated(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
        ], $request->user());

        return (new LoanResource($loan))
            ->response()
            ->setStatusCode(201);
    }

    public function loan(Request $request, Loan $loan): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($loan->cooperative_member_id === $member->id, 403);

        return response()->json([
            'data' => new LoanResource($loan->load(['loanType', 'installments', 'payments', 'approvalLogs'])),
        ]);
    }

    public function requestLoanRestructure(
        StoreLoanRestructureRequest $request,
        Loan $loan,
        LoanRestructureService $service,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);

        abort_unless($loan->cooperative_member_id === $member->id, 403);

        return response()->json([
            'data' => new MemberLoanRestructureResource($service->request($loan, $request->validated(), $request->user())),
        ], 201);
    }

    public function shu(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $periods = CooperativeShuPeriod::query()
            ->with(['allocations' => fn ($query) => $query->where('cooperative_member_id', $member->id)])
            ->whereIn('status', [CooperativeShuPeriodStatus::Closed->value, CooperativeShuPeriodStatus::ClosedRevised->value])
            ->whereHas('allocations', fn ($query) => $query->where('cooperative_member_id', $member->id))
            ->orderByDesc('year')
            ->get();

        return response()->json(['data' => $periods]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return NotificationResource::collection($request->user()
            ->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 15)))
            ->response();
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberSupportTicketResource::collection($member->supportTickets()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15)))
            ->response();
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

        return (new MemberSupportTicketResource($ticket))
            ->response()
            ->setStatusCode(201);
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
