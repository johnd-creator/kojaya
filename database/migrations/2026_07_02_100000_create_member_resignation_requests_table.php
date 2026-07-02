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
        Schema::create('member_resignation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained('cooperative_members')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->text('reason')->nullable();
            $table->date('effective_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_resignation_requests');
    }
};
