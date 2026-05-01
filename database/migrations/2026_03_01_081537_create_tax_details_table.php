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
        Schema::create('tax_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->onDelete('set null');
            $table->string('period', 7);
            $table->string('calculation_type', 20);
            $table->decimal('gross_income', 15, 2)->default(0);
            $table->decimal('biaya_jabatan', 15, 2)->default(0);
            $table->decimal('netto', 15, 2)->default(0);
            $table->string('ptkp_status', 10)->nullable();
            $table->decimal('ptkp_amount', 15, 2)->default(0);
            $table->decimal('pkp', 15, 2)->default(0);
            $table->decimal('pph21_annual', 15, 2)->default(0);
            $table->decimal('pph21_monthly', 15, 2)->default(0);
            $table->boolean('npwp_available')->default(true);
            $table->decimal('no_npwp_surcharge_percent', 5, 2)->default(0);
            $table->decimal('final_pph21_amount', 15, 2)->default(0);
            $table->decimal('bpjs_kesehatan_amount', 15, 2)->default(0);
            $table->decimal('bpjs_jht_amount', 15, 2)->default(0);
            $table->decimal('bpjs_jp_amount', 15, 2)->default(0);
            $table->decimal('bpjs_jkk_amount', 15, 2)->default(0);
            $table->decimal('bpjs_jkm_amount', 15, 2)->default(0);
            $table->decimal('total_bpjs', 15, 2)->default(0);
            $table->json('calculation_breakdown')->nullable();
            $table->string('external_service_ref', 100)->nullable();
            $table->string('calculation_source', 20)->default('INTERNAL');
            $table->timestamps();

            $table->index(['employee_id', 'period']);
            $table->index('payroll_id');
            $table->index('calculation_source');
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_details');
    }
};
