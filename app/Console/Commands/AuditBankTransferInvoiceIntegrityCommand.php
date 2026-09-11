<?php

namespace App\Console\Commands;

use App\Models\BankTransferItem;
use Illuminate\Console\Command;

class AuditBankTransferInvoiceIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank-batches:audit-invoice-integrity {--repair : Automatically nullify invalid cross-tenant invoice relationships}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and optionally repair cross-tenant BankTransferItem to Invoice associations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mismatches = BankTransferItem::query()
            ->whereNotNull('bank_transfer_items.invoice_id')
            ->join('bank_transfer_batches', 'bank_transfer_batches.id', '=', 'bank_transfer_items.batch_id')
            ->join('invoices', 'invoices.id', '=', 'bank_transfer_items.invoice_id')
            ->whereColumn('invoices.organization_id', '!=', 'bank_transfer_batches.organization_id')
            ->select([
                'bank_transfer_items.id',
                'bank_transfer_items.batch_id',
                'bank_transfer_batches.organization_id as batch_org',
                'bank_transfer_items.invoice_id',
                'invoices.organization_id as invoice_org',
            ])
            ->get();

        $count = $mismatches->count();

        if ($count === 0) {
            $this->info('Bank transfer item invoice integrity: ALL CLEAN (0 mismatched rows found).');

            return self::SUCCESS;
        }

        $this->warn("Found {$count} cross-tenant BankTransferItem invoice mismatches:");

        $tableData = $mismatches->map(fn ($m) => [
            'id' => (string) $m->id,
            'batch_id' => (string) $m->batch_id,
            'batch_org' => (string) $m->batch_org,
            'invoice_id' => (string) $m->invoice_id,
            'invoice_org' => (string) $m->invoice_org,
        ])->toArray();

        $this->table(['Item ID', 'Batch ID', 'Batch Org', 'Invoice ID', 'Invoice Org'], $tableData);

        if ($this->option('repair')) {
            $mismatchIds = $mismatches->pluck('id')->all();

            BankTransferItem::whereIn('id', $mismatchIds)->update([
                'invoice_id' => null,
            ]);

            $this->info("Repaired {$count} BankTransferItem records by nullifying foreign invoice_id.");

            return self::SUCCESS;
        }

        $this->error('Run with --repair to nullify invalid cross-tenant invoice relationships.');

        return self::FAILURE;
    }
}
