<?php

namespace App\Http\Controllers\Cooperative;

use App\Enums\MemberStoreFundingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\AdjustStoreCreditRequest;
use App\Http\Requests\Cooperative\ChangeStoreCreditLimitRequest;
use App\Http\Requests\Cooperative\ProcessTransferFundingRequest;
use App\Http\Requests\Cooperative\StoreCashFundingRequest;
use App\Http\Requests\Cooperative\StoreDelegateRequest;
use App\Http\Requests\Cooperative\StoreStoreCreditAccountRequest;
use App\Http\Requests\Cooperative\StoreTransferFundingRequest;
use App\Http\Requests\Cooperative\UpdateDelegateRequest;
use App\Http\Resources\MemberStoreAccountResource;
use App\Http\Resources\MemberStoreDelegateResource;
use App\Http\Resources\MemberStoreFundingRequestResource;
use App\Http\Resources\MemberStoreLedgerEntryResource;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditFundingService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Services\Cooperative\StoreCreditReportService;
use App\Support\MemberStoreAccountContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class MemberStoreCreditController extends Controller
{
    public function __construct(
        private StoreCreditLedgerService $ledger,
        private StoreCreditFundingService $funding,
        private StoreCreditDelegateService $delegateService,
        private StoreCreditReportService $reports,
        private OrganizationScopeService $scope,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MemberStoreAccount::class);

        $query = MemberStoreAccount::query()->with('member');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->whereHas('member', function ($memberQuery) use ($search): void {
                $memberQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%");
            });
        }

        match ($request->string('filter')->toString()) {
            'positive' => $query->where('balance', '>', 0),
            'negative' => $query->where('balance', '<', 0),
            'zero' => $query->where('balance', 0),
            'suspended' => $query->where('status', 'suspended'),
            default => null,
        };

        /** @var LengthAwarePaginator<int, MemberStoreAccount> $accounts */
        $accounts = $this->scope->scopeVisibleTo($query, $request->user())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $eligibleMembers = collect();
        if ($request->user()->can('manage_store_credit')) {
            $existingMemberIds = MemberStoreAccount::query()
                ->where('organization_id', $request->user()->organization_id)
                ->pluck('cooperative_member_id');

            $eligibleMembers = tap(CooperativeMember::query()->active(), fn ($memberQuery) => $this->scope->scopeVisibleTo($memberQuery, $request->user()))
                ->whereNotIn('id', $existingMemberIds)
                ->orderBy('name')
                ->get(['id', 'member_no', 'name']);
        }

        return Inertia::render('Cooperative/StoreCredit/Index', [
            'accounts' => MemberStoreAccountResource::collection($accounts)->response()->getData(true),
            'filters' => $request->only(['q', 'filter']),
            'eligibleMembers' => $eligibleMembers,
            'canManage' => $request->user()->can('manage_store_credit'),
        ]);
    }

    public function show(Request $request, MemberStoreAccount $account): Response
    {
        $this->authorize('view', $account);

        $entries = $account->ledgerEntries()
            ->with(['actor', 'delegate'])
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Cooperative/StoreCredit/Show', [
            'account' => (new MemberStoreAccountResource($account->loadMissing('member')))->resolve(),
            'ledger' => MemberStoreLedgerEntryResource::collection($entries)->response()->getData(true),
            'delegates' => MemberStoreDelegateResource::collection($account->delegates()->get())->resolve(),
        ]);
    }

    public function store(StoreStoreCreditAccountRequest $request): RedirectResponse
    {
        $this->authorize('create', MemberStoreAccount::class);

        $memberQuery = CooperativeMember::query()->whereKey($request->input('cooperative_member_id'));
        $this->scope->scopeVisibleTo($memberQuery, $request->user());
        $member = $memberQuery->firstOrFail();

        $account = $this->ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: (int) ($request->input('credit_limit') ?? 0),
            openingBalance: (int) ($request->input('opening_balance') ?? 0),
            openedBy: $request->user(),
            reason: $request->string('reason')->toString() ?: null,
        ));

        return redirect()->route('cooperative.store-credit.show', $account)
            ->with('success', 'Akun saldo toko anggota dibuka.');
    }

    public function suspend(Request $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('suspend', $account);
        $this->ledger->suspend($account, $request->user(), $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Akun ditangguhkan.');
    }

    public function reactivate(Request $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('suspend', $account);
        $this->ledger->reactivate($account, $request->user(), $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Akun diaktifkan kembali.');
    }

    public function close(Request $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('manage', $account);
        $this->ledger->close($account, $request->user(), $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Akun ditutup.');
    }

    public function changeLimit(ChangeStoreCreditLimitRequest $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('manageLimit', $account);
        $this->ledger->changeCreditLimit(
            account: $account,
            newLimit: (int) $request->input('credit_limit'),
            actor: $request->user(),
            reason: $request->string('reason')->toString(),
            overrideBelowDebt: (bool) $request->boolean('override_below_debt'),
        );

        return back()->with('success', 'Limit kredit diperbarui.');
    }

    public function adjust(AdjustStoreCreditRequest $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('adjust', $account);

        $effect = \App\Enums\MemberStoreLedgerEffect::from((string) $request->input('effect'));
        $this->ledger->adjust(
            account: $account,
            amount: (int) $request->input('amount'),
            effect: $effect,
            actor: $request->user(),
            reason: $request->string('reason')->toString(),
        );

        return back()->with('success', 'Penyesuaian saldo diposting.');
    }

    public function cashFund(StoreCashFundingRequest $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('cashFund', $account);
        $this->funding->submitCashFunding(
            account: $account,
            amount: (int) $request->input('amount'),
            cashier: $request->user(),
            referenceNo: $request->string('reference_no')->toString() ?: null,
            idempotencyKey: $this->stableIdempotencyKey($request),
        );

        return back()->with('success', 'Setoran tunai diposting.');
    }

    public function transferIndex(Request $request): Response
    {
        $this->authorize('viewAny', MemberStoreFundingRequest::class);

        $query = MemberStoreFundingRequest::query()
            ->where('method', 'transfer')
            ->with(['account.member', 'submitter', 'reviewer']);

        if ($request->string('status')->toString() === 'pending') {
            $query->where('status', MemberStoreFundingStatus::Pending->value);
        }

        $transfers = $this->scope->scopeVisibleTo($query, $request->user())
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Cooperative/StoreCredit/Transfers', [
            'transfers' => MemberStoreFundingRequestResource::collection($transfers)->response()->getData(true),
            'filters' => $request->only(['status']),
        ]);
    }

    public function processTransfer(ProcessTransferFundingRequest $request, MemberStoreFundingRequest $funding): RedirectResponse
    {
        $decision = $request->string('decision')->toString();

        if ($decision === 'approve') {
            $this->authorize('approve', $funding);
            $this->funding->approveTransfer($funding, $request->user());
        } else {
            $this->authorize('reject', $funding);
            $this->funding->rejectTransfer($funding, $request->user(), $request->string('rejection_reason')->toString() ?: null);
        }

        return back()->with('success', 'Setoran transfer diproses.');
    }

    public function downloadProof(Request $request, MemberStoreFundingRequest $funding): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewProof', $funding);

        $response = $this->funding->downloadProofResponse($funding);

        if ($response === null) {
            abort(404, 'Bukti setoran tidak tersedia.');
        }

        return $response;
    }

    public function submitTransfer(StoreTransferFundingRequest $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('view', $account);
        $this->funding->submitTransferFunding(
            account: $account,
            amount: (int) $request->input('amount'),
            submitter: $request->user(),
            bankReference: $request->string('bank_reference')->toString() ?: null,
            proof: $request->file('proof_file'),
            idempotencyKey: $this->stableIdempotencyKey($request),
        );

        return back()->with('success', 'Setoran transfer diajukan, menunggu verifikasi.');
    }

    public function storeDelegate(StoreDelegateRequest $request, MemberStoreAccount $account): RedirectResponse
    {
        $this->authorize('manage', $account);
        $this->delegateService->create($account, $request->validated(), $request->user());

        return back()->with('success', 'Delegate ditambahkan.');
    }

    public function updateDelegate(UpdateDelegateRequest $request, MemberStoreAccount $account, MemberStoreDelegate $delegate): RedirectResponse
    {
        $this->authorize('manage', $account);
        $this->ensureDelegateBelongsToAccount($delegate, $account);
        $this->delegateService->update($delegate, $request->validated(), $request->user());

        return back()->with('success', 'Delegate diperbarui.');
    }

    public function revokeDelegate(Request $request, MemberStoreAccount $account, MemberStoreDelegate $delegate): RedirectResponse
    {
        $this->authorize('manage', $account);
        $this->ensureDelegateBelongsToAccount($delegate, $account);
        $this->delegateService->revoke($delegate, $request->user());

        return back()->with('success', 'Delegate dicabut.');
    }

    private function ensureDelegateBelongsToAccount(MemberStoreDelegate $delegate, MemberStoreAccount $account): void
    {
        abort_if($delegate->account_id !== $account->id, 404, 'Delegate tidak ditemukan pada akun ini.');
    }

    private function stableIdempotencyKey(Request $request): ?string
    {
        $key = $request->headers->get('Idempotency-Key');

        if ($key === null || $key === '') {
            return null;
        }

        $submitted = $request->input('idempotency_key');
        if (is_string($submitted) && $submitted !== '') {
            return $submitted;
        }

        return $key;
    }

    public function report(Request $request): Response
    {
        $this->authorize('report', MemberStoreAccount::class);

        $organizationId = $this->scope->visibilityFor($request->user(), 'view_store_credit_all')->organizationId;

        return Inertia::render('Cooperative/StoreCredit/Report', [
            'summary' => $this->reports->summary((string) $organizationId, $request->only(['utilization_threshold'])),
        ]);
    }
}
