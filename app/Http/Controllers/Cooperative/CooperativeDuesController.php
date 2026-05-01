<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\GenerateDuesRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Services\Cooperative\DuesGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeDuesController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CooperativeDuesInvoice::query()->with(['member', 'contributionType']);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Dues/Index', [
            'invoices' => $query->orderByDesc('period')->orderByDesc('id')->paginate(20)->withQueryString(),
            'contributionTypes' => CooperativeContributionType::query()->orderBy('name')->get(),
            'filters' => $request->only(['period', 'status']),
        ]);
    }

    public function generate(GenerateDuesRequest $request, DuesGenerationService $service): RedirectResponse
    {
        $created = $service->generateForPeriod($request->validated('period'));

        return back()->with('success', "{$created} dues invoices generated.");
    }
}
