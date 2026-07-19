<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ResetDelegatePinRequest;
use App\Http\Requests\Cooperative\StoreDelegateRequest;
use App\Http\Requests\Cooperative\StoreTransferFundingRequest;
use App\Http\Requests\Cooperative\UpdateDelegateRequest;
use App\Http\Resources\MemberStoreAccountResource;
use App\Http\Resources\MemberStoreDelegateResource;
use App\Http\Resources\MemberStoreFundingRequestResource;
use App\Http\Resources\MemberStoreLedgerEntryResource;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditFundingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MemberStoreCreditApiController extends Controller
{
    public function __construct(
        private StoreCreditFundingService $funding,
        private StoreCreditDelegateService $delegateService,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $account = $this->resolveOwnAccount($request);

        return (new MemberStoreAccountResource($account))
            ->response()
            ->setStatusCode(200);
    }

    public function ledger(Request $request): AnonymousResourceCollection
    {
        $account = $this->resolveOwnAccount($request);

        $entries = $account->ledgerEntries()
            ->with(['actor', 'delegate'])
            ->paginate(min((int) $request->integer('per_page', 15), 100));

        return MemberStoreLedgerEntryResource::collection($entries);
    }

    public function delegates(Request $request): AnonymousResourceCollection
    {
        $account = $this->resolveOwnAccount($request);

        return MemberStoreDelegateResource::collection($account->delegates()->get());
    }

    public function storeDelegate(StoreDelegateRequest $request): JsonResponse
    {
        $account = $this->resolveOwnAccount($request);
        $delegate = $this->delegateService->create($account, $request->validated(), $request->user());

        return (new MemberStoreDelegateResource($delegate))
            ->response()
            ->setStatusCode(201);
    }

    public function updateDelegate(UpdateDelegateRequest $request, MemberStoreDelegate $delegate): JsonResponse
    {
        $account = $this->resolveOwnAccount($request);
        $this->ensureDelegateOwned($delegate, $account);

        return (new MemberStoreDelegateResource(
            $this->delegateService->update($delegate, $request->validated(), $request->user())
        ))->response();
    }

    public function revokeDelegate(Request $request, MemberStoreDelegate $delegate): Response
    {
        $account = $this->resolveOwnAccount($request);
        $this->ensureDelegateOwned($delegate, $account);
        $this->delegateService->revoke($delegate, $request->user());

        return response()->noContent();
    }

    public function resetDelegatePin(ResetDelegatePinRequest $request, MemberStoreDelegate $delegate): Response
    {
        $account = $this->resolveOwnAccount($request);
        $this->ensureDelegateOwned($delegate, $account);
        $this->delegateService->resetPin($delegate, $request->string('pin')->toString(), $request->user());

        return response()->noContent();
    }

    public function submitTransfer(StoreTransferFundingRequest $request): JsonResponse
    {
        $account = $this->resolveOwnAccount($request);
        $funding = $this->funding->submitTransferFunding(
            account: $account,
            amount: (int) $request->input('amount'),
            submitter: $request->user(),
            bankReference: $request->string('bank_reference')->toString() ?: null,
            proof: $request->file('proof_file'),
        );

        return (new MemberStoreFundingRequestResource($funding))
            ->response()
            ->setStatusCode(201);
    }

    public function fundingRequests(Request $request): AnonymousResourceCollection
    {
        $account = $this->resolveOwnAccount($request);

        return MemberStoreFundingRequestResource::collection(
            $account->fundingRequests()->latest()->paginate(min((int) $request->integer('per_page', 15), 100))
        );
    }

    private function resolveOwnAccount(Request $request): MemberStoreAccount
    {
        $member = $request->user()?->cooperativeMember()->active()->first();
        abort_unless($member !== null, 403, 'Akun belum terhubung dengan anggota koperasi aktif.');

        $account = MemberStoreAccount::query()
            ->where('organization_id', $member->organization_id)
            ->where('cooperative_member_id', $member->id)
            ->first();

        abort_unless($account !== null, 404, 'Akun saldo toko belum tersedia.');

        return $account;
    }

    private function ensureDelegateOwned(MemberStoreDelegate $delegate, MemberStoreAccount $account): void
    {
        abort_if($delegate->account_id !== $account->id, 404, 'Delegate tidak ditemukan pada akun ini.');
    }
}
