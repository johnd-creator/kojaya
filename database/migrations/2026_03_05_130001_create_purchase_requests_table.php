<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('unit_id')->nullable();
            $table->foreignId('requester_id')->nullable();
            $table->string('title');
            $table->string('cost_center')->nullable();
            $table->string('status')->default('DRAFT');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'submitted_at']);

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
