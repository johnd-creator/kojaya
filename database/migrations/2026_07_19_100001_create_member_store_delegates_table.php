<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_store_delegates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('member_store_accounts')->restrictOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('display_name', 120);
            $table->string('code', 40);
            $table->bigInteger('per_transaction_limit')->nullable();
            $table->bigInteger('daily_limit')->nullable();
            $table->date('valid_from');
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code'], 'member_store_delegates_code_unique');
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_store_delegates');
    }
};
