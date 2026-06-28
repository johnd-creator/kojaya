<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->timestamp('manager_reviewed_at')->nullable()->after('first_due_date');
            $table->foreignId('manager_reviewed_by')->nullable()->after('manager_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_reviewed_by');
            $table->dropColumn('manager_reviewed_at');
        });
    }
};
