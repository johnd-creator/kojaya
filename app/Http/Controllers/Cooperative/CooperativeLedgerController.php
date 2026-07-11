<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\CancelLedgerPaymentRequest;
use App\Http\Requests\Cooperative\ReviseLedgerPaymentRequest;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeLedgerController extends Controller
{
    public function index(Request $request, SavingsSummaryService $savingsSummary, OrganizationScopedQueryService $scopeService): Response
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
        $scopeService->scopeVisibleTo($query, $request->user());
        $contributionTypes = $this->savingsContributionTypes()->get();

        return Inertia::render('Cooperative/Ledger/Index', [
            'entries' => $query->orderByDesc('posted_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'summary' => $this->scopedSummary($savingsSummary, $filters, $request, $scopeService),
            'contributionTypes' => $contributionTypes,
            'categories' => $this->savingsContributionTypes()
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'entryTypes' => CooperativeLedgerEntry::query()
                ->select('entry_type')
                ->distinct()
                ->orderBy('entry_type')
                ->pluck('entry_type'),
            'canManageLedger' => (bool) $request->user()?->can('manage_cooperative_ledger')
                && (bool) $request->user()?->can('view_cooperative_all'),
        ]);
    }

    public function cancelPayment(CancelLedgerPaymentRequest $request, CooperativeLedgerEntry $entry, CooperativePaymentService $service): RedirectResponse
    {
        $service->cancelLedgerPayment($entry, $request->user(), $request->validated());

        return back()->with('success', 'Transaksi ledger berhasil dibatalkan.');
    }

    public function revisePayment(ReviseLedgerPaymentRequest $request, CooperativeLedgerEntry $entry, CooperativePaymentService $service): RedirectResponse
    {
        $service->reviseLedgerPayment($entry, $request->user(), $request->validated());

        return back()->with('success', 'Transaksi ledger berhasil direvisi.');
    }

    private function savingsContributionTypes(): Builder
    {
        return CooperativeContributionType::query()
            ->where('is_active', true)
            ->whereIn('code', ['POKOK', 'WAJIB', 'SUKARELA']);
    }

    /**
     * Keep the summary query aligned with the paginated ledger scope.
     *
     * @param  array<string, mixed>  $filters
     */
    private function scopedSummary(
        SavingsSummaryService $savingsSummary,
        array $filters,
        Request $request,
        OrganizationScopedQueryService $scopeService,
    ): array {
        $summaryQuery = $savingsSummary->ledgerQuery(filters: $filters);
        $scopeService->scopeVisibleTo($summaryQuery, $request->user());

        return $savingsSummary->summaryFromQuery($summaryQuery);
    }
}
