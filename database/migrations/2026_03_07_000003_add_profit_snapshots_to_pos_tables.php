<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->default(0)->after('unit_price');
            $table->decimal('unit_profit', 15, 2)->default(0)->after('cost_price');
            $table->decimal('line_profit', 15, 2)->default(0)->after('line_total');
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->decimal('gross_profit', 15, 2)->default(0)->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'unit_profit', 'line_profit']);
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn('gross_profit');
        });
    }
};
