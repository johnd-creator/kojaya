<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignUuid('spare_part_id')->nullable()->constrained('spare_parts')->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignUuid('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignUuid('spare_part_id')->nullable()->constrained('spare_parts')->nullOnDelete();
        });

        Schema::table('goods_receive_notes', function (Blueprint $table) {
            $table->foreignUuid('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['spare_part_id']);
            $table->dropColumn('spare_part_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['spare_part_id']);
            $table->dropColumn('spare_part_id');
        });

        Schema::table('goods_receive_notes', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
