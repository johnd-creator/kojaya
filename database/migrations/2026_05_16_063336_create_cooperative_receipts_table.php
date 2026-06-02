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
        Schema::create('cooperative_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 40)->unique();
            $table->foreignId('cooperative_payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cooperative_member_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperative_receipts');
    }
};
