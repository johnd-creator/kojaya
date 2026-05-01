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
        Schema::create('maintenance_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category'); // PREVENTIVE, CORRECTIVE, SAFETY, QUALITY
            $table->text('description')->nullable();
            $table->json('checklist_items'); // Array of checklist items with instructions
            $table->uuid('asset_category_id')->nullable(); // Apply to specific asset category
            $table->uuid('organization_id')->nullable(); // Organization-specific template
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_checklists');
    }
};
