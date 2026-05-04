<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\GenerateDuesRequest;
use App\Http\Requests\Cooperative\MarkDuesPaidRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\DuesGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeDuesController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CooperativeDuesInvoice::query()->with(['member', 'contributionType']);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Dues/Index', [
            'invoices' => $query->orderByDesc('period')->orderByDesc('id')->paginate(20)->withQueryString(),
            'contributionTypes' => CooperativeContributionType::query()->orderBy('name')->get(),
            'filters' => $request->only(['period', 'status']),
        ]);
    }

    public function generate(GenerateDuesRequest $request, DuesGenerationService $service): RedirectResponse
    {
        $created = $service->generateForPeriod($request->validated('period'));

        return back()->with('success', "{$created} dues invoices generated.");
    }

    public function markPaid(MarkDuesPaidRequest $request, CooperativePaymentService $paymentService): RedirectResponse
    {
        $paidCount = 0;

        $invoices = CooperativeDuesInvoice::query()
            ->whereIn('id', $request->validated('invoice_ids'))
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->get();

        foreach ($invoices as $invoice) {
            $remainingAmount = (float) $invoice->amount - (float) $invoice->paid_amount;

            if ($remainingAmount <= 0) {
                continue;
            }

            $payment = CooperativePayment::query()->create([
                'cooperative_member_id' => $invoice->cooperative_member_id,
                'cooperative_dues_invoice_id' => $invoice->id,
                'user_id' => $request->user()?->id,
                'amount' => $remainingAmount,
                'payment_method' => $request->validated('payment_method') ?: 'CASH',
                'paid_at' => $request->validated('paid_at'),
                'status' => 'APPROVED',
                'reference_no' => $request->validated('reference_no'),
                'notes' => $request->validated('notes') ?: 'Ditandai sudah membayar dari halaman iuran.',
            ]);

            $paymentService->approve($payment, $request->user());
            $paidCount++;
        }

        if ($paidCount === 0) {
            return back()->with('error', 'Tidak ada tagihan belum lunas yang dapat ditandai sudah membayar.');
        }

        return back()->with('success', "{$paidCount} tagihan ditandai sudah membayar.");
    }
}
