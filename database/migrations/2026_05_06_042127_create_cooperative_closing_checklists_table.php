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
        Schema::create('cooperative_closing_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);
            $table->string('module', 40)->default('COOPERATIVE');
            $table->string('step_key', 80);
            $table->string('step_label');
            $table->string('status', 30)->default('OPEN');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period', 'module', 'step_key'], 'coop_closing_unique_step');
            $table->index(['period', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperative_closing_checklists');
    }
};
