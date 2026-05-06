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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('clock_in_longitude');
            $table->string('clock_in_device_id', 100)->nullable()->after('clock_in_accuracy');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_longitude');
            $table->string('clock_out_device_id', 100)->nullable()->after('clock_out_accuracy');
            $table->json('mobile_audit')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_accuracy',
                'clock_in_device_id',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_accuracy',
                'clock_out_device_id',
                'mobile_audit',
            ]);
        });
    }
};
