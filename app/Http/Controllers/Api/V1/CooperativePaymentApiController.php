<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativePaymentRequest;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    private function authorizeCooperativeAccess(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi']), 403);
    }
}
