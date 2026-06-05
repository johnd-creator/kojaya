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
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeDuesController extends Controller
{
    public function index(Request $request, DuesGenerationService $duesGenerationService): Response
    {
        $period = $this->periodFromRequest($request);

        $duesGenerationService->generateForPeriod($period);

        $query = CooperativeDuesInvoice::query()->with(['member', 'contributionType']);
        $status = $request->input('status', '');

        $query->where('period', $period);

        if ($status === 'OPEN') {
            $query->whereIn('status', ['UNPAID', 'PARTIAL']);
        } elseif ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('member_search')) {
            $search = $request->string('member_search')->toString();
            $query->whereHas('member', function ($memberQuery) use ($search): void {
                $memberQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%")
                    ->orWhere('no_anggota', 'like', "%{$search}%")
                    ->orWhere('nama_anggota', 'like', "%{$search}%");
            });
        }

        if ($request->filled('contribution_type_id')) {
            $query->where('cooperative_contribution_type_id', $request->input('contribution_type_id'));
        }

        if ($request->filled('category')) {
            $query->whereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category')));
        }

        return Inertia::render('Cooperative/Dues/Index', [
            'invoices' => $query->orderByDesc('period')->orderByDesc('id')->paginate(20)->withQueryString(),
            'contributionTypes' => CooperativeContributionType::query()->orderBy('name')->get(),
            'categories' => CooperativeContributionType::query()
                ->where('is_active', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                ...$request->only(['period', 'member_search', 'contribution_type_id', 'category']),
                'period' => $period,
                'status' => $status,
            ],
            'canResetPaidDues' => $request->user()?->hasRole('System Admin') ?? false,
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

    public function markUnpaid(CooperativeDuesInvoice $invoice, Request $request, CooperativePaymentService $paymentService): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('System Admin'), 403);

        if ($invoice->status !== 'PAID') {
            return back()->with('error', 'Hanya tagihan berstatus sudah bayar yang dapat dikembalikan menjadi belum bayar.');
        }

        $voidedPayments = $paymentService->voidDuesInvoicePayments($invoice, $request->user());

        if ($voidedPayments === 0) {
            return back()->with('error', 'Tagihan tidak memiliki pembayaran aktif yang dapat dibatalkan.');
        }

        return back()->with('success', 'Status tagihan dikembalikan menjadi belum bayar.');
    }

    private function periodFromRequest(Request $request): string
    {
        $period = $request->input('period');

        if (is_string($period) && preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            return $period;
        }

        return CarbonImmutable::now()->format('Y-m');
    }
}
