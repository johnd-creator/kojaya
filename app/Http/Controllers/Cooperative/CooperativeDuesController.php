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
use Illuminate\Database\Eloquent\Builder;
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

        $query = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->with(['member', 'contributionType']);
        $status = $request->input('status', '');

        $query->where('period', $period);

        if ($status === 'OPEN') {
            $query->whereIn('status', ['UNPAID', 'PARTIAL']);
        } elseif ($request->filled('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('member_id')) {
            $query->where('cooperative_member_id', $request->input('member_id'));
        }

        if ($request->filled('member_search')) {
            $query->whereHas('member', fn (Builder $memberQuery) => $this->applyMemberSearch(
                $memberQuery,
                $request->string('member_search')->toString(),
            ));
        }

        if ($request->filled('contribution_type_id')) {
            $query->where('cooperative_contribution_type_id', $request->input('contribution_type_id'));
        }

        if ($request->filled('category')) {
            $query->whereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category')));
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        $aggregate = (clone $query)->selectRaw(
            'COUNT(*) as total_invoices,
             COALESCE(SUM(amount), 0) as total_nominal,
             COALESCE(SUM(paid_amount), 0) as total_paid,
             COALESCE(SUM(CASE WHEN amount - paid_amount > 0 THEN amount - paid_amount ELSE 0 END), 0) as total_outstanding,
             SUM(CASE WHEN status IN (\'PAID\', \'VOID\') THEN 1 ELSE 0 END) as paid_count'
        )->first();

        return Inertia::render('Cooperative/Dues/Index', [
            'invoices' => $query->orderByDesc('period')->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'stats' => [
                'total_invoices' => (int) ($aggregate->total_invoices ?? 0),
                'total_nominal' => (float) ($aggregate->total_nominal ?? 0),
                'total_paid' => (float) ($aggregate->total_paid ?? 0),
                'total_outstanding' => (float) ($aggregate->total_outstanding ?? 0),
                'paid_count' => (int) ($aggregate->paid_count ?? 0),
            ],
            'contributionTypes' => CooperativeContributionType::query()->orderBy('name')->get(),
            'categories' => CooperativeContributionType::query()
                ->where('is_active', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                ...$request->only(['period', 'member_id', 'member_search', 'contribution_type_id', 'category']),
                'per_page' => $perPage,
                'period' => $period,
                'status' => $status,
            ],
            'canResetPaidDues' => $this->canResetPaidDues($request),
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
        $requestedAmount = $request->validated('amount');

        $invoices = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->whereIn('id', $request->validated('invoice_ids'))
            ->whereIn('status', ['UNPAID', 'PARTIAL'])
            ->get();

        foreach ($invoices as $invoice) {
            $remainingAmount = (float) $invoice->amount - (float) $invoice->paid_amount;

            if ($remainingAmount <= 0) {
                continue;
            }

            $paymentAmount = $requestedAmount === null
                ? $remainingAmount
                : min((float) $requestedAmount, $remainingAmount);

            $payment = CooperativePayment::query()->create([
                'cooperative_member_id' => $invoice->cooperative_member_id,
                'cooperative_dues_invoice_id' => $invoice->id,
                'user_id' => $request->user()?->id,
                'amount' => $paymentAmount,
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
        abort_unless($this->canResetPaidDues($request), 403);

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

    private function canResetPaidDues(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('manage_cooperative_dues')
            && $user->can('manage_cooperative_settings')
            && $user->can('view_user_all');
    }

    private function applyMemberSearch(Builder $query, string $search): void
    {
        $keyword = '%'.mb_strtolower(trim($search)).'%';

        $query->where(function (Builder $memberQuery) use ($keyword): void {
            foreach (['name', 'member_no', 'no_anggota', 'nama_anggota'] as $column) {
                $memberQuery->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$keyword]);
            }
        });
    }
}
