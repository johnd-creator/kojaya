<?php

namespace App\Http\Controllers;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Exceptions\PaymentIntentConflictException;
use App\Http\Requests\Api\MarkMemberOnboardingStepRequest;
use App\Http\Requests\CompleteMemberOnboardingRequest;
use App\Http\Requests\Cooperative\RedeemRewardRequest;
use App\Http\Requests\MemberPaymentProofRequest;
use App\Http\Requests\StoreMemberLoanApplicationRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Http\Resources\MemberStoreAccountResource;
use App\Http\Resources\MemberStoreLedgerEntryResource;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\MemberPaymentIntent;
use App\Models\MemberStoreAccount;
use App\Models\PosTransaction;
use App\Models\Reward;
use App\Services\Cooperative\MemberAccessService;
use App\Services\Cooperative\MemberFinancialActivityService;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberOnboardingSubmitService;
use App\Services\Cooperative\MemberProfileCompletenessService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsSummaryService;
use App\Services\Integrations\LoanPaymentIntentService;
use App\Services\Integrations\PaymentGatewayService;
use App\Services\Integrations\PaymentIntentChargeService;
use App\Support\Money\MinorAmount;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberPortalController extends Controller
{
    public function dashboard(
        Request $request,
        PointService $pointService,
        MemberStatusJourneyService $journeyService,
        SavingsSummaryService $savingsSummary,
        MemberProfileCompletenessService $completenessService,
        MemberAccessService $memberAccessService,
    ): Response {
        $member = $this->memberOrAbort($request);
        $storeAccount = MemberStoreAccount::query()
            ->where('organization_id', $member->organization_id)
            ->where('cooperative_member_id', $member->id)
            ->first();
        $pointSummary = $pointService->balanceSummary($member);
        $savingSummary = $savingsSummary->summary($member);
        $memberAccess = $memberAccessService->for($member);
        $isActive = (bool) $memberAccess['is_active'];
        $isPendingReview = (bool) $memberAccess['is_pending_review'];
        $canPreviewFinancialSummary = (bool) $memberAccess['can_preview_financial_summary'];

        $onboardingCompleteness = $completenessService->summarize($member);

        $simpananPokokInvoice = null;
        $simpananPokokProgress = null;

        $pokokType = CooperativeContributionType::query()
            ->where('code', 'POKOK')
            ->where('is_active', true)
            ->first();

        if ($pokokType) {
            $existingPokok = CooperativeDuesInvoice::query()
                ->where('cooperative_member_id', $member->id)
                ->where('cooperative_contribution_type_id', $pokokType->id)
                ->oldest('id')
                ->first();

            if ($existingPokok) {
                $total = (float) $existingPokok->amount;
                $paid = (float) $existingPokok->paid_amount;
                $simpananPokokProgress = [
                    'amount' => $total,
                    'paid' => $paid,
                    'remaining' => $total - $paid,
                    'percent' => $total > 0 ? (int) round(($paid / $total) * 100) : 0,
                    'is_paid' => in_array($existingPokok->status, ['PAID'], true),
                ];

                if (! $simpananPokokProgress['is_paid']) {
                    $simpananPokokInvoice = [
                        'id' => $existingPokok->id,
                        'amount' => $total,
                        'paid_amount' => $paid,
                        'due_date' => $existingPokok->due_date,
                        'status' => $existingPokok->status,
                        'period' => $existingPokok->period,
                    ];
                }
            }
        }

        $simpananWajibPending = null;
        $simpananWajibProgress = null;
        $simpananWajibInvoice = null;
        if ($canPreviewFinancialSummary) {
            $wajibType = CooperativeContributionType::query()
                ->where('code', 'WAJIB')
                ->where('is_active', true)
                ->first();

            if ($wajibType) {
                $allWajibInvoices = $member->invoices()
                    ->where('cooperative_contribution_type_id', $wajibType->id)
                    ->get();

                $totalWajib = (float) $allWajibInvoices->sum('amount');
                $totalPaid = (float) $allWajibInvoices->sum('paid_amount');

                $simpananWajibProgress = [
                    'total_amount' => $totalWajib,
                    'total_paid' => $totalPaid,
                    'total_invoices' => $allWajibInvoices->count(),
                    'paid_invoices' => $allWajibInvoices->where('status', 'PAID')->count(),
                    'percent' => $totalWajib > 0 ? (int) round(($totalPaid / $totalWajib) * 100) : 0,
                ];

                $wajibInvoices = $allWajibInvoices->whereIn('status', ['UNPAID', 'PARTIAL']);

                if ($wajibInvoices->isNotEmpty()) {
                    $oldestPending = $wajibInvoices->first();
                    $simpananWajibPending = [
                        'count' => $wajibInvoices->count(),
                        'total_amount' => $totalWajib - $totalPaid,
                        'latest_due_date' => $oldestPending?->due_date,
                    ];
                    $simpananWajibInvoice = [
                        'id' => $oldestPending->id,
                        'amount' => (float) $oldestPending->amount,
                        'paid_amount' => (float) $oldestPending->paid_amount,
                        'due_date' => $oldestPending->due_date,
                    ];
                }
            }
        }

        $member->load(['organization', 'user.organization']);
        if ($member->user?->organization && $member->organization_id !== $member->user->organization_id) {
            $member->setRelation('organization', $member->user->organization);
        }

        return Inertia::render('Kojayaku/Dashboard', [
            'member' => $member,
            'is_active_member' => $isActive,
            'is_pending_review' => $isPendingReview,
            'can_access_financial_features' => $isActive,
            'can_preview_financial_summary' => $canPreviewFinancialSummary,
            'can_access_onboarding' => (bool) $memberAccess['can_access_onboarding'],
            'onboarding_completeness' => $onboardingCompleteness,
            'simpanan_pokok_invoice' => $simpananPokokInvoice,
            'simpanan_pokok_progress' => $simpananPokokProgress,
            'simpanan_wajib_pending' => $simpananWajibPending,
            'simpanan_wajib_progress' => $simpananWajibProgress,
            'simpanan_wajib_invoice' => $simpananWajibInvoice,
            'summary' => [
                'savings_balance' => $savingSummary['total_balance'],
                'active_loans' => $member->loans()->where('status', LoanStatus::Active)->count(),
                'loan_outstanding' => (float) $member->loans()->where('status', LoanStatus::Active)->sum('outstanding_amount'),
                'points_balance' => $pointSummary['total_points'],
                'member_tier' => $pointSummary['member_tier'],
                'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'unread_notifications' => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            'store_account' => $storeAccount
                ? (new MemberStoreAccountResource($storeAccount))->resolve()
                : null,
            'recentTransactions' => $canPreviewFinancialSummary ? $this->recentMemberActivities($member) : [],
            'recentLoans' => $canPreviewFinancialSummary ? Loan::query()
                ->with([
                    'loanType',
                    'installments' => fn ($query) => $query
                        ->whereIn('status', [
                            InstallmentStatus::Pending->value,
                            InstallmentStatus::Partial->value,
                            InstallmentStatus::Overdue->value,
                        ])
                        ->orderBy('due_date'),
                ])
                ->where('cooperative_member_id', $member->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Loan $loan): array => [
                    'id' => $loan->id,
                    'status' => $loan->status?->value ?? (string) $loan->status,
                    'outstanding_amount' => (float) $loan->outstanding_amount,
                    'loan_type' => $loan->loanType?->only('name'),
                    'next_installment' => ($installment = $loan->installments->first()) ? [
                        'id' => $installment->id,
                        'installment_no' => $installment->installment_no,
                        'due_date' => $installment->due_date?->toDateString(),
                        'amount_due' => (float) $installment->amount_due,
                        'amount_paid' => (float) $installment->amount_paid,
                        'remaining_amount' => max((float) $installment->amount_due - (float) $installment->amount_paid, 0),
                        'status' => $installment->status?->value ?? (string) $installment->status,
                    ] : null,
                ])
                ->values()
                ->all() : [],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMemberActivities(CooperativeMember $member): array
    {
        $posTransactions = PosTransaction::query()
            ->where('cooperative_member_id', $member->id)
            ->latest('sold_at')
            ->limit(5)
            ->get()
            ->map(fn (PosTransaction $transaction): array => [
                'id' => 'pos-'.$transaction->id,
                'type' => 'POS',
                'title' => $transaction->transaction_no,
                'subtitle' => 'Transaksi POS',
                'amount' => (float) $transaction->total_amount,
                'occurred_at' => $transaction->sold_at?->toIso8601String(),
            ]);

        $savingPayments = CooperativePayment::query()
            ->with(['invoice.contributionType', 'contributionType'])
            ->where('cooperative_member_id', $member->id)
            ->latest('paid_at')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (CooperativePayment $payment): array {
                $typeName = $payment->invoice?->contributionType?->name
                    ?: $payment->contributionType?->name
                    ?: 'Simpanan';
                $period = $payment->invoice?->period;

                return [
                    'id' => 'saving-'.$payment->id,
                    'type' => 'SAVINGS_PAYMENT',
                    'title' => 'Pembayaran '.$typeName,
                    'subtitle' => $period ? 'Iuran periode '.$period : 'Pembayaran simpanan',
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'occurred_at' => ($payment->paid_at ?: $payment->created_at)?->toIso8601String(),
                ];
            });

        return $posTransactions
            ->concat($savingPayments)
            ->sortByDesc('occurred_at')
            ->take(5)
            ->values()
            ->all();
    }

    public function storeAccount(Request $request): Response
    {
        $member = $this->memberOrAbort($request);
        $account = MemberStoreAccount::query()
            ->where('organization_id', $member->organization_id)
            ->where('cooperative_member_id', $member->id)
            ->first();

        $ledger = $account
            ? $account->ledgerEntries()
                ->with('actor')
                ->paginate(20)
                ->withQueryString()
            : null;

        return Inertia::render('Kojayaku/StoreAccount', [
            'account' => $account ? (new MemberStoreAccountResource($account))->resolve() : null,
            'ledger' => $ledger
                ? MemberStoreLedgerEntryResource::collection($ledger)->response()->getData(true)
                : ['data' => [], 'links' => [], 'meta' => []],
        ]);
    }

    public function onboarding(Request $request, MemberOnboardingService $service): Response
    {
        $member = $this->memberOrAbort($request);
        $member->loadMissing(['organization', 'user']);

        $validation = $member->validation_status ?: $member->status;
        $submitted = $member->onboarding_submitted_at !== null;
        $reviewState = $this->resolveOnboardingReviewState($validation, $submitted);

        return Inertia::render('Kojayaku/Onboarding', [
            'member' => $member,
            'onboarding' => $service->status($member),
            'submitted' => $submitted,
            'review_state' => $reviewState,
            'validation_status' => $validation,
            'options' => [
                'jenisKelamin' => [
                    ['value' => 'L', 'label' => 'Laki-laki'],
                    ['value' => 'P', 'label' => 'Perempuan'],
                ],
                'perusahaan' => [
                    ['value' => 'IP', 'label' => 'Indonesia Power'],
                    ['value' => 'CDB', 'label' => 'Cogindo DayaBersama'],
                    ['value' => 'KOP', 'label' => 'Koperasi'],
                ],
                'bank' => [
                    ['value' => 'BNI', 'label' => 'BNI'],
                    ['value' => 'BRI', 'label' => 'BRI'],
                    ['value' => 'Mandiri', 'label' => 'Mandiri'],
                ],
            ],
        ]);
    }

    public function submitOnboarding(
        CompleteMemberOnboardingRequest $request,
        MemberOnboardingSubmitService $service,
    ): RedirectResponse {
        $member = $this->memberOrAbort($request);

        $service->submit($member, $request->validated(), $request->user());

        return redirect()
            ->route('member.onboarding')
            ->with('success', 'Onboarding terkirim. Pengurus akan memvalidasi data Anda.');
    }

    public function markOnboardingStep(
        MarkMemberOnboardingStepRequest $request,
        MemberOnboardingService $service,
    ): RedirectResponse {
        $service->markStep($this->memberOrAbort($request), $request->validated('step'));

        return back()->with('success', 'Progress onboarding diperbarui.');
    }

    public function savings(
        Request $request,
        MemberStatusJourneyService $journeyService,
        SavingsSummaryService $savingsSummary,
    ): Response {
        $member = $this->memberOrAbort($request);

        $summary = $savingsSummary->summary($member);
        $wajibData = $this->wajibSavingsInvoiceData($member);

        return Inertia::render('Kojayaku/Savings', [
            'summary' => [
                'savings_balance' => $summary['total_balance'],
                'by_category' => $summary['by_category'],
                'uncategorized' => $summary['uncategorized'],
                'total_paid' => (float) $member->payments()->where('status', 'APPROVED')->sum('amount'),
                'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
            ],
            'entries' => $savingsSummary->ledgerQuery($member)->latest('posted_at')->paginate(12)->withQueryString(),
            'invoices' => $member->invoices()->with('contributionType')->latest('period')->paginate(10, ['*'], 'invoices')->withQueryString(),
            'payments' => $member->payments()->with('invoice.contributionType')->latest('paid_at')->paginate(10, ['*'], 'payments')->withQueryString(),
            'wajibInvoices' => $wajibData['invoices'],
            'wajibSummary' => $wajibData['summary'],
            'journey' => $journeyService->pendingManualPaymentJourney($member),
        ]);
    }

    /**
     * @return array{summary: array<string, mixed>, invoices: array<int, array<string, mixed>>}
     */
    private function wajibSavingsInvoiceData(CooperativeMember $member): array
    {
        $wajibType = CooperativeContributionType::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('code', 'WAJIB')
                    ->orWhere('category', 'WAJIB');
            })
            ->orderByRaw("CASE WHEN code = 'WAJIB' THEN 0 ELSE 1 END")
            ->first();

        if (! $wajibType) {
            return [
                'summary' => [
                    'total_invoices' => 0,
                    'paid_invoices' => 0,
                    'open_invoices' => 0,
                    'total_amount' => 0.0,
                    'paid_amount' => 0.0,
                    'outstanding_amount' => 0.0,
                ],
                'invoices' => [],
            ];
        }

        $invoices = $member->invoices()
            ->where('cooperative_contribution_type_id', $wajibType->id)
            ->orderByDesc('period')
            ->limit(24)
            ->get();

        $mapped = $invoices->map(function (CooperativeDuesInvoice $invoice): array {
            $amount = (float) $invoice->amount;
            $paidAmount = (float) $invoice->paid_amount;
            $remainingAmount = max($amount - $paidAmount, 0);

            return [
                'id' => $invoice->id,
                'period' => $invoice->period,
                'period_label' => $this->periodLabel($invoice->period),
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
                'status_label' => $this->invoiceStatusLabel($invoice->status),
                'is_paid' => $invoice->status === 'PAID',
            ];
        })->values()->all();

        $totalAmount = (float) $invoices->sum('amount');
        $paidAmount = (float) $invoices->sum('paid_amount');

        return [
            'summary' => [
                'total_invoices' => $invoices->count(),
                'paid_invoices' => $invoices->where('status', 'PAID')->count(),
                'open_invoices' => $invoices->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => max($totalAmount - $paidAmount, 0),
            ],
            'invoices' => $mapped,
        ];
    }

    private function periodLabel(string $period): string
    {
        $date = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return ($months[(int) $date->format('n')] ?? $date->format('F')).' '.$date->format('Y');
    }

    private function invoiceStatusLabel(string $status): string
    {
        return match ($status) {
            'PAID' => 'Sudah dibayar',
            'PARTIAL' => 'Dibayar sebagian',
            'UNPAID' => 'Belum dibayar',
            'VOID' => 'Dibatalkan',
            default => $status,
        };
    }

    public function loans(Request $request, MemberStatusJourneyService $journeyService): Response
    {
        $member = $this->memberOrAbort($request);

        return Inertia::render('Kojayaku/Loans', [
            'loans' => $member->loans()->with(['loanType', 'installments'])->latest()->paginate(12)->withQueryString(),
            'loanTypes' => LoanType::query()->where('is_active', true)->orderBy('name')->get(),
            'journey' => $journeyService->loanJourney(
                $member,
                hideAfterDisbursement: true,
                includeCompletionStep: false,
            ),
        ]);
    }

    public function applyLoan(
        StoreMemberLoanApplicationRequest $request,
        LoanServiceContract $loanService
    ): RedirectResponse {
        $member = $this->memberOrAbort($request);

        $loan = $loanService->apply([
            ...$request->validated(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
        ], $request->user());

        $loanReference = $loan->reference_no ?: (string) $loan->id;

        return redirect()->route('member.loans')
            ->with('success', "Pengajuan pinjaman {$loanReference} berhasil dikirim.");
    }

    public function points(Request $request, PointService $pointService): Response
    {
        $member = $this->memberOrAbort($request);

        return Inertia::render('Kojayaku/Points', [
            'summary' => $pointService->balanceSummary($member),
            'history' => $pointService->historyQuery($member)->paginate(15)->withQueryString(),
            'redemptions' => $member->rewardRedemptions()->with('reward')->latest('redeemed_at')->limit(5)->get(),
        ]);
    }

    public function rewards(
        Request $request,
        PointService $pointService,
        MemberStatusJourneyService $journeyService,
    ): Response {
        $member = $this->memberWithOrganizationOrAbort($request);

        return Inertia::render('Kojayaku/Rewards', [
            'summary' => $pointService->balanceSummary($member),
            'rewards' => Reward::query()
                ->where('organization_id', $member->organization_id)
                ->where('is_active', true)
                ->orderBy('points_required')
                ->paginate(12)
                ->withQueryString(),
            'redemptions' => $member->rewardRedemptions()->with('reward')->latest('redeemed_at')->paginate(10, ['*'], 'redemptions')->withQueryString(),
            'journey' => $journeyService->rewardJourney($member),
        ]);
    }

    public function redeemReward(
        RedeemRewardRequest $request,
        string $reward,
        PointService $pointService
    ): RedirectResponse {
        $member = $this->memberWithOrganizationOrAbort($request);

        /** @var Reward $rewardModel */
        $rewardModel = Reward::query()
            ->where('organization_id', $member->organization_id)
            ->findOrFail($reward);

        $pointService->redeem(
            member: $member,
            reward: $rewardModel,
            quantity: (int) $request->validated('quantity'),
            deliveryAddress: $request->validated('delivery_address'),
        );

        return back()->with('success', 'Reward berhasil ditukarkan.');
    }

    public function transactions(Request $request, MemberFinancialActivityService $activityService): Response
    {
        $member = $this->memberOrAbort($request);
        $activityData = $activityService->paginate($member, $request);

        return Inertia::render('Kojayaku/Transactions', [
            'transactions' => $activityData['transactions'],
            'summary' => $activityData['summary'],
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function profile(Request $request, MemberProfileCompletenessService $completeness): Response
    {
        $member = $this->memberOrAbort($request);
        $member->load(['user.socialAccounts', 'organization', 'validator']);

        return Inertia::render('Kojayaku/Profile', [
            'user' => $request->user(),
            'member' => $member,
            'completeness' => $completeness->summarize($member),
            'googleSsoEnabled' => (bool) config('services.google.sso_enabled', false),
        ]);
    }

    public function updateProfile(UpdateMemberPortalProfileRequest $request): RedirectResponse
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

        return back()->with('success', 'Profil anggota berhasil diperbarui.');
    }

    public function notifications(Request $request): Response
    {
        return Inertia::render('Kojayaku/Notifications', [
            'notifications' => $request->user()?->notifications()->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function uploadPaymentProof(MemberPaymentProofRequest $request): RedirectResponse
    {
        $member = $this->memberOrAbort($request);
        $invoice = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->findOrFail($request->validated('cooperative_dues_invoice_id'));

        $proofPath = $request->file('proof')->store('cooperative/payment-proofs/'.$member->id, 'public');

        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $invoice->cooperative_contribution_type_id,
            'user_id' => $request->user()?->id,
            'amount' => $request->validated('amount'),
            'payment_method' => $request->validated('payment_method'),
            'paid_at' => $request->validated('paid_at'),
            'status' => 'PENDING',
            'proof_path' => $proofPath,
            'reference_no' => $request->validated('reference_no'),
            'notes' => $request->validated('notes'),
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil dikirim. Pengurus akan memverifikasi dalam 1-3 hari kerja.');
    }

    /**
     * Create a direct Midtrans charge for a dues invoice using the chosen
     * channel. Returns the native payment artefact (QR string for QRIS, VA
     * number for bank transfer, checkout URL for e-wallet) so the member
     * portal renders the payment inline instead of opening the Snap modal.
     */
    public function createPaymentIntent(Request $request, PaymentGatewayService $gateway): JsonResponse
    {
        $member = $this->memberOrAbort($request);

        $data = $request->validate([
            'cooperative_dues_invoice_id' => ['required', 'integer'],
            'channel' => ['nullable', 'in:QRIS,VA,E_WALLET'],
        ]);

        $channel = strtoupper($data['channel'] ?? 'QRIS');

        $invoice = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->findOrFail($data['cooperative_dues_invoice_id']);

        $payment = $invoice->payments()
            ->where('status', 'PENDING')
            ->where('cooperative_member_id', $member->id)
            ->latest()
            ->first();

        if (! $payment) {
            $amount = max((float) $invoice->amount - (float) $invoice->paid_amount, 0);

            $payment = $invoice->payments()->create([
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $invoice->cooperative_contribution_type_id,
                'user_id' => $request->user()?->id,
                'amount' => $amount,
                'payment_method' => $channel,
                'paid_at' => now()->toDateString(),
                'status' => 'PENDING',
            ]);
        }

        try {
            $charge = $gateway->createCharge($payment, $channel);
        } catch (\RuntimeException $e) {
            $isInactiveChannel = str_contains($e->getMessage(), 'Payment channel is not activated');

            if ($isInactiveChannel && $channel !== 'VA') {
                report($e);

                try {
                    $charge = $gateway->createCharge($payment, 'VA');

                    return $this->paymentIntentResponse($payment, $invoice, $charge, [
                        'requested_channel' => $channel,
                        'fallback_reason' => 'MIDTRANS_CHANNEL_INACTIVE',
                    ]);
                } catch (\RuntimeException $fallbackException) {
                    report($fallbackException);

                    return $this->paymentGatewayErrorResponse($fallbackException, 'VA');
                }
            }

            report($e);

            return $this->paymentGatewayErrorResponse($e, $channel);
        }

        return $this->paymentIntentResponse($payment, $invoice, $charge);
    }

    public function createLoanPaymentIntent(
        Request $request,
        LoanPaymentIntentService $loanPaymentIntentService,
        PaymentIntentChargeService $chargeService,
    ): JsonResponse {
        $member = $this->memberOrAbort($request);

        $data = $request->validate([
            'loan_installment_id' => ['required', 'integer'],
            'channel' => ['nullable', 'in:QRIS,VA,E_WALLET'],
        ]);

        $channel = strtoupper((string) ($data['channel'] ?? 'QRIS'));
        $resolution = $loanPaymentIntentService->resolveOrCreate(
            member: $member,
            installmentId: (int) $data['loan_installment_id'],
            userId: $request->user()?->id,
            requestedChannel: $channel,
        );
        $intent = $resolution->intent->refresh();
        try {
            $charge = $chargeService->ensureCharge($intent);
        } catch (\RuntimeException $exception) {
            report($exception);

            return $this->paymentGatewayErrorResponse($exception, $channel);
        }

        if (in_array($charge['status'] ?? null, ['PREPARING', 'RECONCILIATION_REQUIRED'], true)) {
            throw PaymentIntentConflictException::loanReconciliationRequired(
                'Pembayaran sebelumnya sedang diproses atau perlu direkonsiliasi. Tidak dibuat tagihan kedua.'
            );
        }

        $charge['reused'] = ! $resolution->created;
        $charge['requested_channel'] = $channel;

        return response()->json([
            'data' => [
                'payment_intent' => [
                    'id' => $intent->id,
                    'amount' => MinorAmount::fromDecimal($intent->amount) / 100,
                    'channel' => $intent->channel,
                    'expires_at' => $intent->expires_at?->toISOString(),
                    'reused' => ! $resolution->created,
                    'requested_channel' => $channel,
                ],
                'charge' => $charge,
            ],
        ], 201);
    }

    public function loanPaymentIntentStatus(Request $request, MemberPaymentIntent $intent): JsonResponse
    {
        $member = $this->memberOrAbort($request);
        abort_unless(
            $intent->cooperative_member_id === $member->id
                && $intent->payable_type === MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            403,
        );

        $isPaid = $intent->gateway_status === 'PAID' || $intent->settled_at !== null;
        $isFailed = in_array($intent->gateway_status, ['FAILED', 'EXPIRED', 'CANCELLED', 'DENIED'], true);

        return response()->json([
            'data' => [
                'status' => $intent->gateway_status,
                'is_paid' => $isPaid,
                'is_failed' => $isFailed,
                'is_terminal' => $isPaid || $isFailed,
                'gateway_expires_at' => $intent->expires_at?->toISOString(),
            ],
        ]);
    }

    /**
     * @param  array{provider: string, reference: string, status: string, channel: string, amount: float, checkout_url: string|null, qr_image_url?: string|null, expires_at?: string|null, instructions?: array<string, mixed>, poll_after_seconds?: int}  $charge
     * @param  array<string, mixed>  $extra
     */
    private function paymentIntentResponse(CooperativePayment $payment, CooperativeDuesInvoice $invoice, array $charge, array $extra = []): JsonResponse
    {
        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => (float) $payment->amount,
                'provider' => $charge['provider'],
                'channel' => $charge['channel'],
                'checkout_url' => $charge['checkout_url'] ?? null,
                'qr_image_url' => $charge['qr_image_url'] ?? null,
                'instructions' => $charge['instructions'] ?? [],
                'gateway_reference' => $charge['reference'],
                'expires_at' => $charge['expires_at'] ?? null,
                'poll_after_seconds' => $charge['poll_after_seconds'] ?? 5,
                'status' => $charge['status'],
                ...$extra,
            ],
        ], 201);
    }

    private function paymentGatewayErrorResponse(\RuntimeException $e, string $channel): JsonResponse
    {
        $isInactiveChannel = str_contains($e->getMessage(), 'Payment channel is not activated');

        return response()->json([
            'message' => $isInactiveChannel
                ? "Kanal {$channel} belum aktif di akun sandbox Midtrans ini. Coba Virtual Account atau ubah MIDTRANS_VA_BANK ke bank sandbox yang tersedia."
                : 'Payment gateway sedang tidak tersedia. Status pembayaran akan direkonsiliasi sebelum percobaan berikutnya.',
            'error_code' => $isInactiveChannel ? 'MIDTRANS_CHANNEL_INACTIVE' : 'PAYMENT_GATEWAY_ERROR',
        ], $isInactiveChannel ? 503 : 422);
    }

    /**
     * Poll the status of a payment so the portal can detect settlement after
     * the member completes the Midtrans checkout. Exposes terminal failure
     * states so the frontend can stop polling once the charge is settled,
     * expired, or denied (instead of looping indefinitely on webhook miss).
     */
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
            ],
        ]);
    }

    private function parseGatewayExpiry(CooperativePayment $payment): ?\Illuminate\Support\Carbon
    {
        $raw = $payment->gateway_payload['expires_at'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function memberOrAbort(Request $request): CooperativeMember
    {
        $member = $request->user()?->cooperativeMember;
        abort_unless($member, 404, 'Anda belum terdaftar sebagai anggota koperasi.');

        return $member;
    }

    private function memberWithOrganizationOrAbort(Request $request): CooperativeMember
    {
        $member = $this->memberOrAbort($request);
        abort_if(blank($member->organization_id), 403, 'Anggota belum terdaftar pada unit koperasi.');

        return $member;
    }

    private function resolveOnboardingReviewState(string $validation, bool $submitted): string
    {
        if (! $submitted) {
            return 'draft';
        }

        return match ($validation) {
            \App\Models\CooperativeMember::VALIDATION_PENDING_REVIEW => 'review',
            \App\Models\CooperativeMember::VALIDATION_REVISION => 'revision',
            \App\Models\CooperativeMember::VALIDATION_REJECTED => 'rejected',
            \App\Models\CooperativeMember::VALIDATION_ACTIVE => 'approved',
            default => 'pending',
        };
    }
}
