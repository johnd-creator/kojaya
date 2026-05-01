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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('submission_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'PAID'])->default('DRAFT');
            $table->text('description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reimbursement_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reimbursement_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['TRANSPORT', 'MEAL', 'MEDICAL', 'LODGING', 'OFFICE_SUPPLIES', 'OTHER']);
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->string('receipt_file_path')->nullable();
            $table->date('receipt_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_items');
        Schema::dropIfExists('reimbursements');
    }
};
