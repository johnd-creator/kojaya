<?php

namespace App\Http\Controllers;

use App\Enums\PermissionEnum;
use App\Http\Requests\ReconcileBankStatementRequest;
use App\Http\Requests\StoreBankTransferBatchRequest;
use App\Models\BankTransferBatch;
use App\Models\BankTransferItem;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\BankFileGenerator;
use App\Services\BankStatementReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceBankController extends Controller
{
    public function index(Request $request, OrganizationScopeService $scopeService)
    {
        $this->authorizePermission('manage_bank_batch');

        $batches = $scopeService->scopeVisibleTo(
            BankTransferBatch::query(),
            $request->user(),
            PermissionEnum::BANK_BATCH_VIEW_ALL->value
        )->orderBy('created_at', 'desc')->get();

        return inertia('Finance/BankBatches/Index', [
            'batches' => $batches,
        ]);
    }

    public function store(StoreBankTransferBatchRequest $request)
    {
        $this->authorizePermission('manage_bank_batch');

        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($validated, $user) {
            $batch = BankTransferBatch::create([
                'organization_id' => $user->organization_id,
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
        });

        return redirect()->route('finance.bank-batches.index')->with('success', 'Bank transfer batch created.');
    }

    public function export(Request $request, string $batch, BankFileGenerator $generator, OrganizationScopeService $scopeService)
    {
        $this->authorizePermission('manage_bank_batch');

        /** @var BankTransferBatch $batchModel */
        $batchModel = $scopeService->resolveVisible(
            BankTransferBatch::class,
            $request->user(),
            $batch,
            PermissionEnum::BANK_BATCH_VIEW_ALL->value
        );

        $this->authorize('export', $batchModel);

        $batchModel->load('items');
        $csv = $generator->generateCsv($batchModel);
        $filename = 'bank_batch_'.$batchModel->id.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reconcile(ReconcileBankStatementRequest $request, BankStatementReconciler $reconciler)
    {
        $this->authorizePermission('manage_bank_reconciliation');

        $validated = $request->validated();

        $matched = $reconciler->reconcileCsv($validated['statement_csv'], $request->user());

        return back()->with('success', "Reconciled {$matched} payments.");
    }
}
