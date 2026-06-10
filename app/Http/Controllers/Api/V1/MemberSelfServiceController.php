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
use App\Models\PosTransaction;
use App\Models\RewardRedemption;
use App\Services\Cooperative\CooperativeReceiptService;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsSummaryService;
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
        SavingsSummaryService $savingsSummary,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $pointSummary = $pointService->balanceSummary($member);
        $savingSummary = $savingsSummary->summary($member);

        return response()->json([
            'data' => [
                'member' => new MemberSelfServiceResource($member->load(['organization', 'user'])),
                'summary' => [
                    'savings_balance' => $savingSummary['total_balance'],
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

    public function savingsSummary(Request $request, SavingsSummaryService $savingsSummary): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $summary = $savingsSummary->summary($member);

        return response()->json([
            'data' => [
                'total_balance' => $summary['total_balance'],
                'by_category' => $summary['by_category'],
                'uncategorized' => $summary['uncategorized'],
                'total_paid' => (float) $member->payments()->where('status', 'APPROVED')->sum('amount'),
                'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'pending_invoice_amount' => (float) $member->invoices()
                    ->whereIn('status', ['UNPAID', 'PARTIAL'])
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as remaining')
                    ->value('remaining'),
            ],
        ]);
    }

    public function savingsLedger(Request $request, SavingsSummaryService $savingsSummary): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json(
            $savingsSummary->ledgerQuery($member, $request->only(['category', 'contribution_type_id', 'start_date', 'end_date']))
                ->orderByDesc('posted_at')
                ->orderByDesc('id')
                ->paginate($request->integer('per_page', 15))
                ->through(fn (CooperativeLedgerEntry $entry): array => [
                    'id' => $entry->id,
                    'entry_type' => $entry->entry_type,
                    'ledger_scope' => $entry->ledger_scope,
                    'category' => $entry->contributionType?->category ?? $entry->category_snapshot,
                    'contribution_type' => $entry->contributionType ? [
                        'id' => $entry->contributionType->id,
                        'code' => $entry->contributionType->code,
                        'name' => $entry->contributionType->name,
                        'category' => $entry->contributionType->category,
                    ] : null,
                    'description' => $entry->description,
                    'posted_at' => $entry->posted_at?->toDateString(),
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'balance_delta' => round((float) $entry->credit - (float) $entry->debit, 2),
                ])
        );
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
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->input('period')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category'))))
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 15)))
            ->response();
    }

    public function payments(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberPaymentResource::collection($member->payments()
            ->with(['invoice.contributionType', 'receipt'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('invoice.contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category'))))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('paid_at', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('paid_at', '<=', $request->input('end_date')))
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

    public function rewardRedemptions(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return response()->json(
            $member->rewardRedemptions()
                ->with('reward')
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->orderByDesc('redeemed_at')
                ->paginate($request->integer('per_page', 15))
                ->through(fn (RewardRedemption $redemption): array => [
                    'id' => $redemption->id,
                    'reward_id' => $redemption->reward_id,
                    'reward' => $redemption->reward ? [
                        'id' => $redemption->reward->id,
                        'name' => $redemption->reward->name,
                        'category' => $redemption->reward->category,
                        'points_required' => (int) $redemption->reward->points_required,
                    ] : null,
                    'quantity' => (int) $redemption->quantity,
                    'points_used' => (int) $redemption->points_used,
                    'status' => $redemption->status,
                    'delivery_address' => $redemption->delivery_address,
                    'redeemed_at' => $redemption->redeemed_at?->toISOString(),
                    'processed_at' => $redemption->processed_at?->toISOString(),
                ])
        );
    }

    public function transactions(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $filters = $request->only(['date_from', 'date_to', 'status']);

        $baseQuery = PosTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('sold_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('sold_at', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $summaryQuery = clone $baseQuery;
        $itemsQuery = clone $baseQuery;
        $latestQuery = clone $baseQuery;

        $transactions = $baseQuery
            ->with(['items.product', 'payments', 'cashier:id,name'])
            ->orderByDesc('sold_at')
            ->paginate($request->integer('per_page', 15))
            ->through(fn (PosTransaction $transaction): array => [
                'id' => $transaction->id,
                'transaction_no' => $transaction->transaction_no,
                'client_reference' => $transaction->client_reference,
                'subtotal' => (float) $transaction->subtotal,
                'discount_amount' => (float) $transaction->discount_amount,
                'total_amount' => (float) $transaction->total_amount,
                'status' => $transaction->status,
                'sold_at' => $transaction->sold_at?->toISOString(),
                'cashier' => $transaction->cashier ? [
                    'id' => $transaction->cashier->id,
                    'name' => $transaction->cashier->name,
                ] : null,
                'items' => $transaction->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'product_id' => $item->pos_product_id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                    ] : null,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
                'payments' => $transaction->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'amount' => (float) $payment->amount,
                    'reference_no' => $payment->reference_no,
                ])->values(),
            ]);

        return response()->json([
            'summary' => [
                'total_transactions' => (int) $summaryQuery->count(),
                'total_amount' => (float) $summaryQuery->sum('total_amount'),
                'total_items' => (int) $itemsQuery
                    ->join('pos_transaction_items', 'pos_transactions.id', '=', 'pos_transaction_items.pos_transaction_id')
                    ->sum('quantity'),
                'last_transaction_at' => $latestQuery->latest('sold_at')->value('sold_at'),
            ],
            'transactions' => $transactions,
        ]);
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
}
