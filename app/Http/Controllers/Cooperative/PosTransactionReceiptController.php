<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\View;

class PosTransactionReceiptController extends Controller
{
    public function show(PosTransaction $transaction): HttpResponse
    {
        $transaction->load(['member', 'cashier', 'items.product', 'payments']);

        return response(View::make('cooperative.pos.receipt', [
            'transaction' => $transaction,
        ])->render());
    }

    public function pdf(PosTransaction $transaction): HttpResponse
    {
        $transaction->load(['member', 'cashier', 'items.product', 'payments']);

        $html = View::make('cooperative.pos.receipt', [
            'transaction' => $transaction,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="receipt-'.$transaction->transaction_no.'.html"',
        ]);
    }
}
