<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_no', 50)->unique();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pos_inventory_location_id')->nullable()->constrained()->nullOnDelete();
            $table->date('shift_date');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->integer('transaction_count')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('OPEN');
            $table->timestamps();

            $table->index(['cashier_id', 'shift_date']);
            $table->index(['status', 'shift_date']);
        });

        Schema::create('pos_daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->integer('transaction_count')->default(0);
            $table->decimal('gross_sales', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('total_void', 15, 2)->default(0);
            $table->decimal('total_return', 15, 2)->default(0);
            $table->decimal('net_sales', 15, 2)->default(0);
            $table->decimal('member_credit_outstanding', 15, 2)->default(0);
            $table->json('payment_summary')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('severity', 20)->default('INFO');
            $table->json('payload')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->foreignId('pos_cashier_shift_id')->nullable()->after('cashier_id')
                ->constrained('pos_cashier_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_transactions', 'pos_cashier_shift_id')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pos_cashier_shift_id');
            });
        }

        Schema::dropIfExists('pos_audit_logs');
        Schema::dropIfExists('pos_daily_closings');
        Schema::dropIfExists('pos_cashier_shifts');
    }
};
