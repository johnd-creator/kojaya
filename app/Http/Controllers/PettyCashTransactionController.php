<?php

namespace App\Http\Controllers;

use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PettyCashTransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'petty_cash_account_id' => 'required|exists:petty_cash_accounts,id',
            'transaction_date' => 'required|date',
            'type' => 'required|in:DEBIT,CREDIT',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'reference_no' => 'nullable|string',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $account = PettyCashAccount::findOrFail($validated['petty_cash_account_id']);

        // Check limit if Credit
        if ($validated['type'] === 'CREDIT') {
            if ($account->balance < $validated['amount']) {
                return redirect()->back()->withErrors(['amount' => 'Insufficient balance.']);
            }
        }

        DB::transaction(function () use ($validated, $account, $request) {
            $path = null;
            if ($request->hasFile('proof_file')) {
                $path = $request->file('proof_file')->store('petty_cash_proofs', 'public');
            }

            $transaction = PettyCashTransaction::create([
                'petty_cash_account_id' => $account->id,
                'user_id' => Auth::id(),
                'transaction_date' => $validated['transaction_date'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'reference_no' => $validated['reference_no'] ?? null,
                'status' => 'APPROVED', // Auto-approve for now
                'proof_file' => $path,
            ]);

            // Update Account Balance
            if ($validated['type'] === 'DEBIT') {
                $account->increment('balance', $validated['amount']);
            } else {
                $account->decrement('balance', $validated['amount']);
            }
        });

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }
}
