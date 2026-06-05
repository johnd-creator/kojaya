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
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('regulation_reference')->nullable();
            $table->json('ptkp_amounts');
            $table->json('progressive_layers');
            $table->decimal('biaya_jabatan_rate', 6, 4);
            $table->decimal('biaya_jabatan_max', 15, 2);
            $table->decimal('no_npwp_surcharge_rate', 6, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'effective_from', 'effective_until']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
