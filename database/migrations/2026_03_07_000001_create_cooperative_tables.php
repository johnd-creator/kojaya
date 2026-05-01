<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('member_no', 40)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('identity_number', 40)->nullable();
            $table->text('address')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('resigned_at')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('identity_number');
        });

        Schema::create('cooperative_member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('cooperative_contribution_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('category', 30);
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('frequency', 30)->default('MONTHLY');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cooperative_dues_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_contribution_type_id')->constrained()->restrictOnDelete();
            $table->string('period', 7);
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('UNPAID');
            $table->timestamps();

            $table->unique(['cooperative_member_id', 'cooperative_contribution_type_id', 'period'], 'coop_dues_unique_period');
            $table->index(['period', 'status']);
        });

        Schema::create('cooperative_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_dues_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->date('paid_at');
            $table->string('status', 30)->default('PENDING');
            $table->string('proof_path')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
        });

        Schema::create('cooperative_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('source');
            $table->string('entry_type', 40);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('period', 7)->nullable();
            $table->text('description')->nullable();
            $table->date('posted_at');
            $table->timestamps();

            $table->index(['cooperative_member_id', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_ledger_entries');
        Schema::dropIfExists('cooperative_payments');
        Schema::dropIfExists('cooperative_dues_invoices');
        Schema::dropIfExists('cooperative_contribution_types');
        Schema::dropIfExists('cooperative_member_documents');
        Schema::dropIfExists('cooperative_members');
    }
};
