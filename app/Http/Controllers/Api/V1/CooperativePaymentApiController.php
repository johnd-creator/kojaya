<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\MarkDuesPaidRequest;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Http\Resources\CooperativePaymentResource;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativePaymentService;
use App\Support\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CooperativePaymentApiController extends Controller
{
    public function store(StoreCooperativePaymentRequest $request, CooperativePaymentService $service, OrganizationScopedQueryService $scopeService): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        $data = $request->validated();
        $memberQuery = CooperativeMember::query()->whereKey($data['cooperative_member_id']);
        $scopeService->scopeVisibleTo($memberQuery, $request->user());
        $memberQuery->firstOrFail();

        if ($request->hasFile('proof')) {
            $data['proof_path'] = $request->file('proof')->store('cooperative/payment-proofs/admin-api', 'public');
        }

        $payment = $service->record($data, $request->user());

        if ($payment->status === 'APPROVED') {
            $payment = $service->approve($payment, $request->user());
        }

        return (new CooperativePaymentResource($payment->load(['member', 'invoice.contributionType', 'contributionType'])))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(Request $request, CooperativePayment $payment, CooperativePaymentService $service): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);
        $this->authorize('approve', $payment);

        return (new CooperativePaymentResource($service->approve($payment, $request->user())->load(['member', 'invoice.contributionType', 'contributionType'])))
            ->response();
    }

    public function batch(
        MarkDuesPaidRequest $request,
        CooperativePaymentService $service,
        OrganizationScopedQueryService $scopeService,
        AuditLogService $audit,
    ): JsonResponse {
        $this->authorizeCooperativeAccess($request);

        $context = AuditContext::fromHttp($request);
        $invoiceIds = $request->validated('invoice_ids');
        $audit->log('payment.batch.requested', 'cooperative.payment', null, [
            'new' => [
                'requested_count' => count($invoiceIds),
                'payment_method' => $request->validated('payment_method') ?: 'CASH',
                'scope' => 'organization',
                'organization_id' => $request->user()?->organization_id,
            ],
            'reason' => 'Cooperative payment batch requested.',
        ], $context);

        try {
            $result = DB::transaction(function () use ($request, $service, $scopeService): array {
                $payments = collect();

                $invoices = CooperativeDuesInvoice::query()
                    ->with(['member', 'contributionType'])
                    ->whereIn('id', $request->validated('invoice_ids'))
                    ->whereIn('status', ['UNPAID', 'PARTIAL'])
                    ->lockForUpdate()
                    ->get();

                $visibleInvoices = $invoices->filter(function (CooperativeDuesInvoice $invoice) use ($scopeService, $request): bool {
                    $memberQuery = CooperativeMember::query()->whereKey($invoice->cooperative_member_id);
                    $scopeService->scopeVisibleTo($memberQuery, $request->user());

                    return $memberQuery->exists();
                });
                abort_if($visibleInvoices->count() !== count($request->validated('invoice_ids')), 403, 'Semua invoice harus berada dalam organisasi yang sama.');

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

                    $payments->push($service->approve($payment, $request->user())->load(['member', 'invoice.contributionType', 'contributionType']));
                }

                return [
                    'processed_count' => $payments->count(),
                    'total_amount' => round($payments->sum(fn (CooperativePayment $payment): float => (float) $payment->amount), 2),
                    'payments' => CooperativePaymentResource::collection($payments->values())->resolve($request),
                ];
            });
        } catch (Throwable $exception) {
            $audit->log('payment.batch.failed', 'cooperative.payment', null, [
                'new' => [
                    'requested_count' => count($invoiceIds),
                    'scope' => 'organization',
                    'organization_id' => $request->user()?->organization_id,
                ],
                'reason' => 'Cooperative payment batch failed.',
            ], $context);

            throw $exception;
        }

        $audit->log('payment.batch.completed', 'cooperative.payment', null, [
            'new' => [
                'requested_count' => count($invoiceIds),
                'processed_count' => $result['processed_count'],
                'total_amount' => $result['total_amount'],
                'scope' => 'organization',
                'organization_id' => $request->user()?->organization_id,
            ],
            'reason' => 'Cooperative payment batch completed.',
        ], $context);

        return response()->json([
            'data' => $result,
        ], 201);
    }

    private function authorizeCooperativeAccess(Request $request): void
    {
        abort_unless($request->user()?->can('manage_cooperative_payment'), 403);
    }
}
