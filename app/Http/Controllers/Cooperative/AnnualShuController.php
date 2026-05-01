<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\PreviewAnnualShuRequest;
use App\Models\CooperativeShuPeriod;
use App\Services\Cooperative\AnnualShuDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnualShuController extends Controller
{
    public function index(Request $request, AnnualShuDistributionService $service): Response
    {
        $year = (int) $request->input('year', now()->year);
        $cooperativePool = (float) $request->input('cooperative_pool', 0);
        $posProfitPool = $request->filled('pos_profit_pool') ? (float) $request->input('pos_profit_pool') : null;

        return Inertia::render('Cooperative/Shu/Index', [
            'preview' => $service->preview($year, $cooperativePool, $posProfitPool),
            'closedPeriod' => CooperativeShuPeriod::query()
                ->with('allocations.member')
                ->where('year', $year)
                ->first(),
            'filters' => [
                'year' => $year,
                'cooperative_pool' => $cooperativePool,
                'pos_profit_pool' => $posProfitPool,
            ],
        ]);
    }

    public function close(PreviewAnnualShuRequest $request, AnnualShuDistributionService $service): RedirectResponse
    {
        $validated = $request->validated();

        $service->close(
            (int) $validated['year'],
            (float) ($validated['cooperative_pool'] ?? 0),
            array_key_exists('pos_profit_pool', $validated) ? (float) $validated['pos_profit_pool'] : null,
            $request->user(),
        );

        return back()->with('success', 'Annual SHU period closed successfully.');
    }
}
