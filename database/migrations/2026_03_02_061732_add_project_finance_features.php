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
        // 1. Add project_id to Invoices (Revenue Tracking)
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignUuid('project_id')->nullable()->after('client_id')->constrained('projects')->nullOnDelete();
        });

        // 2. Add project_id to Reimbursements (Direct Cost - Expense)
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->foreignUuid('project_id')->nullable()->after('user_id')->constrained('projects')->nullOnDelete();
        });

        // 3. Add project_id to Petty Cash Transactions (Direct Cost - Material/Consumable)
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->foreignUuid('project_id')->nullable()->after('petty_cash_account_id')->constrained('projects')->nullOnDelete();
        });

        // 4. Create Project Budget Items Table (Budgeting)
        Schema::create('project_budget_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->enum('category', ['MATERIAL', 'LABOR', 'OVERHEAD', 'SUBCONTRACTOR', 'EQUIPMENT', 'OTHERS']);
            $table->string('description');
            $table->decimal('planned_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0); // For quick comparison cache
            $table->timestamps();
        });

        // 5. Create Project Payroll Allocations Table (Direct Cost - Labor)
        Schema::create('project_payroll_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('amount', 15, 2); // Portion of salary allocated to this project
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure one payroll record isn't double-counted beyond reasonable limits logic (handled in app)
            // But we index for speed
            $table->index(['project_id', 'payroll_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_payroll_allocations');
        Schema::dropIfExists('project_budget_items');

        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
