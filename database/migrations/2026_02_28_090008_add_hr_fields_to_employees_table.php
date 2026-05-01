<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_type', 20)->default('Organic')->after('status'); // TKWT | Organic
            $table->foreignId('department_id')->nullable()->after('employee_type')->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('department_id')->constrained('positions')->nullOnDelete();
            $table->foreignId('job_grade_id')->nullable()->after('position_id')->constrained('job_grades')->nullOnDelete();

            // Drop basic_salary — replaced by salary_structures
            $table->dropColumn('basic_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['job_grade_id']);
            $table->dropColumn(['employee_type', 'department_id', 'position_id', 'job_grade_id']);
            $table->decimal('basic_salary', 15, 2)->default(0);
        });
    }
};
