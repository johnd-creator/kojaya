<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_checkups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('checkup_date');
            $table->date('next_checkup_date')->nullable();
            $table->enum('result', ['FIT', 'FIT_WITH_RESTRICTION', 'UNFIT'])->default('FIT');
            $table->boolean('fit_to_work')->default(true);
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('clinic_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('next_checkup_date');
            $table->index('checkup_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_checkups');
    }
};
