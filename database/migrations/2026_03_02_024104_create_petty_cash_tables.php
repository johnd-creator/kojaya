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
        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('limit', 15, 2)->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('petty_cash_account_id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('transaction_date');
            $table->enum('type', ['DEBIT', 'CREDIT']);
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->string('reference_no')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->string('proof_file')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('petty_cash_account_id')->references('id')->on('petty_cash_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('petty_cash_accounts');
    }
};
