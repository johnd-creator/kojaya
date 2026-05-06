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
        Schema::create('cooperative_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained('cooperative_members')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_no')->unique();
            $table->string('category', 50)->default('GENERAL');
            $table->string('priority', 20)->default('NORMAL');
            $table->string('subject');
            $table->text('message');
            $table->string('status', 30)->default('OPEN');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperative_support_tickets');
    }
};
