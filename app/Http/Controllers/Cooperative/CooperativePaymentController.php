<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\BulkApprovePaymentsRequest;
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
    private const SORT_WHITELIST = ['paid_at', 'status', 'amount', 'id'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CooperativePayment::class);

        $query = CooperativePayment::query()->with(['member', 'invoice.contributionType', 'contributionType']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sortField = in_array($request->input('sort_field'), self::SORT_WHITELIST, true)
            ? $request->input('sort_field')
            : 'paid_at';
        $sortDirection = $request->input('sort_direction') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortDirection)->orderByDesc('id');

        return Inertia::render('Cooperative/Payments/Index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
            'contributionTypes' => $this->paymentContributionTypes()->get(),
            'filters' => array_merge(
                $request->only(['status']),
                ['sort_field' => $sortField, 'sort_direction' => $sortDirection],
            ),
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

    public function bulkApprove(BulkApprovePaymentsRequest $request, CooperativePaymentService $service): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('Admin Koperasi'), 403);

        $ids = $request->validated('ids');

        $payments = CooperativePayment::query()
            ->whereIn('id', $ids)
            ->get();

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
