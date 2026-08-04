<?php

namespace App\Http\Controllers\Cooperative;

use App\Concerns\ResolvesApiPageSize;
use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\GenerateDuesRequest;
use App\Http\Requests\Cooperative\MarkDuesPaidRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\DuesGenerationService;
use App\Support\PaginationLimitResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeDuesController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $period = $this->periodFromRequest($request);
        $periodScope = $request->input('period_scope') === 'all' ? 'all' : 'period';

        $query = CooperativeDuesInvoice::query()
            ->forSavingsDues()
            ->forActiveMembers()
            ->with(['member', 'contributionType']);
        $scopeService->scopeVisibleTo($query, $request->user());
        $status = $request->input('status', '');

        if ($periodScope !== 'all') {
            $query->where('period', $period);
        }

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

        $perPage = $this->apiPageSize($request, maximum: PaginationLimitResolver::ADMIN_MAXIMUM);

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
            'contributionTypes' => CooperativeContributionType::query()
                ->savingsDues()
                ->orderByRaw("CASE WHEN category = 'POKOK' OR code = 'POKOK' THEN 0 WHEN category = 'WAJIB' OR code = 'WAJIB' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->get(),
            'categories' => CooperativeContributionType::query()
                ->savingsDues()
                ->where('is_active', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                ...$request->only(['period', 'member_id', 'member_search', 'contribution_type_id', 'category']),
                'per_page' => $perPage,
                'period' => $period,
                'period_scope' => $periodScope,
                'status' => $status,
            ],
            'monthlyDuesInfo' => $this->monthlyDuesInfo($period, $scopeService, $request),
            'canResetPaidDues' => $this->canResetPaidDues($request),
        ]);
    }

    public function generate(GenerateDuesRequest $request, DuesGenerationService $service): RedirectResponse
    {
        $created = $service->generateForPeriod($request->validated('period'));

        return back()->with('success', "{$created} dues invoices generated.");
    }

    public function markPaid(
        MarkDuesPaidRequest $request,
        CooperativePaymentService $paymentService,
        OrganizationScopedQueryService $scopeService,
    ): RedirectResponse {
        $paidCount = 0;
        $requestedAmount = $request->validated('amount');

        $invoices = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->whereIn('id', $request->validated('invoice_ids'))
            ->whereIn('status', ['UNPAID', 'PARTIAL']);
        $scopeService->scopeVisibleTo($invoices, $request->user());
        $invoices = $invoices->get();

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

    public function markUnpaid(
        CooperativeDuesInvoice $invoice,
        Request $request,
        CooperativePaymentService $paymentService,
        OrganizationScopedQueryService $scopeService,
    ): RedirectResponse {
        $scopeService->assertVisible($request->user(), $invoice);
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

    /**
     * @return array<string, mixed>|null
     */
    private function monthlyDuesInfo(
        string $period,
        OrganizationScopedQueryService $scopeService,
        Request $request,
    ): ?array {
        $type = CooperativeContributionType::query()
            ->where('is_active', true)
            ->where('frequency', 'MONTHLY')
            ->where(function (Builder $query): void {
                $query->where('code', 'WAJIB')
                    ->orWhere('category', 'WAJIB');
            })
            ->orderByRaw("CASE WHEN code = 'WAJIB' THEN 0 ELSE 1 END")
            ->first();

        if (! $type) {
            return null;
        }

        $periodDate = CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth();
        $aggregateQuery = CooperativeDuesInvoice::query()
            ->forActiveMembers()
            ->where('period', $period)
            ->where('cooperative_contribution_type_id', $type->id);
        $scopeService->scopeVisibleTo($aggregateQuery, $request->user());
        $aggregate = $aggregateQuery
            ->selectRaw(
                'COUNT(*) as total_invoices,
                 COALESCE(SUM(amount), 0) as total_nominal,
                 COALESCE(SUM(paid_amount), 0) as total_paid,
                 COALESCE(SUM(CASE WHEN amount - paid_amount > 0 THEN amount - paid_amount ELSE 0 END), 0) as total_outstanding'
            )
            ->first();

        return [
            'title' => $type->name.' '.$this->periodLabel($periodDate),
            'period' => $period,
            'period_label' => $this->periodLabel($periodDate),
            'next_period_label' => $this->periodLabel($periodDate->addMonth()),
            'type_name' => $type->name,
            'amount' => (float) $type->default_amount,
            'due_date' => $periodDate->day(10)->toDateString(),
            'total_invoices' => (int) ($aggregate->total_invoices ?? 0),
            'total_nominal' => (float) ($aggregate->total_nominal ?? 0),
            'total_paid' => (float) ($aggregate->total_paid ?? 0),
            'total_outstanding' => (float) ($aggregate->total_outstanding ?? 0),
        ];
    }

    private function periodLabel(CarbonImmutable $periodDate): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return ($months[(int) $periodDate->format('n')] ?? $periodDate->format('F')).' '.$periodDate->format('Y');
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
