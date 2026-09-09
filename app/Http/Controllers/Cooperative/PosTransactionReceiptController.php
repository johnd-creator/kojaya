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
        $relatedScope = fn ($query) => $visibility->global
            ? $query
            : $query->where('organization_id', $visibility->organizationId);

        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(
            PosTransaction::query()->with([
                'member' => $relatedScope,
                'cashier' => $relatedScope,
                'payments',
                'items.product' => fn ($query) => $visibility->global
                    ? $query
                    : $query->where('organization_id', $visibility->organizationId),
            ]),
            $request->user(),
            $transaction
        );

        if (! $visibility->global) {
            $this->maskForeignRelatedIds($transactionModel);
        }

        return response(View::make('cooperative.pos.receipt', [
            'transaction' => $transactionModel,
        ])->render());
    }

    public function pdf(string $transaction, Request $request, OrganizationScopedQueryService $scopedQuery): HttpResponse
    {
        $visibility = $scopedQuery->visibilityFor($request->user());
        $relatedScope = fn ($query) => $visibility->global
            ? $query
            : $query->where('organization_id', $visibility->organizationId);

        /** @var PosTransaction $transactionModel */
        $transactionModel = $scopedQuery->resolveVisible(
            PosTransaction::query()->with([
                'member' => $relatedScope,
                'cashier' => $relatedScope,
                'payments',
                'items.product' => fn ($query) => $visibility->global
                    ? $query
                    : $query->where('organization_id', $visibility->organizationId),
            ]),
            $request->user(),
            $transaction
        );

        if (! $visibility->global) {
            $this->maskForeignRelatedIds($transactionModel);
        }

        $html = View::make('cooperative.pos.receipt', [
            'transaction' => $transactionModel,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="receipt-'.$transactionModel->transaction_no.'.html"',
        ]);
    }

    private function maskForeignRelatedIds(PosTransaction $transaction): void
    {
        if ($transaction->member === null) {
            $transaction->makeHidden('cooperative_member_id');
        }

        if ($transaction->cashier === null) {
            $transaction->makeHidden('cashier_id');
        }
    }
}
