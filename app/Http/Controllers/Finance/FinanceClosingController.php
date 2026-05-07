<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\CompleteClosingStepRequest;
use App\Http\Requests\Cooperative\LockPeriodRequest;
use App\Models\CooperativeClosingChecklist;
use App\Services\Finance\FinanceClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class FinanceClosingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Finance/Closing');
    }

    public function closing(string $period, FinanceClosingService $service): JsonResponse
    {
        if (! request()->user()?->can('view_balance_sheet')) {
            abort(403);
        }

        $service->ensureChecklist($period);

        return response()->json([
            'data' => [
                'period' => $period,
                'is_locked' => $service->isLocked($period),
                'checklist' => CooperativeClosingChecklist::query()
                    ->where('period', $period)
                    ->where('module', 'FINANCE')
                    ->orderBy('id')
                    ->get(),
            ],
        ]);
    }

    public function completeClosingStep(string $period, CompleteClosingStepRequest $request, FinanceClosingService $service): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $service->completeStep($period, $validated['step_key'], $request->user(), $validated['notes'] ?? null),
        ]);
    }

    public function lock(string $period, LockPeriodRequest $request, FinanceClosingService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->lock($period, $request->user(), $request->validated('reason')),
        ], SymfonyResponse::HTTP_CREATED);
    }

    public function unlock(string $period, Request $request, FinanceClosingService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->unlock($period, $request->user()),
        ]);
    }
}
