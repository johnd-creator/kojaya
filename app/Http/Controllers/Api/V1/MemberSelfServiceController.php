<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateMemberBillPaymentIntentRequest;
use App\Http\Requests\Api\MarkMemberOnboardingStepRequest;
use App\Http\Requests\Api\MemberPaymentProofRequest;
use App\Http\Requests\Api\MemberSupportTicketRequest;
use App\Http\Requests\Api\StoreLoanRestructureRequest;
use App\Http\Requests\Api\StoreMemberResignationRequest;
use App\Http\Requests\Api\StoreSavingsWithdrawalRequest;
use App\Http\Requests\Cooperative\ApplyLoanRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Http\Resources\LoanResource;
use App\Http\Resources\LoanTypeResource;
use App\Http\Resources\MemberInvoiceResource;
use App\Http\Resources\MemberLoanRestructureResource;
use App\Http\Resources\MemberPaymentResource;
use App\Http\Resources\MemberResignationRequestResource;
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
use App\Models\LoanInstallment;
use App\Models\LoanType;
use App\Models\MemberPaymentIntent;
use App\Models\MemberResignationRequest;
use App\Models\PosTransaction;
use App\Models\RewardRedemption;
use App\Services\Cooperative\CooperativeReceiptService;
use App\Services\Cooperative\LoanRestructureService;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberProfileService;
use App\Services\Cooperative\MemberResignationRequestService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsSummaryService;
use App\Services\Cooperative\SavingsWithdrawalService;
use App\Services\Integrations\PaymentGatewayService;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MemberSelfServiceController extends Controller
{
    private const QRIS_IMAGE_MAX_BYTES = 262144;

    private const QRIS_IMAGE_CONTENT_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
    ];

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
                    'pending_invoices' => $member->invoices()->forSavingsDues()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
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

    public function updateProfile(UpdateMemberPortalProfileRequest $request, MemberProfileService $profileService): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $user = $request->user();

        abort_unless($user !== null, 401);

        $member = $profileService->update($user, $member, $request->validated());

        return response()->json([
            'data' => [
                'user' => new MemberUserResource($user->refresh()->loadMissing('roles')),
                'member' => new MemberSelfServiceResource($member->refresh()->load(['organization'])),
            ],
        ]);
    }

    public function resignationStatus(Request $request, MemberResignationRequestService $service): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        $latest = $service->latestFor($member);

        return response()->json([
            'data' => $latest ? new MemberResignationRequestResource($latest) : null,
        ]);
    }

    public function submitResignation(
        StoreMemberResignationRequest $request,
        MemberResignationRequestService $service,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $resignation = $service->submit($member, $request->validated(), $request->user());

        return (new MemberResignationRequestResource($resignation))
            ->response()
            ->setStatusCode(201);
    }

    public function cancelResignation(
        Request $request,
        MemberResignationRequestService $service,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);

        $latest = $service->latestFor($member);
        abort_unless($latest !== null && $latest->status === MemberResignationRequest::STATUS_PENDING, 404, 'Tidak ada pengajuan pengunduran diri yang dapat dibatalkan.');

        $service->cancel($latest);

        return response()->json([
            'data' => new MemberResignationRequestResource($latest->refresh()),
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
                'pending_invoices' => $member->invoices()->forSavingsDues()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'pending_invoice_amount' => (float) $member->invoices()
                    ->forSavingsDues()
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
                ->paginate($this->perPage($request))
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
            ->forSavingsDues()
            ->with('contributionType')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->input('period')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category'))))
            ->orderByDesc('period')
            ->paginate($this->perPage($request)))
            ->response();
    }

    public function showInvoice(Request $request, CooperativeDuesInvoice $invoice): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($invoice->cooperative_member_id === $member->id, 403);
        abort_unless($invoice->loadMissing('contributionType')->isSavingsDues(), 404);

        return response()->json([
            'data' => new MemberInvoiceResource($invoice->load(['contributionType', 'member'])),
        ]);
    }

    public function createPaymentIntent(Request $request, CooperativeDuesInvoice $invoice): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($invoice->cooperative_member_id === $member->id, 403);
        abort_unless($invoice->loadMissing('contributionType')->isSavingsDues(), 404);
        abort_unless(in_array($invoice->status, ['UNPAID', 'PARTIAL']), 422, 'Invoice sudah lunas.');

        $payment = $this->pendingPaymentForInvoice($request, $member, $invoice);

        $availableChannels = [
            ['code' => 'VA', 'label' => 'Transfer Bank / VA', 'admin_fee' => 4000, 'fee_type' => 'fixed'],
            ['code' => 'QRIS', 'label' => 'QRIS', 'admin_fee' => 0.7, 'fee_type' => 'percent'],
            ['code' => 'E_WALLET', 'label' => 'E-Wallet (GoPay, DANA, ShopeePay)', 'admin_fee' => 2000, 'fee_type' => 'fixed'],
        ];

        $channelOptions = array_map(function ($channel) use ($payment) {
            $fee = $channel['fee_type'] === 'percent'
                ? ((float) $payment->amount * $channel['admin_fee'] / 100)
                : $channel['admin_fee'];

            return [
                'code' => $channel['code'],
                'label' => $channel['label'],
                'admin_fee' => round($fee, 2),
                'fee_type' => $channel['fee_type'],
            ];
        }, $availableChannels);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'available_channels' => $channelOptions,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberPaymentResource::collection($member->payments()
            // Voided/rolled-back payments are admin-internal corrections; hide
            // them from the member's own history so the member only sees real
            // payments and the restored (unpaid) invoice in the bills list.
            ->where('status', '!=', 'VOID')
            ->with(['invoice.contributionType', 'receipt'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('invoice.contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category'))))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('paid_at', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('paid_at', '<=', $request->input('end_date')))
            ->orderByDesc('paid_at')
            ->paginate($this->perPage($request)))
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

    public function paymentStatus(Request $request, CooperativePayment $payment): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($payment->cooperative_member_id === $member->id, 403);

        $gatewayExpiresAt = $this->parseGatewayExpiry($payment);
        $isPaid = $payment->gateway_status === 'PAID' || $payment->status === 'APPROVED';
        $isFailed = in_array($payment->gateway_status, ['FAILED', 'EXPIRED', 'CANCELLED'], true);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'gateway_status' => $payment->gateway_status,
                'reconciled_at' => $payment->reconciled_at?->toIso8601String(),
                'gateway_expires_at' => $gatewayExpiresAt?->toIso8601String(),
                'is_paid' => $isPaid,
                'is_failed' => $isFailed,
                'is_terminal' => $isPaid || $isFailed,
                'poll_after_seconds' => (int) ($this->gatewayPresentation($payment)['poll_after_seconds'] ?? 5),
            ],
        ]);
    }

    public function qrisImage(Request $request, CooperativePayment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $member = $this->memberOrAbort($request);

        abort_unless($payment->cooperative_member_id === $member->id, 403);
        abort_unless($payment->payment_method === 'QRIS', 404, 'QRIS pembayaran tidak tersedia.');

        $presentation = $this->gatewayPresentation($payment);
        $payload = is_array($payment->gateway_payload) ? $payment->gateway_payload : [];
        $qrString = $presentation['qr_string'] ?? $payload['qr_string'] ?? null;

        if (is_string($qrString) && $qrString !== '') {
            $writer = new Writer(new GDLibRenderer(360, 2, 'png'));

            return response($writer->writeString($qrString), 200)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'private, max-age=60');
        }

        $actionUrl = $this->qrisActionUrl($payment);

        abort_unless(is_string($actionUrl) && str_starts_with($actionUrl, 'https://'), 404, 'QRIS pembayaran tidak tersedia.');

        $this->assertAllowedQrisActionUrl($actionUrl);

        $response = Http::timeout(5)
            ->withOptions(['allow_redirects' => false])
            ->get($actionUrl);

        abort_unless($response->successful(), 502, 'Gagal mengambil QRIS dari Midtrans.');
        $this->assertValidQrisImageResponse($response);

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type') ?: 'image/png')
            ->header('Cache-Control', 'private, max-age=60');
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
            ->paginate($this->perPage($request)))
            ->response();
    }

    public function loanOptions(Request $request): JsonResponse
    {
        $this->memberOrAbort($request);

        return LoanTypeResource::collection(
            LoanType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        )->response();
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
                ->paginate($this->perPage($request))
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
            ->paginate($this->perPage($request))
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
            ->paginate($this->perPage($request)))
            ->response();
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        return MemberSupportTicketResource::collection($member->supportTickets()
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request)))
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

    /**
     * Detail of a single member payment. Lets the mobile app poll the latest
     * status (PENDING/APPROVED/REJECTED) after a charge or manual upload.
     */
    public function showPayment(Request $request, CooperativePayment $payment): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        abort_unless($payment->cooperative_member_id === $member->id, 403);

        $payment->load(['invoice.contributionType', 'receipt']);

        return response()->json(['data' => new MemberPaymentResource($payment)]);
    }

    /**
     * Unified bills: merges dues invoices and active-loan installments that are
     * still payable into a single member-scoped list.
     */
    public function bills(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $category = $request->input('category'); // dues|loan|pos_credit
        $status = $request->input('status');
        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $bills = collect();

        if ($category === null || $category === 'dues') {
            $bills = $bills->merge(
                $member->invoices()
                    ->forSavingsDues()
                    ->with('contributionType')
                    ->whereIn('status', ['UNPAID', 'PARTIAL'])
                    ->when($status, fn ($query, $value) => $query->where('status', $value))
                    ->orderByDesc('due_date')
                    ->get()
                    ->map(fn (CooperativeDuesInvoice $invoice) => $this->duesInvoiceToBill($invoice))
            );
        }

        if ($category === null || $category === 'loan') {
            $loanIds = $member->loans()
                ->whereIn('status', [LoanStatus::Active->value, LoanStatus::Defaulted->value])
                ->pluck('id');

            $bills = $bills->merge(
                LoanInstallment::query()
                    ->whereIn('loan_id', $loanIds)
                    ->whereIn('status', [
                        InstallmentStatus::Pending->value,
                        InstallmentStatus::Partial->value,
                        InstallmentStatus::Overdue->value,
                    ])
                    ->when($status, fn ($query, $value) => $query->where('status', $value))
                    ->with('loan.loanType')
                    ->orderByDesc('due_date')
                    ->get()
                    ->map(fn (LoanInstallment $installment) => $this->loanInstallmentToBill($installment))
            );
        }

        if ($category === 'pos_credit') {
            $posCreditBill = $this->posCreditBill($member);

            if ($posCreditBill !== null) {
                $bills = $bills->push($posCreditBill);
            }
        }

        $sorted = $bills->sortByDesc('due_date')->values();

        $payable = $sorted->where('payable', true);
        $todayTimestamp = strtotime('today');
        $summary = [
            'total_bills' => $sorted->count(),
            'payable_count' => $payable->count(),
            'total_remaining' => round((float) $payable->sum('remaining_amount'), 2),
            'overdue_count' => $sorted->filter(
                fn (array $bill) => $bill['payable'] === true
                    && ! empty($bill['due_date'])
                    && strtotime((string) $bill['due_date']) < $todayTimestamp
            )->count(),
            'dues_count' => $sorted->where('source', 'dues')->count(),
            'loan_count' => $sorted->where('source', 'loan')->count(),
            'pos_credit_count' => $sorted->where('source', 'pos_credit')->count(),
        ];

        $page = $request->integer('page', 1);
        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'data' => $paginator->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $perPage,
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Single unified bill detail, addressed by the composite id emitted by the
     * bills list (`dues:{id}`, `loan:{id}`, or `pos_credit:{member_id}`).
     */
    public function showBill(Request $request, string $bill): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $segments = explode(':', $bill, 2);
        abort_unless(count($segments) === 2, 404, 'Format bill tidak dikenali.');

        [$source, $id] = $segments;

        $data = match ($source) {
            'dues' => $this->resolveDuesBill($member, $id),
            'loan' => $this->resolveLoanBill($member, $id),
            'pos_credit' => $this->resolvePosCreditBill($member, $id),
            default => null,
        };

        abort_unless($data !== null, 404, 'Bill tidak ditemukan.');

        return response()->json(['data' => $data]);
    }

    public function createBillPaymentIntent(
        CreateMemberBillPaymentIntentRequest $request,
        string $bill,
        PaymentGatewayService $gateway,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);
        $segments = explode(':', $bill, 2);
        abort_unless(count($segments) === 2, 404, 'Format bill tidak dikenali.');

        [$source, $id] = $segments;

        $channel = (string) $request->validated('channel');

        if ($source === 'dues') {
            $invoice = $member->invoices()
                ->forSavingsDues()
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->findOrFail($id);

            $payment = $this->pendingPaymentForInvoice($request, $member, $invoice, $channel);
            $charge = $gateway->createCharge($payment, $channel);

            return response()->json([
                'data' => [
                    'bill_id' => $bill,
                    'source' => $source,
                    'payment' => new MemberPaymentResource($payment->refresh()->load(['invoice.contributionType', 'receipt'])),
                    'charge' => $charge,
                ],
            ], 201);
        }

        $intent = match ($source) {
            'loan' => $this->pendingLoanPaymentIntent($request, $member, $id, $channel),
            'pos_credit' => $this->pendingPosCreditPaymentIntent($request, $member, $id, $channel),
            default => null,
        };

        abort_unless($intent !== null, 404, 'Bill tidak ditemukan.');

        $charge = $gateway->createIntentCharge($intent);

        return response()->json([
            'data' => [
                'bill_id' => $bill,
                'source' => $source,
                'payment_intent' => $this->formatPaymentIntent($intent->refresh()),
                'charge' => $charge,
            ],
        ], 201);
    }

    /**
     * Unified activity timeline merging POS purchases and cooperative payments
     * into a single, server-paginated stream. The existing `/transactions`
     * endpoint remains POS-only for backward compatibility.
     */
    public function unifiedTransactions(Request $request): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $source = $request->input('source'); // pos|payment

        $items = collect();

        if ($source === null || $source === 'pos') {
            $items = $items->merge(
                $member->posTransactions()
                    ->orderByDesc('sold_at')
                    ->limit(100)
                    ->get()
                    ->map(fn (PosTransaction $transaction) => [
                        'id' => 'pos:'.$transaction->id,
                        'source' => 'pos',
                        'title' => 'Belanja POS',
                        'subtitle' => $transaction->transaction_no,
                        'amount' => (float) $transaction->total_amount,
                        'date' => $transaction->sold_at?->toISOString(),
                        'status' => $transaction->status,
                        'is_pos' => true,
                    ])
            );
        }

        if ($source === null || $source === 'payment') {
            $items = $items->merge(
                $member->payments()
                    ->with(['invoice.contributionType'])
                    ->orderByDesc('paid_at')
                    ->limit(100)
                    ->get()
                    ->map(fn (CooperativePayment $payment) => [
                        'id' => 'payment:'.$payment->id,
                        'source' => 'payment',
                        'title' => $payment->invoice?->contributionType?->name ?? 'Pembayaran Iuran',
                        'subtitle' => $payment->payment_method,
                        'amount' => (float) $payment->amount,
                        'date' => $payment->paid_at?->toDateString(),
                        'status' => $payment->status,
                        'is_pos' => false,
                    ])
            );
        }

        $sorted = $items->sortByDesc('date')->values();

        $page = $request->integer('page', 1);
        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'data' => $paginator->items(),
            'summary' => [
                'total_count' => $sorted->count(),
                'pos_count' => $sorted->where('source', 'pos')->count(),
                'payment_count' => $sorted->where('source', 'payment')->count(),
                'total_amount' => round((float) $sorted->sum('amount'), 2),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $perPage,
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    protected function duesInvoiceToBill(CooperativeDuesInvoice $invoice): array
    {
        $remaining = max((float) $invoice->amount - (float) $invoice->paid_amount, 0);

        return [
            'id' => 'dues:'.$invoice->id,
            'source' => 'dues',
            'source_id' => (string) $invoice->id,
            'title' => $invoice->contributionType?->name ?? 'Tagihan Iuran',
            'amount' => (float) $invoice->amount,
            'paid_amount' => (float) $invoice->paid_amount,
            'remaining_amount' => round($remaining, 2),
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'payable' => ! in_array($invoice->status, ['PAID', 'VOID'], true),
            'period' => $invoice->period,
            'category' => $invoice->contributionType?->category ?? 'dues',
        ];
    }

    protected function loanInstallmentToBill(LoanInstallment $installment): array
    {
        $remaining = max((float) $installment->amount_due - (float) $installment->amount_paid, 0);
        $loanTypeName = $installment->loan?->loanType?->name ?? 'Pinjaman';
        $installmentNo = $installment->installment_no ?? '?';

        return [
            'id' => 'loan:'.$installment->id,
            'source' => 'loan',
            'source_id' => (string) $installment->id,
            'title' => "{$loanTypeName} · Angsuran #{$installmentNo}",
            'amount' => (float) $installment->amount_due,
            'paid_amount' => (float) $installment->amount_paid,
            'remaining_amount' => round($remaining, 2),
            'due_date' => $installment->due_date?->toDateString(),
            'status' => $installment->status?->value,
            'payable' => $installment->status !== InstallmentStatus::Paid,
            'period' => null,
            'category' => 'loan',
        ];
    }

    protected function resolveDuesBill(CooperativeMember $member, string $id): ?array
    {
        $invoice = $member->invoices()
            ->forSavingsDues()
            ->with('contributionType')
            ->find($id);

        return $invoice?->status !== null ? $this->duesInvoiceToBill($invoice) : null;
    }

    protected function resolveLoanBill(CooperativeMember $member, string $id): ?array
    {
        $installment = LoanInstallment::query()
            ->with('loan.loanType')
            ->where('id', $id)
            ->whereHas('loan', fn ($query) => $query->where('cooperative_member_id', $member->id))
            ->first();

        return $installment?->id !== null ? $this->loanInstallmentToBill($installment) : null;
    }

    protected function resolvePosCreditBill(CooperativeMember $member, string $id): ?array
    {
        if ((string) $member->id !== $id) {
            return null;
        }

        return $this->posCreditBill($member);
    }

    protected function posCreditBill(CooperativeMember $member): ?array
    {
        $remaining = round((float) $member->outstanding_balance, 2);

        if ($remaining <= 0) {
            return null;
        }

        return [
            'id' => 'pos_credit:'.$member->id,
            'source' => 'pos_credit',
            'source_id' => (string) $member->id,
            'title' => 'Kredit Belanja POS',
            'amount' => $remaining,
            'paid_amount' => 0,
            'remaining_amount' => $remaining,
            'due_date' => null,
            'status' => 'OUTSTANDING',
            'payable' => true,
            'period' => null,
            'category' => 'pos_credit',
            'action_url' => '/member/transactions',
        ];
    }

    protected function pendingPaymentForInvoice(Request $request, CooperativeMember $member, CooperativeDuesInvoice $invoice, ?string $paymentMethod = null): CooperativePayment
    {
        $payment = $invoice->payments()
            ->where('status', 'PENDING')
            ->where('cooperative_member_id', $member->id)
            ->latest()
            ->first();

        if ($payment) {
            return $payment;
        }

        $amount = max((float) $invoice->amount - (float) $invoice->paid_amount, 0);

        return $invoice->payments()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $request->user()?->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod ?? 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 50);
    }

    protected function pendingLoanPaymentIntent(Request $request, CooperativeMember $member, string $id, string $channel): MemberPaymentIntent
    {
        $installment = LoanInstallment::query()
            ->with('loan.loanType')
            ->where('id', $id)
            ->whereHas('loan', fn ($query) => $query->where('cooperative_member_id', $member->id))
            ->firstOrFail();

        $remaining = round((float) $installment->amount_due - (float) $installment->amount_paid, 2);
        abort_if($remaining <= 0, 422, 'Cicilan pinjaman ini sudah lunas.');

        $intent = MemberPaymentIntent::query()
            ->where('cooperative_member_id', $member->id)
            ->where('payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
            ->where('payable_id', $installment->id)
            ->where('gateway_status', 'PENDING')
            ->latest()
            ->first();

        if ($intent) {
            return $intent;
        }

        return MemberPaymentIntent::query()->create([
            'user_id' => $request->user()?->id,
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $installment->id,
            'amount' => $remaining,
            'channel' => $channel,
            'gateway_status' => 'PENDING',
            'metadata' => [
                'description' => "Angsuran {$installment->loan?->loanType?->name} #{$installment->installment_no}",
                'loan_id' => $installment->loan_id,
                'installment_no' => $installment->installment_no,
            ],
            'expires_at' => now()->addDay(),
        ]);
    }

    protected function pendingPosCreditPaymentIntent(Request $request, CooperativeMember $member, string $id, string $channel): MemberPaymentIntent
    {
        abort_unless((string) $member->id === $id, 404, 'Bill tidak ditemukan.');

        $remaining = round((float) $member->outstanding_balance, 2);
        abort_if($remaining <= 0, 422, 'Kredit belanja POS sudah lunas.');

        $intent = MemberPaymentIntent::query()
            ->where('cooperative_member_id', $member->id)
            ->where('payable_type', MemberPaymentIntent::PAYABLE_POS_CREDIT)
            ->where('payable_id', $member->id)
            ->where('gateway_status', 'PENDING')
            ->latest()
            ->first();

        if ($intent) {
            return $intent;
        }

        return MemberPaymentIntent::query()->create([
            'user_id' => $request->user()?->id,
            'cooperative_member_id' => $member->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $member->id,
            'amount' => $remaining,
            'channel' => $channel,
            'gateway_status' => 'PENDING',
            'metadata' => [
                'description' => 'Pelunasan Kredit Belanja POS',
            ],
            'expires_at' => now()->addDay(),
        ]);
    }

    protected function formatPaymentIntent(MemberPaymentIntent $intent): array
    {
        return [
            'id' => $intent->id,
            'payable_type' => $intent->payable_type,
            'payable_id' => $intent->payable_id,
            'amount' => (float) $intent->amount,
            'channel' => $intent->channel,
            'gateway_provider' => $intent->gateway_provider,
            'gateway_reference' => $intent->gateway_reference,
            'gateway_status' => $intent->gateway_status,
            'settled_at' => $intent->settled_at?->toISOString(),
            'expires_at' => $intent->expires_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatewayPresentation(CooperativePayment $payment): array
    {
        $payload = $payment->gateway_payload;

        if (! is_array($payload)) {
            return [];
        }

        return is_array($payload['presentation'] ?? null) ? $payload['presentation'] : $payload;
    }

    private function qrisActionUrl(CooperativePayment $payment): ?string
    {
        $payload = is_array($payment->gateway_payload) ? $payment->gateway_payload : [];
        $actions = $payload['actions'] ?? [];

        if (! is_array($actions)) {
            return null;
        }

        foreach (['generate-qr-code-v2', 'generate-qr-code'] as $name) {
            foreach ($actions as $action) {
                if (is_array($action) && ($action['name'] ?? null) === $name && is_string($action['url'] ?? null)) {
                    return $action['url'];
                }
            }
        }

        return null;
    }

    private function assertAllowedQrisActionUrl(string $actionUrl): void
    {
        $parts = parse_url($actionUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = $parts['port'] ?? null;

        abort_unless($scheme === 'https', 404, 'QRIS pembayaran tidak tersedia.');
        abort_unless(in_array($host, $this->allowedMidtransHosts(), true), 404, 'QRIS pembayaran tidak tersedia.');
        abort_unless($port === null || (int) $port === 443, 404, 'QRIS pembayaran tidak tersedia.');
    }

    /**
     * @return array<int, string>
     */
    private function allowedMidtransHosts(): array
    {
        return [
            config('services.midtrans.is_production', false)
                ? 'api.midtrans.com'
                : 'api.sandbox.midtrans.com',
        ];
    }

    private function assertValidQrisImageResponse(ClientResponse $response): void
    {
        abort_if($response->redirect(), 502, 'Gagal mengambil QRIS dari Midtrans.');

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        abort_unless(in_array($contentType, self::QRIS_IMAGE_CONTENT_TYPES, true), 502, 'Gagal mengambil QRIS dari Midtrans.');

        $contentLength = $response->header('Content-Length');
        abort_if(is_numeric($contentLength) && (int) $contentLength > self::QRIS_IMAGE_MAX_BYTES, 502, 'Gagal mengambil QRIS dari Midtrans.');
        abort_if(strlen($response->body()) > self::QRIS_IMAGE_MAX_BYTES, 502, 'Gagal mengambil QRIS dari Midtrans.');
    }

    private function parseGatewayExpiry(CooperativePayment $payment): ?\Illuminate\Support\Carbon
    {
        $raw = $this->gatewayPresentation($payment)['expires_at'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
