<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeLedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeLedgerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CooperativeLedgerEntry::query()->with('member');

        if ($request->filled('member_search')) {
            $search = $request->string('member_search')->toString();
            $query->whereHas('member', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->input('entry_type'));
        }

        return Inertia::render('Cooperative/Ledger/Index', [
            'entries' => $query->orderByDesc('posted_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $request->only(['member_search', 'entry_type']),
        ]);
    }
}
