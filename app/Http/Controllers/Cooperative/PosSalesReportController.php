<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Services\Cooperative\PosSalesReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosSalesReportController extends Controller
{
    public function index(Request $request, PosSalesReportService $service): Response
    {
        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Cooperative/Pos/Reports/Index', [
            'summary' => $service->summaryForYear($year),
            'productSales' => $service->productSalesForYear($year),
            'filters' => ['year' => $year],
        ]);
    }
}
