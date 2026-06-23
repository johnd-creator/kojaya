<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperative_member_opening_balance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->date('calculation_start_period');
            $table->date('calculation_end_period');
            $table->unsignedInteger('months_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->string('source_type', 60)->nullable();
            $table->string('source_reference')->nullable();
            $table->date('source_document_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['status', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_member_opening_balance_batches');
    }
};
