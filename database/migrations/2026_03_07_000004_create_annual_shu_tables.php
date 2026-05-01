<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_member_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('profit_amount', 15, 2);
            $table->unsignedBigInteger('points');
            $table->date('posted_at');
            $table->timestamps();

            $table->unique('pos_transaction_id');
            $table->index(['year', 'cooperative_member_id']);
        });

        Schema::create('cooperative_shu_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->decimal('cooperative_pool', 15, 2)->default(0);
            $table->decimal('pos_profit_pool', 15, 2)->default(0);
            $table->decimal('total_membership_score', 15, 2)->default(0);
            $table->decimal('total_dues_score', 15, 2)->default(0);
            $table->decimal('total_shu_score', 15, 2)->default(0);
            $table->unsignedBigInteger('total_pos_points')->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cooperative_shu_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_shu_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_member_id')->constrained()->cascadeOnDelete();
            $table->decimal('membership_score', 10, 2)->default(0);
            $table->decimal('dues_score', 10, 2)->default(0);
            $table->decimal('shu_score', 10, 2)->default(0);
            $table->decimal('cooperative_shu_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('pos_points')->default(0);
            $table->decimal('pos_shu_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['cooperative_shu_period_id', 'cooperative_member_id'], 'coop_shu_allocation_unique_member');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperative_shu_allocations');
        Schema::dropIfExists('cooperative_shu_periods');
        Schema::dropIfExists('pos_member_points');
    }
};
