<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_member_opening_balance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_batch_id')->constrained('cooperative_member_opening_balance_batches')->cascadeOnDelete();
            $table->foreignId('cooperative_contribution_type_id')->constrained()->restrictOnDelete();
            $table->string('category_snapshot', 20);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('months_count')->default(0);
            $table->decimal('unit_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('calculation_method', 20)->default('ONCE');
            $table->text('override_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['opening_balance_batch_id', 'category_snapshot']);
            $table->index(['cooperative_contribution_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_member_opening_balance_lines');
    }
};
