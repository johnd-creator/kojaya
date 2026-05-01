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
            $table->boolean('is_thr')->default(false)->after('status');
            $table->integer('thr_proportion_months')->nullable()->after('is_thr');
            $table->decimal('thr_amount', 15, 2)->default(0)->after('thr_proportion_months');
            $table->text('thr_calculation_breakdown')->nullable()->after('thr_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['is_thr', 'thr_proportion_months', 'thr_amount', 'thr_calculation_breakdown']);
        });
    }
};
