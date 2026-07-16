<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\CompleteClosingStepRequest;
use App\Http\Requests\Cooperative\LockPeriodRequest;
use App\Http\Requests\Cooperative\ReconcilePaymentRequest;
use App\Models\CooperativeClosingChecklist;
use App\Models\CooperativePayment;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\CooperativePeriodLockService;
use App\Services\Cooperative\OperatorProcedureService;
use App\Support\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OperatorProcedureController extends Controller
{
    public function dashboard(Request $request, OperatorProcedureService $service): Response
    {
        $this->authorizePermission('view_cooperative_report');

        return Inertia::render('Cooperative/Operator/Dashboard', [
            'analytics' => Inertia::defer(fn (): array => $service->analytics($request->user()), 'analytics'),
        ]);
    }

    public function approvalInbox(Request $request, OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->approvalInbox($request->user())]);
    }

    public function exceptions(Request $request, OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->exceptions($request->user())]);
    }

    public function analytics(Request $request, OperatorProcedureService $service): JsonResponse
    {
        $this->authorizePermission('view_cooperative_report');

        return response()->json(['data' => $service->analytics($request->user())]);
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
            'data' => $service->reconcile(
                $payment,
                $request->user(),
                $validated['reference'],
                (bool) ($validated['approve'] ?? true),
                AuditContext::fromHttp($request, $request->user()),
            ),
        ]);
    }

    public function export(Request $request, OperatorProcedureService $service, AuditLogService $audit): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizePermission('view_cooperative_report');

        $type = (string) $request->query('type', 'members');
        $period = $request->query('period') ? (string) $request->query('period') : null;
        $audit->log('cooperative.exported', 'cooperative.operator', null, [
            'new' => ['type' => $type, 'period' => $period],
            'reason' => 'Operator cockpit export requested.',
        ]);
        $csv = $service->export($type, $period, $request->user());

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cooperative-'.$type.'-'.now()->format('YmdHis').'.csv"',
        ]);
    }
}
