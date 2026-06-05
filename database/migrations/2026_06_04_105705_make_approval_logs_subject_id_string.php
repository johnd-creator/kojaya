<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE approval_logs ALTER COLUMN subject_id TYPE VARCHAR(64) USING subject_id::text');
        } else {
            Schema::table('approval_logs', function (Blueprint $table) {
                $table->string('subject_id', 64)->change();
            });
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE approval_logs ALTER COLUMN subject_id TYPE UUID USING subject_id::uuid');
        } else {
            Schema::table('approval_logs', function (Blueprint $table) {
                $table->uuid('subject_id')->change();
            });
        }

        Schema::table('approval_logs', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id']);
        });
    }
};
