<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('journal_number')->unique();
            $table->date('entry_date');
            $table->string('status', 30)->default('POSTED');
            $table->string('reference_number')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->text('description');
            $table->timestamps();

            $table->index(['organization_id', 'entry_date']);
            $table->index(['status', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
