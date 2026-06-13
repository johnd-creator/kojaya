<?php

namespace App\Http\Controllers;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\LoanStatus;
use App\Http\Requests\Api\MarkMemberOnboardingStepRequest;
use App\Http\Requests\CompleteMemberOnboardingRequest;
use App\Http\Requests\Cooperative\RedeemRewardRequest;
use App\Http\Requests\MemberPaymentProofRequest;
use App\Http\Requests\StoreMemberLoanApplicationRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\PosTransaction;
use App\Models\Reward;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberOnboardingSubmitService;
use App\Services\Cooperative\MemberProfileCompletenessService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsSummaryService;
use Carbon\CarbonImmutable;
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
        DuesGenerationService $duesGenerationService,
        MemberProfileCompletenessService $completenessService,
    ): Response {
        $member = $this->memberOrAbort($request);
        $pointSummary = $pointService->balanceSummary($member);
        $savingSummary = $savingsSummary->summary($member);
        $isActive = ($member->validation_status ?: $member->status) === CooperativeMember::VALIDATION_ACTIVE;
        $isPendingReview = $member->validation_status === CooperativeMember::VALIDATION_PENDING_REVIEW
            && $member->onboarding_submitted_at !== null;

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

            if (! $existingPokok) {
                try {
                    $existingPokok = $duesGenerationService->ensureOneTimeInvoice($member, 'POKOK');
                } catch (\Exception $e) {
                    // Invoice can't be created yet (e.g. period locked), skip silently
                }
            }

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
        if ($isActive || $isPendingReview) {
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

        return Inertia::render('Kojayaku/Dashboard', [
            'member' => $member->load(['organization', 'user']),
            'is_active_member' => $isActive || $isPendingReview,
            'is_pending_review' => $isPendingReview,
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
            'recentTransactions' => ($isActive || $isPendingReview) ? PosTransaction::query()
                ->with(['payments'])
                ->where('cooperative_member_id', $member->id)
                ->latest('sold_at')
                ->limit(5)
                ->get() : [],
            'recentLoans' => ($isActive || $isPendingReview) ? Loan::query()
                ->with('loanType')
                ->where('cooperative_member_id', $member->id)
                ->latest()
                ->limit(5)
                ->get() : [],
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

        return back()->with('success', 'Onboarding terkirim. Pengurus akan memvalidasi data Anda.');
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
        DuesGenerationService $duesGenerationService,
    ): Response {
        $member = $this->memberOrAbort($request);
        $currentPeriod = CarbonImmutable::now()->format('Y-m');

        try {
            $duesGenerationService->generateForPeriod($currentPeriod);
        } catch (\Throwable) {
            // If the period is locked, keep the member page read-only.
        }

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
            'journey' => $journeyService->paymentJourney($member),
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

    public function loans(Request $request, MemberOnboardingService $onboardingService, MemberStatusJourneyService $journeyService): Response
    {
        $member = $this->memberOrAbort($request);
        $onboardingService->markStep($member, 'loans');

        return Inertia::render('Kojayaku/Loans', [
            'loans' => $member->loans()->with(['loanType', 'installments'])->latest()->paginate(12)->withQueryString(),
            'loanTypes' => LoanType::query()->where('is_active', true)->orderBy('name')->get(),
            'journey' => $journeyService->loanJourney($member),
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
        MemberOnboardingService $onboardingService,
        MemberStatusJourneyService $journeyService,
    ): Response {
        $member = $this->memberOrAbort($request);
        $onboardingService->markStep($member, 'rewards');

        return Inertia::render('Kojayaku/Rewards', [
            'summary' => $pointService->balanceSummary($member),
            'rewards' => Reward::query()
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
        Reward $reward,
        PointService $pointService
    ): RedirectResponse {
        $member = $this->memberOrAbort($request);

        $pointService->redeem(
            member: $member,
            reward: $reward,
            quantity: (int) $request->validated('quantity'),
            deliveryAddress: $request->validated('delivery_address'),
        );

        return back()->with('success', 'Reward berhasil ditukarkan.');
    }

    public function transactions(Request $request): Response
    {
        $member = $this->memberOrAbort($request);

        $query = PosTransaction::query()
            ->with(['items.product', 'payments'])
            ->where('cooperative_member_id', $member->id);

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->input('date_to'));
        }

        $summaryBase = PosTransaction::query()
            ->where('cooperative_member_id', $member->id);

        if ($request->filled('date_from')) {
            $summaryBase->whereDate('sold_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $summaryBase->whereDate('sold_at', '<=', $request->input('date_to'));
        }

        $summaryQuery = clone $summaryBase;

        return Inertia::render('Kojayaku/Transactions', [
            'transactions' => $query->latest('sold_at')->paginate(12)->withQueryString(),
            'summary' => [
                'total_transactions' => $summaryQuery->count(),
                'total_amount' => (float) $summaryBase->clone()->sum('total_amount'),
                'total_items' => (int) $summaryBase->clone()->join('pos_transaction_items', 'pos_transactions.id', '=', 'pos_transaction_items.pos_transaction_id')->sum('quantity'),
                'last_transaction_at' => $summaryBase->clone()->latest('sold_at')->value('sold_at'),
            ],
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

    private function memberOrAbort(Request $request): CooperativeMember
    {
        $member = $request->user()?->cooperativeMember;
        abort_unless($member, 404, 'Anda belum terdaftar sebagai anggota koperasi.');

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
