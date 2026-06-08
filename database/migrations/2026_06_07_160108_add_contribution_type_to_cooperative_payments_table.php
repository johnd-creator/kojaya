<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_payments', function (Blueprint $table) {
            $table->foreignId('cooperative_contribution_type_id')
                ->nullable()
                ->after('cooperative_dues_invoice_id')
                ->constrained('cooperative_contribution_types')
                ->nullOnDelete();
        });

        DB::table('cooperative_payments')
            ->select(['id', 'cooperative_dues_invoice_id'])
            ->whereNotNull('cooperative_dues_invoice_id')
            ->orderBy('id')
            ->chunkById(100, function ($payments): void {
                $invoiceTypeIds = DB::table('cooperative_dues_invoices')
                    ->whereIn('id', $payments->pluck('cooperative_dues_invoice_id')->all())
                    ->pluck('cooperative_contribution_type_id', 'id');

                foreach ($payments as $payment) {
                    $contributionTypeId = $invoiceTypeIds->get($payment->cooperative_dues_invoice_id);

                    if ($contributionTypeId === null) {
                        continue;
                    }

                    DB::table('cooperative_payments')
                        ->where('id', $payment->id)
                        ->update([
                            'cooperative_contribution_type_id' => $contributionTypeId,
                        ]);
                }
            });

        DB::table('cooperative_contribution_types')
            ->where('code', 'WAJIB')
            ->update([
                'default_amount' => 100000,
                'frequency' => 'MONTHLY',
            ]);

        DB::table('cooperative_contribution_types')
            ->where('code', 'POKOK')
            ->update([
                'default_amount' => 200000,
                'frequency' => 'ONCE',
            ]);
    }

    public function down(): void
    {
        DB::table('cooperative_contribution_types')
            ->where('code', 'WAJIB')
            ->update([
                'default_amount' => 50000,
                'frequency' => 'MONTHLY',
            ]);

        Schema::table('cooperative_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cooperative_contribution_type_id');
        });
    }
};
