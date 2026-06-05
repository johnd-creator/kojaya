<?php

namespace App\Http\Controllers;

use App\Contracts\Cooperative\LoanServiceContract;
use App\Enums\LoanStatus;
use App\Http\Requests\Api\MarkMemberOnboardingStepRequest;
use App\Http\Requests\Cooperative\RedeemRewardRequest;
use App\Http\Requests\StoreMemberLoanApplicationRequest;
use App\Http\Requests\UpdateMemberPortalProfileRequest;
use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\PosTransaction;
use App\Models\Reward;
use App\Services\Cooperative\MemberOnboardingService;
use App\Services\Cooperative\MemberStatusJourneyService;
use App\Services\Cooperative\PointService;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberPortalController extends Controller
{
    public function dashboard(
        Request $request,
        PointService $pointService,
        MemberOnboardingService $onboardingService,
        MemberStatusJourneyService $journeyService,
        SavingsSummaryService $savingsSummary,
    ): Response {
        $member = $this->memberOrAbort($request);
        $pointSummary = $pointService->balanceSummary($member);
        $savingSummary = $savingsSummary->summary($member);

        return Inertia::render('Kojayaku/Dashboard', [
            'member' => $member->load(['organization', 'user']),
            'summary' => [
                'savings_balance' => $savingSummary['total_balance'],
                'pending_invoices' => $member->invoices()->whereIn('status', ['UNPAID', 'PARTIAL'])->count(),
                'active_loans' => $member->loans()->where('status', LoanStatus::Active)->count(),
                'loan_outstanding' => (float) $member->loans()->where('status', LoanStatus::Active)->sum('outstanding_amount'),
                'points_balance' => $pointSummary['total_points'],
                'member_tier' => $pointSummary['member_tier'],
                'unread_notifications' => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            'onboarding' => $onboardingService->status($member),
            'journeys' => $journeyService->summary($member),
            'recentTransactions' => PosTransaction::query()
                ->with(['payments'])
                ->where('cooperative_member_id', $member->id)
                ->latest('sold_at')
                ->limit(5)
                ->get(),
            'recentLoans' => Loan::query()
                ->with('loanType')
                ->where('cooperative_member_id', $member->id)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function onboarding(Request $request, MemberOnboardingService $service): Response
    {
        $member = $this->memberOrAbort($request);

        return Inertia::render('Kojayaku/Onboarding', [
            'member' => $member->load('organization'),
            'onboarding' => $service->status($member),
        ]);
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
            'journey' => $journeyService->paymentJourney($member),
        ]);
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

        return Inertia::render('Kojayaku/Transactions', [
            'transactions' => PosTransaction::query()
                ->with(['items.product', 'payments'])
                ->where('cooperative_member_id', $member->id)
                ->latest('sold_at')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function profile(Request $request): Response
    {
        $member = $this->memberOrAbort($request);

        return Inertia::render('Kojayaku/Profile', [
            'user' => $request->user(),
            'member' => $member,
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

    private function memberOrAbort(Request $request): CooperativeMember
    {
        $member = $request->user()?->cooperativeMember;

        abort_unless($member, 403, 'Akun ini belum terhubung ke anggota koperasi.');

        return $member;
    }
}
