<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertPettyCashAccountRequest;
use App\Models\Organization;
use App\Models\PettyCashAccount;
use Inertia\Inertia;

class PettyCashAccountController extends Controller
{
    public function index()
    {
        $this->authorizePermission('manage_petty_cash');

        $accounts = PettyCashAccount::with('organization')
            ->orderBy('created_at', 'desc')
            ->get();

        $organizations = Organization::orderBy('name')->get();

        return Inertia::render('PettyCash/Index', [
            'accounts' => $accounts,
            'organizations' => $organizations,
        ]);
    }

    public function store(UpsertPettyCashAccountRequest $request)
    {
        $this->authorizePermission('manage_petty_cash');

        PettyCashAccount::create($request->validated());

        return redirect()->route('petty-cash.index')
            ->with('success', 'Petty Cash Account created successfully.');
    }

    public function show(string $id)
    {
        $this->authorizePermission('manage_petty_cash');

        $account = PettyCashAccount::with(['organization', 'transactions.user'])
            ->findOrFail($id);

        return Inertia::render('PettyCash/Show', [
            'account' => $account,
        ]);
    }

    public function update(UpsertPettyCashAccountRequest $request, string $id)
    {
        $this->authorizePermission('manage_petty_cash');

        $account = PettyCashAccount::findOrFail($id);

        $account->update($request->validated());

        return redirect()->back()
            ->with('success', 'Petty Cash Account updated successfully.');
    }

    public function destroy(string $id)
    {
        $this->authorizePermission('manage_petty_cash');

        $account = PettyCashAccount::findOrFail($id);

        if ($account->transactions()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete account with existing transactions.');
        }

        $account->delete();

        return redirect()->route('petty-cash.index')
            ->with('success', 'Petty Cash Account deleted successfully.');
    }
}
