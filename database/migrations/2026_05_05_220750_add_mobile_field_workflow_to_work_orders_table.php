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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('description');
            $table->timestamp('started_at')->nullable()->after('assigned_to');
            $table->decimal('start_latitude', 10, 7)->nullable()->after('started_at');
            $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
            $table->decimal('start_accuracy', 8, 2)->nullable()->after('start_longitude');
            $table->decimal('completion_latitude', 10, 7)->nullable()->after('completed_at');
            $table->decimal('completion_longitude', 10, 7)->nullable()->after('completion_latitude');
            $table->decimal('completion_accuracy', 8, 2)->nullable()->after('completion_longitude');
            $table->text('completion_notes')->nullable()->after('completion_accuracy');
            $table->timestamp('reviewed_at')->nullable()->after('completion_notes');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reviewed_by');
            $table->foreignId('reopened_by')->nullable()->after('reopened_at')->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable()->after('reopened_by');
            $table->timestamp('escalated_at')->nullable()->after('reopen_reason');
            $table->foreignId('escalated_by')->nullable()->after('escalated_at')->constrained('users')->nullOnDelete();
            $table->string('escalation_type', 50)->nullable()->after('escalated_by');
            $table->text('escalation_reason')->nullable()->after('escalation_type');
            $table->foreignId('reassignment_requested_to')->nullable()->after('escalation_reason')->constrained('users')->nullOnDelete();

            $table->index(['assigned_to', 'status', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status', 'scheduled_date']);
            $table->dropConstrainedForeignId('reassignment_requested_to');
            $table->dropConstrainedForeignId('escalated_by');
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'scheduled_date',
                'started_at',
                'start_latitude',
                'start_longitude',
                'start_accuracy',
                'completion_latitude',
                'completion_longitude',
                'completion_accuracy',
                'completion_notes',
                'reviewed_at',
                'reopened_at',
                'reopen_reason',
                'escalated_at',
                'escalation_type',
                'escalation_reason',
            ]);
        });
    }
};
