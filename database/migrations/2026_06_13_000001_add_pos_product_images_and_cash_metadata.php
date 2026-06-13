<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_products', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('name');
            $table->string('brand')->nullable()->after('image_path');
            $table->string('variant')->nullable()->after('brand');
            $table->string('unit', 30)->nullable()->after('variant');
            $table->string('rack_location', 60)->nullable()->after('unit');
            $table->boolean('is_discontinued')->default(false)->after('is_active');
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->decimal('cash_received', 15, 2)->nullable()->after('gross_profit');
            $table->decimal('cash_change', 15, 2)->nullable()->after('cash_received');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['cash_received', 'cash_change']);
        });

        Schema::table('pos_products', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'brand', 'variant', 'unit', 'rack_location', 'is_discontinued']);
        });
    }
};
