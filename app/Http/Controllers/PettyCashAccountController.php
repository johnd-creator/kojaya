<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PettyCashAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PettyCashAccountController extends Controller
{
    public function index()
    {
        $accounts = PettyCashAccount::with('organization')
            ->orderBy('created_at', 'desc')
            ->get();

        $organizations = Organization::orderBy('name')->get();

        return Inertia::render('PettyCash/Index', [
            'accounts' => $accounts,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'limit' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        PettyCashAccount::create($validated);

        return redirect()->route('petty-cash.index')
            ->with('success', 'Petty Cash Account created successfully.');
    }

    public function show(string $id)
    {
        $account = PettyCashAccount::with(['organization', 'transactions.user'])
            ->findOrFail($id);

        return Inertia::render('PettyCash/Show', [
            'account' => $account,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $account = PettyCashAccount::findOrFail($id);

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'limit' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $account->update($validated);

        return redirect()->back()
            ->with('success', 'Petty Cash Account updated successfully.');
    }

    public function destroy(string $id)
    {
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
