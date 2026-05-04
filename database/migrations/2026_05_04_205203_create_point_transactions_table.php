<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 30);
            $table->integer('points');
            $table->unsignedBigInteger('balance_before')->default(0);
            $table->unsignedBigInteger('balance_after')->default(0);
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('description');
            $table->date('posted_at');
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'posted_at']);
            $table->index(['transaction_type', 'posted_at']);
            $table->unique(['source_type', 'source_id', 'transaction_type'], 'point_transactions_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
