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
        Schema::create('spare_part_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('spare_part_id');
            $table->uuid('warehouse_id');
            $table->uuid('bin_location_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0); // Reserved for open WOs
            $table->timestamps();

            $table->foreign('spare_part_id')->references('id')->on('spare_parts')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->unique(['spare_part_id', 'warehouse_id'], 'unique_part_warehouse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_stocks');
    }
};
