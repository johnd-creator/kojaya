<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->json('pph21_calculation_breakdown')->nullable()->after('tax_amount');
            $table->decimal('bpjs_kesehatan_amount', 15, 2)->default(0)->after('bpjs_amount');
            $table->decimal('bpjs_jht_amount', 15, 2)->default(0)->after('bpjs_kesehatan_amount');
            $table->decimal('bpjs_jp_amount', 15, 2)->default(0)->after('bpjs_jht_amount');
            $table->decimal('bpjs_jkk_amount', 15, 2)->default(0)->after('bpjs_jp_amount');
            $table->decimal('bpjs_jkm_amount', 15, 2)->default(0)->after('bpjs_jkk_amount');
            $table->json('bpjs_calculation_breakdown')->nullable()->after('bpjs_jkm_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'pph21_calculation_breakdown',
                'bpjs_kesehatan_amount',
                'bpjs_jht_amount',
                'bpjs_jp_amount',
                'bpjs_jkk_amount',
                'bpjs_jkm_amount',
                'bpjs_calculation_breakdown',
            ]);
        });
    }
};
