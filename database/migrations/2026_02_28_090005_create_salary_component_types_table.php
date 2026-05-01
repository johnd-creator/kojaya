<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_component_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // P1, P2, TGT, TPL, TP
            $table->string('name');               // Pendapatan 1, Tunjangan Jabatan, ...
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_component_types');
    }
};
