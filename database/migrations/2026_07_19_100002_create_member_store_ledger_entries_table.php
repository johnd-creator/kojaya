<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_store_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('member_store_accounts')->restrictOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('entry_type', 40);
            $table->bigInteger('amount');
            $table->string('effect', 10);
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key', 120);
            $table->foreignId('reversal_of_entry_id')->nullable()->constrained('member_store_ledger_entries')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delegate_id')->nullable()->constrained('member_store_delegates')->nullOnDelete();
            $table->string('purchaser_name', 120)->nullable();
            $table->text('purchase_note')->nullable();
            $table->string('transaction_no', 80)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->datetime('occurred_at');
            $table->timestamps();

            $table->unique(['account_id', 'idempotency_key'], 'member_store_ledger_idempotency_unique');
            $table->unique(['reference_type', 'reference_id', 'entry_type'], 'member_store_ledger_reference_unique');
            $table->index(['organization_id', 'occurred_at']);
            $table->index(['account_id', 'occurred_at']);
            $table->index('delegate_id');
            $table->index(['account_id', 'transaction_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_store_ledger_entries');
    }
};
