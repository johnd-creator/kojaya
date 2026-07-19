<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_store_funding_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('member_store_accounts')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('method', 10);
            $table->bigInteger('amount');
            $table->string('status', 15)->default('pending');
            $table->string('proof_path', 255)->nullable();
            $table->string('bank_reference', 120)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('idempotency_key', 120);
            $table->foreignId('posted_ledger_entry_id')->nullable()->constrained('member_store_ledger_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique('idempotency_key', 'member_store_funding_idempotency_unique');
            $table->index(['account_id', 'status']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_store_funding_requests');
    }
};
