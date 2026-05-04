<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Finance/JournalEntries/Index', [
            'entries' => JournalEntry::query()
                ->with(['postedBy', 'lines.account'])
                ->latest('entry_date')
                ->paginate(15)
                ->withQueryString(),
            'accounts' => ChartOfAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'normal_balance']),
        ]);
    }

    public function store(StoreJournalEntryRequest $request, JournalEntryService $journalEntryService): RedirectResponse
    {
        $entry = $journalEntryService->create($request->validated(), $request->user());

        return redirect()->route('finance.journal-entries.index')->with('success', "Jurnal {$entry->journal_number} berhasil dibuat.");
    }
}
