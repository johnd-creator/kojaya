<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_types', function (Blueprint $table): void {
            $table->unsignedSmallInteger('npl_threshold_days')->default(90)->after('eligibility_rules');
        });

        Schema::create('loan_restructures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('PENDING');
            $table->text('reason');
            $table->decimal('proposed_principal_amount', 15, 2)->nullable();
            $table->decimal('proposed_interest_rate', 8, 4)->nullable();
            $table->unsignedInteger('proposed_term_months')->nullable();
            $table->date('proposed_first_due_date')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'status']);
            $table->index(['cooperative_member_id', 'status']);
        });

        Schema::create('savings_withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 30)->default('PENDING');
            $table->string('destination_bank')->nullable();
            $table->string('destination_account_no')->nullable();
            $table->string('destination_account_name')->nullable();
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'status']);
        });

        Schema::create('pos_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('cooperative_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_no', 60)->unique();
            $table->string('status', 30)->default('APPROVED');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('points_reversed')->default(0);
            $table->text('reason')->nullable();
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->index(['pos_transaction_id', 'status']);
            $table->index(['returned_at', 'status']);
        });

        Schema::create('pos_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_transaction_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('pos_product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->index(['pos_transaction_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_returns');
        Schema::dropIfExists('savings_withdrawals');
        Schema::dropIfExists('loan_restructures');

        Schema::table('loan_types', function (Blueprint $table): void {
            $table->dropColumn('npl_threshold_days');
        });
    }
};
