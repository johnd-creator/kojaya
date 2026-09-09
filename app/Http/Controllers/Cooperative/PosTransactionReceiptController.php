<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\View;

class PosTransactionReceiptController extends Controller
{
    public function show(string $transaction, Request $request, OrganizationScopedQueryService $scopedQuery): HttpResponse
    {
        $visibility = $scopedQuery->visibilityFor($request->user());

        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(
            PosTransaction::query()->with([
                'member',
                'cashier',
                'payments',
                'items.product' => fn ($query) => $visibility->global
                    ? $query
                    : $query->where('organization_id', $visibility->organizationId),
            ]),
            $request->user(),
            $transaction
        );

        return response(View::make('cooperative.pos.receipt', [
            'transaction' => $transactionModel,
        ])->render());
    }

    public function pdf(string $transaction, Request $request, OrganizationScopedQueryService $scopedQuery): HttpResponse
    {
        $visibility = $scopedQuery->visibilityFor($request->user());

        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(
            PosTransaction::query()->with([
                'member',
                'cashier',
                'payments',
                'items.product' => fn ($query) => $visibility->global
                    ? $query
                    : $query->where('organization_id', $visibility->organizationId),
            ]),
            $request->user(),
            $transaction
        );

        $html = View::make('cooperative.pos.receipt', [
            'transaction' => $transactionModel,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="receipt-'.$transactionModel->transaction_no.'.html"',
        ]);
    }
}
