<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\MarkDuesPaidRequest;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CooperativePaymentApiController extends Controller
{
    public function store(StoreCooperativePaymentRequest $request, CooperativePaymentService $service): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        $payment = $service->record($request->validated(), $request->user());

        if ($payment->status === 'APPROVED') {
            $payment = $service->approve($payment, $request->user());
        }

        return response()->json(['data' => $payment->load(['member', 'invoice'])], 201);
    }

    public function approve(Request $request, CooperativePayment $payment, CooperativePaymentService $service): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        return response()->json(['data' => $service->approve($payment, $request->user())]);
    }

    public function batch(MarkDuesPaidRequest $request, CooperativePaymentService $service): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        $result = DB::transaction(function () use ($request, $service): array {
            $payments = collect();

            $invoices = CooperativeDuesInvoice::query()
                ->with(['member', 'contributionType'])
                ->whereIn('id', $request->validated('invoice_ids'))
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                $remainingAmount = round((float) $invoice->amount - (float) $invoice->paid_amount, 2);

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
                    'notes' => $request->validated('notes') ?: 'Batch payment iuran/simpanan.',
                ]);

                $payments->push($service->approve($payment, $request->user())->load(['member', 'invoice.contributionType']));
            }

            return [
                'processed_count' => $payments->count(),
                'total_amount' => round($payments->sum(fn (CooperativePayment $payment): float => (float) $payment->amount), 2),
                'payments' => $payments->values(),
            ];
        });

        return response()->json([
            'data' => $result,
        ], 201);
    }

    private function authorizeCooperativeAccess(Request $request): void
    {
        abort_unless($request->user()?->can('manage_cooperative_payment'), 403);
    }
}
