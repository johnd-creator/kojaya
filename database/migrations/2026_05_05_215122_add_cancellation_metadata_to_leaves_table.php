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
        Schema::table('leaves', function (Blueprint $table) {
            $table->timestamp('cancel_requested_at')->nullable()->after('approver_id');
            $table->foreignId('cancel_requested_by')->nullable()->after('cancel_requested_at')->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable()->after('cancel_requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancel_requested_by');
            $table->dropColumn(['cancel_requested_at', 'cancel_reason']);
        });
    }
};
