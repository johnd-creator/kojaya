<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosMemberCreditPaymentRequest;
use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberCreditService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosMemberCreditController extends Controller
{
    public function __construct(private MemberCreditService $service) {}

    public function create(CooperativeMember $member): Response
    {
        $member->load(['creditPayments' => fn ($q) => $q->orderByDesc('paid_at')->limit(50)]);

        return Inertia::render('Cooperative/Pos/Credit/Pay', [
            'member' => $member,
            'available_credit' => $member->availableCredit(),
            'outstanding_balance' => (float) $member->outstanding_balance,
        ]);
    }

    public function store(
        StorePosMemberCreditPaymentRequest $request,
        CooperativeMember $member,
    ): RedirectResponse {
        $this->service->recordPayment(
            $member,
            (float) $request->validated('amount'),
            $request->user(),
            $request->validated('reference_no'),
            $request->validated('notes'),
            $request->validated('paid_at'),
        );

        return back()->with('success', 'Pembayaran kredit anggota berhasil dicatat.');
    }
}
