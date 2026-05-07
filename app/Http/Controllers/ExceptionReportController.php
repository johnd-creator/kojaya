<?php

namespace App\Http\Controllers;

use App\Services\Exceptions\CrossModuleExceptionService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExceptionReportController extends Controller
{
    public function index(): Response
    {
        if (! request()->user()?->can('view_balance_sheet')) {
            abort(403);
        }

        return Inertia::render('Exceptions/Dashboard');
    }

    public function data(CrossModuleExceptionService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->allModules(),
        ]);
    }

    public function module(string $module, CrossModuleExceptionService $service): JsonResponse
    {
        $method = match ($module) {
            'cooperative' => 'cooperativeExceptions',
            'finance' => 'financeExceptions',
            'procurement' => 'procurementExceptions',
            'hr' => 'hrExceptions',
            default => abort(404, "Unknown module: {$module}"),
        };

        return response()->json([
            'data' => $service->{$method}(today()->format('Y-m')),
        ]);
    }
}
