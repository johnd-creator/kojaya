<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_store_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('cooperative_member_id')->constrained('cooperative_members')->cascadeOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('credit_limit')->default(0);
            $table->string('status', 20)->default('active');
            $table->datetime('opened_at')->nullable();
            $table->datetime('suspended_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'cooperative_member_id'], 'member_store_accounts_member_unique');
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_store_accounts');
    }
};
