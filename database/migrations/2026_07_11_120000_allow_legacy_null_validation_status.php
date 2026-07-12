<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->string('validation_status', 32)->nullable()->default('PENDING')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->string('validation_status', 32)->nullable(false)->default('PENDING')->change();
        });
    }
};
