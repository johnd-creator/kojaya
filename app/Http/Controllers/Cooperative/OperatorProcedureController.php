<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\CompleteClosingStepRequest;
use App\Http\Requests\Cooperative\LockPeriodRequest;
use App\Http\Requests\Cooperative\ReconcilePaymentRequest;
use App\Models\CooperativeClosingChecklist;
use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\CooperativePeriodLockService;
use App\Services\Cooperative\OperatorProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OperatorProcedureController extends Controller
{
    public function dashboard(): Response
    {
        $this->authorizePermission('view_cooperative_report');

        return Inertia::render('Cooperative/Operator/Dashboard');
    }

    public function approvalInbox(OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->approvalInbox()]);
    }

    public function exceptions(OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->exceptions()]);
    }

    public function analytics(OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->analytics()]);
    }

    public function closingPage(): Response
    {
        $this->authorizePermission('manage_cooperative_settings');

        return Inertia::render('Cooperative/Operator/Closing');
    }

    public function closing(string $period, CooperativePeriodLockService $service): JsonResponse
    {
        $this->authorizePermission('manage_cooperative_settings');

        $service->ensureChecklist($period);

        return response()->json([
            'data' => [
                'period' => $period,
                'is_locked' => $service->isLocked($period),
                'checklist' => CooperativeClosingChecklist::query()
                    ->where('period', $period)
                    ->where('module', 'COOPERATIVE')
                    ->orderBy('id')
                    ->get(),
            ],
        ]);
    }

    public function completeClosingStep(string $period, CompleteClosingStepRequest $request, CooperativePeriodLockService $service): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $service->completeStep($period, $validated['step_key'], $request->user(), $validated['notes'] ?? null),
        ]);
    }

    public function lock(string $period, LockPeriodRequest $request, CooperativePeriodLockService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->lock($period, $request->user(), $request->validated('reason')),
        ], SymfonyResponse::HTTP_CREATED);
    }

    public function unlock(string $period, Request $request, CooperativePeriodLockService $service): JsonResponse
    {
        $this->authorizePermission('manage_cooperative_settings');

        return response()->json([
            'data' => $service->unlock($period, $request->user()),
        ]);
    }

    public function reconcilePayment(CooperativePayment $payment, ReconcilePaymentRequest $request, CooperativePaymentService $service): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $service->reconcile($payment, $request->user(), $validated['reference'], (bool) ($validated['approve'] ?? true)),
        ]);
    }

    public function export(Request $request, OperatorProcedureService $service): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizePermission('view_cooperative_report');

        $type = (string) $request->query('type', 'members');
        $period = $request->query('period') ? (string) $request->query('period') : null;
        $csv = $service->export($type, $period);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cooperative-'.$type.'-'.now()->format('YmdHis').'.csv"',
        ]);
    }
}
