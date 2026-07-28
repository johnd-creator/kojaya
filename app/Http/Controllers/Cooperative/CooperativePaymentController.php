<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\BulkApprovePaymentsRequest;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativePaymentController extends Controller
{
    private const SORT_WHITELIST = ['paid_at', 'status', 'amount', 'id'];

    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('viewAny', CooperativePayment::class);

        $query = CooperativePayment::query()->with(['member', 'invoice.contributionType', 'contributionType']);
        $scopeService->scopeVisibleTo($query, $request->user());

        $status = $request->input(
            'status',
            $request->user()->hasRole('Admin Koperasi') ? 'PENDING' : null,
        );

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('member', function (Builder $memberQuery) use ($search): void {
                $keyword = mb_strtolower($search);
                $memberQuery->where(function (Builder $query) use ($keyword): void {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw('LOWER(member_no) LIKE ?', ["%{$keyword}%"]);
                });
            });
        }

        $period = $request->input('period');
        if (is_string($period) && preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            $periodDate = CarbonImmutable::createFromFormat('!Y-m', $period);

            $query->whereBetween('paid_at', [
                $periodDate->startOfMonth()->toDateString(),
                $periodDate->endOfMonth()->toDateString(),
            ]);
        }

        $paymentMethod = $request->input('payment_method');
        if (is_string($paymentMethod) && in_array($paymentMethod, ['CASH', 'TRANSFER', 'QRIS'], true)) {
            $query->where('payment_method', $paymentMethod);
        }

        $sortField = in_array($request->input('sort_field'), self::SORT_WHITELIST, true)
            ? $request->input('sort_field')
            : 'paid_at';
        $sortDirection = $request->input('sort_direction') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortDirection)->orderByDesc('id');

        return Inertia::render('Cooperative/Payments/Index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'members' => tap(CooperativeMember::query()->active(), fn ($memberQuery) => $scopeService->scopeVisibleTo($memberQuery, $request->user()))
                ->orderBy('name')->get(['id', 'member_no', 'name']),
            'contributionTypes' => $this->paymentContributionTypes()->get(),
            'filters' => array_merge(
                [
                    'status' => $status,
                    'search' => $search,
                    'period' => is_string($period) ? $period : '',
                    'payment_method' => is_string($paymentMethod) ? $paymentMethod : '',
                ],
                ['sort_field' => $sortField, 'sort_direction' => $sortDirection],
            ),
            'canApprovePayments' => $this->canApprovePaymentsFromUi($request),
        ]);
    }

    public function store(
        StoreCooperativePaymentRequest $request,
        CooperativePaymentService $service,
        OrganizationScopedQueryService $scopeService,
    ): RedirectResponse {
        $this->authorize('create', CooperativePayment::class);

        $data = $request->validated();
        $memberQuery = CooperativeMember::query()->whereKey($data['cooperative_member_id']);
        $scopeService->scopeVisibleTo($memberQuery, $request->user());
        $memberQuery->firstOrFail();

        if ($request->hasFile('proof')) {
            $data['proof_path'] = $request->file('proof')->store('cooperative/payment-proofs/admin', 'public');
        }

        $data['status'] = 'APPROVED';

        $payment = $service->record($data, $request->user());

        $service->approve($payment, $request->user());

        return back()->with('success', 'Pembayaran simpanan berhasil dicatat.');
    }

    public function bulkApprove(BulkApprovePaymentsRequest $request, CooperativePaymentService $service, OrganizationScopedQueryService $scopeService): RedirectResponse
    {
        abort_unless($this->canApprovePaymentsFromUi($request), 403);

        $ids = $request->validated('ids');

        $paymentQuery = CooperativePayment::query()->whereIn('id', $ids);
        $scopeService->scopeVisibleTo($paymentQuery, $request->user());
        $payments = $paymentQuery->get();

        abort_if($payments->count() !== count(array_unique($ids)), 403, 'Semua pembayaran harus berada dalam organisasi yang sama.');

        $results = [
            'approved' => 0,
            'skipped' => 0,
        ];

        foreach ($payments as $payment) {
            if ($payment->status !== 'PENDING') {
                $results['skipped']++;

                continue;
            }

            $service->approve($payment, $request->user());
            $results['approved']++;
        }

        $message = collect()
            ->when($results['approved'] > 0, fn ($c) => $c->push("{$results['approved']} pembayaran berhasil disetujui."))
            ->when($results['skipped'] > 0, fn ($c) => $c->push("{$results['skipped']} pembayaran dilewati (status bukan PENDING)."))
            ->implode(' ');

        return back()->with('success', $message);
    }

    public function approve(CooperativePayment $payment, CooperativePaymentService $service, Request $request): RedirectResponse
    {
        abort_unless($this->canApprovePaymentsFromUi($request), 403);

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

    private function canApprovePaymentsFromUi(Request $request): bool
    {
        $user = $request->user();

        return (bool) $user?->can('manage_cooperative_payment')
            && (bool) $user?->can('verify_cooperative_member')
            && ! (bool) $user?->can('view_cooperative_all');
    }
}
