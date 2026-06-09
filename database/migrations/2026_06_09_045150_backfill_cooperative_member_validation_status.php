<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cooperative_members')
            ->where('status', 'ACTIVE')
            ->where('validation_status', 'PENDING')
            ->update(['validation_status' => 'ACTIVE']);

        DB::table('cooperative_members')
            ->whereIn('status', ['INACTIVE', 'RESIGNED'])
            ->where('validation_status', 'PENDING')
            ->update(['validation_status' => 'INACTIVE']);
    }

    public function down(): void
    {
        //
    }
};
