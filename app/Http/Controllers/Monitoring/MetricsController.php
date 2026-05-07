<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MetricsService;
use Inertia\Inertia;

class MetricsController extends Controller
{
    public function index(MetricsService $metrics)
    {
        return Inertia::render('Monitoring/Metrics', [
            'metrics' => $metrics->dashboard(),
        ]);
    }

    public function json(MetricsService $metrics)
    {
        return response()->json($metrics->dashboard());
    }
}
