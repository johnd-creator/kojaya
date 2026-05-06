<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePermission('view_chart_of_accounts');

        $query = ChartOfAccount::query()->with('parent');

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        return Inertia::render('Finance/ChartOfAccounts/Index', [
            'accounts' => $query->orderBy('code')->paginate(20)->withQueryString(),
            'filters' => $request->only(['account_type']),
        ]);
    }

    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $this->authorizePermission('manage_chart_of_accounts');

        ChartOfAccount::query()->create([
            ...$request->validated(),
            'organization_id' => $request->validated('organization_id') ?? $request->user()?->organization_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }
}
