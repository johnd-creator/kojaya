<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\FinancialStatementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialStatementController extends Controller
{
    public function trialBalance(Request $request, FinancialStatementService $financialStatementService): Response
    {
        return Inertia::render('Finance/TrialBalance', [
            'rows' => $financialStatementService->trialBalance(
                $request->user()?->organization_id,
                $request->input('as_of_date')
            ),
            'filters' => $request->only(['as_of_date']),
        ]);
    }

    public function balanceSheet(Request $request, FinancialStatementService $financialStatementService): Response
    {
        return Inertia::render('Finance/BalanceSheet', [
            'statement' => $financialStatementService->balanceSheet(
                $request->user()?->organization_id,
                $request->input('as_of_date')
            ),
            'filters' => $request->only(['as_of_date']),
        ]);
    }

    public function incomeStatement(Request $request, FinancialStatementService $financialStatementService): Response
    {
        return Inertia::render('Finance/IncomeStatement', [
            'statement' => $financialStatementService->incomeStatement(
                $request->user()?->organization_id,
                $request->input('start_date'),
                $request->input('end_date')
            ),
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }
}
