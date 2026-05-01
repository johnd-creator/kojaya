<?php

namespace App\Http\Controllers;

use App\Models\BankTransferBatch;
use App\Models\BankTransferItem;
use App\Models\Invoice;
use App\Services\BankFileGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceBankController extends Controller
{
    public function index()
    {
        $batches = BankTransferBatch::orderBy('created_at', 'desc')->get();

        return inertia('Finance/BankBatches/Index', [
            'batches' => $batches,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'format' => 'in:CSV,XML,FW',
            'batch_date' => 'required|date',
            'reference' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.beneficiary_name' => 'required|string',
            'items.*.beneficiary_account' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.currency' => 'in:IDR',
            'items.*.reference' => 'nullable|string',
            'items.*.invoice_id' => 'nullable|uuid|exists:invoices,id',
        ]);

        $batch = BankTransferBatch::create([
            'organization_id' => Auth::user()->organization_id,
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'format' => $validated['format'] ?? 'CSV',
            'batch_date' => $validated['batch_date'],
            'reference' => $validated['reference'] ?? null,
            'status' => 'DRAFT',
        ]);

        foreach ($validated['items'] as $it) {
            BankTransferItem::create([
                'batch_id' => $batch->id,
                'beneficiary_name' => $it['beneficiary_name'],
                'beneficiary_account' => $it['beneficiary_account'],
                'amount' => $it['amount'],
                'currency' => $it['currency'] ?? 'IDR',
                'reference' => $it['reference'] ?? null,
                'invoice_id' => $it['invoice_id'] ?? null,
                'status' => 'PENDING',
            ]);
        }

        return redirect()->route('finance.bank-batches.index')->with('success', 'Bank transfer batch created.');
    }

    public function export(BankTransferBatch $batch, BankFileGenerator $generator)
    {
        $batch->load('items');
        $csv = $generator->generateCsv($batch);
        $filename = 'bank_batch_'.$batch->id.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function reconcile(Request $request)
    {
        $validated = $request->validate([
            'statement_csv' => 'required|string',
        ]);

        $lines = array_filter(array_map('trim', explode("\n", $validated['statement_csv'])));
        if (count($lines) < 2) {
            return back()->with('error', 'Statement file is empty.');
        }
        $header = array_map('strtoupper', str_getcsv(array_shift($lines)));
        $refIndex = array_search('REFERENCE', $header);
        $amountIndex = array_search('AMOUNT', $header);

        $matched = 0;
        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $ref = $cols[$refIndex] ?? null;
            $amount = (float) trim((string) ($cols[$amountIndex] ?? 0));

            if ($ref && str_starts_with($ref, 'INV-')) {
                $invoiceId = substr($ref, 4);
                $invoice = Invoice::find($invoiceId);
                if ($invoice && (float) $invoice->total_amount === $amount) {
                    $invoice->status = 'PAID';
                    $invoice->save();
                    $matched++;
                }
            }
        }

        return back()->with('success', "Reconciled {$matched} payments.");
    }
}
