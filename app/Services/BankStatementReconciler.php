<?php

namespace App\Services;

use App\Enums\PermissionEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BankStatementReconciler
{
    public function __construct(
        private readonly ?OrganizationScopeService $scopeService = null,
    ) {}

    public function reconcileCsv(string $csv, ?User $user = null): int
    {
        $actor = $user ?? auth()->user();
        if (! $actor) {
            return 0;
        }

        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        if (count($lines) < 2) {
            return 0;
        }

        $header = array_map('strtoupper', str_getcsv(array_shift($lines)));
        $refIndex = array_search('REFERENCE', $header);
        $amountIndex = array_search('AMOUNT', $header);

        if ($refIndex === false || $amountIndex === false) {
            return 0;
        }

        $scope = $this->scopeService ?? app(OrganizationScopeService::class);

        return DB::transaction(function () use ($lines, $refIndex, $amountIndex, $actor, $scope): int {
            $matched = 0;

            foreach ($lines as $line) {
                $cols = str_getcsv($line);
                $ref = $cols[$refIndex] ?? null;
                $amount = (float) trim((string) ($cols[$amountIndex] ?? 0));

                if (! $ref || ! str_starts_with($ref, 'INV-')) {
                    continue;
                }

                $invoiceId = substr($ref, 4);

                try {
                    /** @var Invoice $invoice */
                    $invoice = $scope->resolveVisible(
                        Invoice::class,
                        $actor,
                        $invoiceId,
                        PermissionEnum::INVOICE_VIEW_ALL->value
                    );
                } catch (\Throwable) {
                    // Nonexistent or foreign invoice: skip silently without leaking existence
                    continue;
                }

                if (! Gate::forUser($actor)->allows('reconcilePayment', $invoice)) {
                    continue;
                }

                $invoiceTotal = round((float) $invoice->total_amount, 2);
                $statementAmount = round($amount, 2);

                if (abs($invoiceTotal - $statementAmount) < 0.01) {
                    $invoice->status = 'PAID';
                    $invoice->save();
                    $matched++;
                }
            }

            return $matched;
        });
    }
}
