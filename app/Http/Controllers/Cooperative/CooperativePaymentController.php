<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativePaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CooperativePayment::class);

        $query = CooperativePayment::query()->with(['member', 'invoice.contributionType', 'contributionType']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Payments/Index', [
            'payments' => $query->orderByDesc('paid_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
            'contributionTypes' => $this->paymentContributionTypes()->get(),
            'filters' => $request->only(['status']),
            'canApprovePayments' => $request->user()?->hasRole('Admin Koperasi') ?? false,
        ]);
    }

    public function store(StoreCooperativePaymentRequest $request, CooperativePaymentService $service): RedirectResponse
    {
        $this->authorize('create', CooperativePayment::class);

        $data = $request->validated();

        if ($request->hasFile('proof')) {
            $data['proof_path'] = $request->file('proof')->store('cooperative/payment-proofs/admin', 'public');
        }

        $data['status'] = 'APPROVED';

        $payment = $service->record($data, $request->user());

        $service->approve($payment, $request->user());

        return back()->with('success', 'Pembayaran simpanan berhasil dicatat.');
    }

    public function approve(CooperativePayment $payment, CooperativePaymentService $service, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('Admin Koperasi'), 403);

        $this->authorize('approve', $payment);

        $service->approve($payment, $request->user());

        return back()->with('success', 'Pembayaran simpanan berhasil disetujui.');
    }

    private function paymentContributionTypes(): Builder
    {
        return CooperativeContributionType::query()
            ->where('is_active', true)
            ->whereIn('code', ['POKOK', 'SUKARELA'])
            ->orderBy('name');
    }
}
