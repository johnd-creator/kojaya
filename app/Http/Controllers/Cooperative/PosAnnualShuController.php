<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeShuPeriod;
use App\Services\Cooperative\AnnualShuDistributionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosAnnualShuController extends Controller
{
    public function index(Request $request, AnnualShuDistributionService $service): Response
    {
        $this->authorize('viewAny', CooperativeShuPeriod::class);

        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Cooperative/Pos/Shu/Index', [
            'preview' => $service->preview($year),
            'closedPeriod' => CooperativeShuPeriod::query()
                ->with('allocations.member')
                ->where('year', $year)
                ->first(),
            'filters' => [
                'year' => $year,
            ],
        ]);
    }
}
