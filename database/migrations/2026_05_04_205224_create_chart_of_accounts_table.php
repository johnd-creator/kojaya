<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 30);
            $table->string('name');
            $table->string('account_type', 30);
            $table->string('normal_balance', 10);
            $table->string('category', 30);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['account_type', 'category']);
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
