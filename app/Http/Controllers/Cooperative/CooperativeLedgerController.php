<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeLedgerController extends Controller
{
    public function index(Request $request, SavingsSummaryService $savingsSummary): Response
    {
        $filters = [
            ...$request->only([
                'member_search',
                'member_id',
                'category',
                'contribution_type_id',
                'start_date',
                'end_date',
                'entry_type',
            ]),
            'ledger_scope' => $request->input('ledger_scope', 'SAVINGS'),
        ];

        $query = $savingsSummary->ledgerQuery(filters: $filters);

        return Inertia::render('Cooperative/Ledger/Index', [
            'entries' => $query->orderByDesc('posted_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'summary' => $savingsSummary->summary(filters: $filters),
            'contributionTypes' => CooperativeContributionType::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => CooperativeContributionType::query()
                ->where('is_active', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'entryTypes' => CooperativeLedgerEntry::query()
                ->select('entry_type')
                ->distinct()
                ->orderBy('entry_type')
                ->pluck('entry_type'),
        ]);
    }
}
