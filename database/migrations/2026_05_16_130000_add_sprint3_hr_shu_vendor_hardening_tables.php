<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thr_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('months_worked');
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('paid_payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->json('calculation_breakdown')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
            $table->index(['organization_id', 'year', 'status']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->time('corrected_clock_in')->nullable();
            $table->time('corrected_clock_out')->nullable();
            $table->text('reason');
            $table->string('evidence_path')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date', 'status']);
        });

        Schema::table('cooperative_shu_periods', function (Blueprint $table) {
            $table->text('revision_reason')->nullable()->after('closed_by');
            $table->foreignId('revision_requested_by')->nullable()->after('revision_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('revision_requested_at')->nullable()->after('revision_requested_by');
        });

        Schema::create('vendor_performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->unsignedTinyInteger('rating');
            $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
            $table->decimal('quality_acceptance_rate', 5, 2)->default(0);
            $table->unsignedInteger('purchase_order_count')->default(0);
            $table->unsignedInteger('goods_receive_note_count')->default(0);
            $table->timestamp('calculated_at');
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_performance_snapshots');

        Schema::table('cooperative_shu_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revision_requested_by');
            $table->dropColumn(['revision_reason', 'revision_requested_at']);
        });

        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('thr_entitlements');
    }
};
