<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('location_type', 30)->default('STORE');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        Schema::create('pos_inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_inventory_location_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->timestamps();

            $table->unique(['pos_product_id', 'pos_inventory_location_id']);
        });

        Schema::table('pos_stock_movements', function (Blueprint $table) {
            $table->foreignId('pos_inventory_location_id')->nullable()->after('pos_product_id')
                ->constrained('pos_inventory_locations')->nullOnDelete();
            $table->string('reference_no', 80)->nullable()->after('movement_type');
            $table->index(['pos_inventory_location_id', 'movement_type']);
        });

        Schema::create('pos_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 50)->unique();
            $table->foreignId('pos_supplier_id')->nullable()->constrained('pos_suppliers')->nullOnDelete();
            $table->foreignId('pos_inventory_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_no', 80)->nullable();
            $table->date('received_at');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('POSTED');
            $table->timestamps();

            $table->index(['received_at', 'status']);
        });

        Schema::create('pos_stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_stock_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->string('batch_no', 60)->nullable();
            $table->date('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 50)->unique();
            $table->foreignId('from_location_id')->constrained('pos_inventory_locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('pos_inventory_locations')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transferred_at');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('POSTED');
            $table->timestamps();

            $table->index(['status', 'transferred_at']);
        });

        Schema::create('pos_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('pos_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_no', 50)->unique();
            $table->foreignId('pos_inventory_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('counted_at');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->timestamps();

            $table->index(['status', 'counted_at']);
        });

        Schema::create('pos_stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_qty');
            $table->integer('counted_qty');
            $table->integer('difference');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_count_items');
        Schema::dropIfExists('pos_stock_counts');
        Schema::dropIfExists('pos_stock_transfer_items');
        Schema::dropIfExists('pos_stock_transfers');
        Schema::dropIfExists('pos_stock_receipt_items');
        Schema::dropIfExists('pos_stock_receipts');
        Schema::dropIfExists('pos_suppliers');

        if (Schema::hasColumn('pos_stock_movements', 'pos_inventory_location_id')) {
            Schema::table('pos_stock_movements', function (Blueprint $table) {
                $table->dropIndex(['pos_inventory_location_id', 'movement_type']);
                $table->dropConstrainedForeignId('pos_inventory_location_id');
                $table->dropColumn('reference_no');
            });
        }

        Schema::dropIfExists('pos_inventory_stocks');
        Schema::dropIfExists('pos_inventory_locations');
    }
};
