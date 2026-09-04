<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ProcessPosVoidRequest;
use App\Http\Requests\Cooperative\RequestVoidPosTransactionRequest;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosVoidController extends Controller
{
    public function __construct(private PosTransactionService $service) {}

    public function index(Request $request, OrganizationScopedQueryService $scopedQuery): Response
    {
        $requests = PosVoidRequest::query()
            ->with(['transaction.member', 'transaction.cashier', 'requester', 'approver'])
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        $scopedQuery->scopeVisibleTo($requests, $request->user());

        return Inertia::render('Cooperative/Pos/Void/Index', [
            'requests' => $requests->paginate(20)->withQueryString(),
        ]);
    }

    public function store(RequestVoidPosTransactionRequest $request, string $transaction, OrganizationScopedQueryService $scopedQuery): RedirectResponse
    {
        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(PosTransaction::class, $request->user(), $transaction);

        $this->service->requestVoid($transactionModel, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Pengajuan void dikirim, menunggu persetujuan.');
    }

    public function process(ProcessPosVoidRequest $request, string $voidRequest, OrganizationScopedQueryService $scopedQuery): RedirectResponse
    {
        /** @var PosVoidRequest $voidRequestModel */
        $voidRequestModel = $scopedQuery->resolveVisible(PosVoidRequest::class, $request->user(), $voidRequest);

        $decision = $request->validated('decision');

        if ($decision === 'APPROVE') {
            $this->service->approveVoid($voidRequestModel, $request->user());
            $message = 'Transaksi berhasil di-void.';
        } else {
            $this->service->rejectVoid($voidRequestModel, $request->user(), $request->validated('rejection_reason'));
            $message = 'Pengajuan void ditolak.';
        }

        return back()->with('success', $message);
    }
}
