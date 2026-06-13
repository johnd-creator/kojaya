<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ProcessPosVoidRequest;
use App\Http\Requests\Cooperative\RequestVoidPosTransactionRequest;
use App\Models\PosVoidRequest;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosVoidController extends Controller
{
    public function __construct(private PosTransactionService $service) {}

    public function index(): Response
    {
        $requests = PosVoidRequest::query()
            ->with(['transaction.member', 'transaction.cashier', 'requester', 'approver'])
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Cooperative/Pos/Void/Index', [
            'requests' => $requests,
        ]);
    }

    public function store(RequestVoidPosTransactionRequest $request, int $transactionId): RedirectResponse
    {
        $transaction = \App\Models\PosTransaction::query()->findOrFail($transactionId);
        $this->service->requestVoid($transaction, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Pengajuan void dikirim, menunggu persetujuan.');
    }

    public function process(ProcessPosVoidRequest $request, PosVoidRequest $voidRequest): RedirectResponse
    {
        $decision = $request->validated('decision');

        if ($decision === 'APPROVE') {
            $this->service->approveVoid($voidRequest, $request->user());
            $message = 'Transaksi berhasil di-void.';
        } else {
            $this->service->rejectVoid($voidRequest, $request->user(), $request->validated('rejection_reason'));
            $message = 'Pengajuan void ditolak.';
        }

        return back()->with('success', $message);
    }
}
