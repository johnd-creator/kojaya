<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject_type');
            $table->string('subject_id', 64);
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('approved_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};
