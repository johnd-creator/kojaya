<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('employee_type', 20);          // TKWT | Organic
            $table->foreignId('job_grade_id')->constrained('job_grades')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->unsignedInteger('min_tenure_months')->default(0);   // masa kerja minimum (bulan)
            $table->unsignedInteger('max_tenure_months')->nullable();   // null = tidak dibatasi
            $table->date('effective_from');
            $table->date('effective_until')->nullable();                 // null = masih berlaku
            $table->timestamps();

            // Composite index for fast lookup
            $table->index(['employee_type', 'job_grade_id', 'organization_id', 'effective_from'], 'salary_structures_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
