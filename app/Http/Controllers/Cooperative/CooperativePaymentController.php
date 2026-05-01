<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativePaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CooperativePayment::query()->with(['member', 'invoice.contributionType']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Payments/Index', [
            'payments' => $query->orderByDesc('paid_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
            'invoices' => CooperativeDuesInvoice::query()->with(['member', 'contributionType'])->whereIn('status', ['UNPAID', 'PARTIAL'])->orderByDesc('period')->get(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(StoreCooperativePaymentRequest $request, CooperativePaymentService $service): RedirectResponse
    {
        $payment = $service->record($request->validated(), $request->user());

        if ($payment->status === 'APPROVED') {
            $service->approve($payment, $request->user());
        }

        return back()->with('success', 'Cooperative payment recorded successfully.');
    }

    public function approve(CooperativePayment $payment, CooperativePaymentService $service, Request $request): RedirectResponse
    {
        $service->approve($payment, $request->user());

        return back()->with('success', 'Cooperative payment approved successfully.');
    }
}
